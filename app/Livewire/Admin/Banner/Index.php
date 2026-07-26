<?php

namespace App\Livewire\Admin\Banner;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Banner;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithFileUploads, WithPagination, WithPerPage;

    public string $search = '';

    public bool $showModal = false;

    /**
     * Plain id, not an Eloquent model property - Livewire re-fetches a
     * public model property by its primary key on every request. If that
     * row gets deleted (e.g. by this same component's hapus() action while
     * it was the one open for editing), the very next request's hydration
     * throws ModelNotFoundException. Looking it up fresh only when actually
     * needed (openEdit()/save()/render()) sidesteps that entirely.
     */
    public ?int $editingId = null;

    public string $judul = '';

    public ?string $link_url = null;

    public bool $aktif = true;

    public int $urutan = 0;

    /** Temporary upload - required on create, optional on edit (keeps the existing image if left empty). */
    public $gambar = null;

    // Rendered inside this component's own view (not session()->flash(),
    // which only becomes visible after a full page navigation - see
    // Pengaturan\Aplikasi's identical reasoning).
    public ?string $statusMessage = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->reset(['judul', 'link_url', 'gambar']);
        $this->aktif = true;
        // Pushes a new banner to the end of the carousel order by default -
        // still just a plain number the admin can change in the form.
        $this->urutan = ((int) Banner::max('urutan')) + 1;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $banner = Banner::findOrFail($id);
        $this->editingId = $id;
        $this->fill($banner->only(['judul', 'link_url', 'aktif', 'urutan']));
        $this->gambar = null;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $editing = $this->editingId ? Banner::find($this->editingId) : null;

        $data = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'aktif' => ['boolean'],
            'urutan' => ['required', 'integer', 'min:0'],
            'gambar' => [
                $editing ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        unset($data['gambar']);

        if ($this->gambar) {
            $lama = $editing?->gambar_path;
            $data['gambar_path'] = $this->gambar->store('banners', 'public');

            if ($lama !== null) {
                Storage::disk('public')->delete($lama);
            }
        }

        if ($editing) {
            $editing->update($data);
        } else {
            Banner::create($data);
        }

        $this->showModal = false;
        $this->editingId = null;
        $this->statusMessage = 'Banner berhasil disimpan.';
    }

    public function toggleActive(int $id): void
    {
        $banner = Banner::findOrFail($id);
        $banner->update(['aktif' => ! $banner->aktif]);
    }

    public function hapus(int $id): void
    {
        // File cleanup happens via Banner::booted()'s deleting hook.
        Banner::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->editingId = null;
        }

        $this->statusMessage = 'Banner dihapus.';
    }

    public function render()
    {
        $search = trim($this->search);

        return view('livewire.admin.banner.index', [
            'title' => 'Banner Beranda',
            'banners' => Banner::query()
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('judul', 'like', "%{$search}%")
                            ->orWhere('link_url', 'like', "%{$search}%");
                    });
                })
                ->orderBy('urutan')
                ->orderByDesc('id')
                ->paginate($this->perPage),
            'editingBanner' => $this->editingId ? Banner::find($this->editingId) : null,
        ]);
    }
}
