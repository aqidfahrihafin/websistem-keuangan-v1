<?php

namespace App\Livewire\Admin\Users;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Keluarga;
use App\Models\Lembaga;
use App\Models\Rayon;
use App\Models\User;
use App\Services\KeluargaLinkingService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public function boot(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
    }

    /** Role yang boleh dikelola langsung dari formulir pengguna. */
    private const ROLE_FORM = [
        'admin',
        'bendahara',
        'admin_lembaga',
        'admin_rayon',
        'petugas_kios',
        'pengasuh',
        'wali',
        'santri',
    ];

    public string $search = '';

    #[Url]
    public string $filterRole = '';

    public bool $showModal = false;

    public ?User $editing = null;

    public string $name = '';

    public ?string $email = null;

    public ?string $nis = null;

    public ?string $no_kk = null;

    public ?string $phone = null;

    public ?string $password = null;

    public ?string $role = null;
    public array $lembaga_ids = [];
    public array $rayon_ids = [];

    public ?Keluarga $keluargaDitemukan = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRole(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editing = null;
        $this->reset(['name', 'email', 'nis', 'no_kk', 'phone', 'password', 'role', 'lembaga_ids', 'rayon_ids', 'keluargaDitemukan']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);
        $this->editing = $user;
        $this->fill($user->only(['name', 'email', 'nis', 'no_kk', 'phone']));
        $this->password = null;
        $this->role = $user->roles->first()?->name;
        $this->lembaga_ids = $user->lembagasDikelola()->pluck('lembagas.id')->map(fn ($id) => (string) $id)->all();
        $this->rayon_ids = $user->rayonsDikelola()->pluck('rayons.id')->map(fn ($id) => (string) $id)->all();
        $this->keluargaDitemukan = $this->no_kk ? Keluarga::where('no_kk', $this->no_kk)->first() : null;
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Purely informational for the wali role - this form never creates a
     * Keluarga, it just shows which family (and santri) this No. KK will
     * auto-link to once saved, instead of no_kk being a blind text field.
     */
    public function updatedNoKk(): void
    {
        $this->keluargaDitemukan = $this->no_kk && preg_match('/^\d{16}$/', $this->no_kk)
            ? Keluarga::where('no_kk', $this->no_kk)->first()
            : null;
    }

    public function save(KeluargaLinkingService $linking): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($this->editing?->id)],
            'nis' => ['nullable', 'string', Rule::unique('users', 'nis')->ignore($this->editing?->id)],
            'no_kk' => ['nullable', 'digits:16'],
            'phone' => ['nullable', 'string'],
            'password' => [$this->editing ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(self::ROLE_FORM)],
            'lembaga_ids' => [$this->role === 'admin_lembaga' ? 'required' : 'nullable', 'array'],
            'lembaga_ids.*' => ['exists:lembagas,id'],
            'rayon_ids' => [$this->role === 'admin_rayon' ? 'required' : 'nullable', 'array'],
            'rayon_ids.*' => ['exists:rayons,id'],
        ]);

        $payload = collect($data)->except(['password', 'role', 'lembaga_ids', 'rayon_ids'])->all();

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        if ($this->editing) {
            $this->editing->update($payload);
            $user = $this->editing;
        } else {
            $user = User::create($payload);
        }

        $user->syncRoles([$data['role']]);
        $pivot = ['akses' => 'kelola', 'aktif' => true, 'ditugaskan_oleh' => auth()->id(), 'ditugaskan_at' => now()];
        $user->lembagasDikelola()->sync($data['role'] === 'admin_lembaga'
            ? collect($data['lembaga_ids'])->mapWithKeys(fn ($id) => [$id => $pivot])->all() : []);
        $user->rayonsDikelola()->sync($data['role'] === 'admin_rayon'
            ? collect($data['rayon_ids'])->mapWithKeys(fn ($id) => [$id => $pivot])->all() : []);

        // The auto-link sync that fires from UserObserver::created() runs
        // before the role is assigned above (assignRole/syncRoles always
        // happens after the model exists), so a brand new wali would never
        // get linked to santri that already exist. Re-run it now that the
        // role is actually in place - safe to call anytime, it just
        // recomputes the auto-generated links.
        $linking->syncForUser($user->fresh());

        $this->showModal = false;
        session()->flash('status', 'Pengguna berhasil disimpan.');
    }

    /**
     * Same precedent as password reset: admin doesn't set a new PIN value
     * (that's the wali's own secret), just clears it - the mobile app sees
     * has_pin=false and prompts the wali to set a fresh one before their
     * next sensitive action.
     */
    public function resetPin(int $id): void
    {
        $user = User::findOrFail($id);
        $user->update(['pin' => null, 'pin_set_at' => null]);

        // User::getActivitylogOptions() deliberately excludes `pin` from
        // the normal dirty-attribute log (a hash diff isn't useful and
        // there's no reason to write hashes into the audit log at all),
        // so a reset needs its own explicit entry to be auditable at all -
        // "who reset whose PIN and when" is exactly the kind of admin
        // action worth a durable record.
        activity('auth')->causedBy(auth()->user())->performedOn($user)->log('PIN transaksi direset oleh admin');

        if ($this->editing?->id === $id) {
            $this->editing = $this->editing->fresh();
        }

        session()->flash('status', 'PIN transaksi berhasil direset.');
    }

    public function render()
    {
        $query = User::query();
        $users = (clone $query)
            ->with(['roles', 'lembagasDikelola:id,nama', 'rayonsDikelola:id,nama'])
            ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('nis', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")))
            ->when($this->filterRole !== '', fn ($q) => $q->role($this->filterRole))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.users.index', [
            'title' => 'Pengguna',
            'users' => $users,
            'totalPengguna' => (clone $query)->count(),
            'totalStaf' => (clone $query)->whereHas('roles', fn ($q) => $q->whereNotIn('name', ['wali', 'santri']))->count(),
            // whereHas tetap aman pada test/instalasi awal ketika role wali
            // belum diseed; scope role() akan melempar RoleDoesNotExist.
            'totalWali' => (clone $query)->whereHas('roles', fn ($q) => $q->where('name', 'wali'))->count(),
            'totalAkunUnit' => (clone $query)->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin_lembaga', 'admin_rayon']))->count(),
            'roles' => Role::query()
                ->whereIn('name', self::ROLE_FORM)
                ->orderBy('name')
                ->pluck('name'),
            'lembagas' => Lembaga::where('is_active', true)->orderBy('nama')->get(),
            'rayons' => Rayon::where('is_active', true)->orderBy('nama')->get(),
        ]);
    }
}
