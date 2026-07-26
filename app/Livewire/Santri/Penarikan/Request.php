<?php

namespace App\Livewire\Santri\Penarikan;

use App\Livewire\Concerns\WithPerPage;
use App\Models\PenarikanRequest as PenarikanRequestModel;
use App\Services\PenarikanService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Request extends Component
{
    use WithFileUploads, WithPagination, WithPerPage;

    public ?int $nominal = null;

    public $surat_file = null;

    public function ajukan(PenarikanService $service): void
    {
        $this->validate(['nominal' => ['required', 'integer', 'min:1']]);

        $santri = Auth::user()->santri;

        if (! $santri) {
            $this->addError('nominal', 'Akun Anda belum tertaut dengan data santri.');

            return;
        }

        $service->createRequest($santri, $this->nominal);

        session()->flash('status', 'Request penarikan tunai berhasil diajukan. Silakan tunggu verifikasi petugas.');
        $this->nominal = null;
    }

    public function unggahSurat(int $requestId, PenarikanService $service): void
    {
        $this->validate(['surat_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']]);

        $request = PenarikanRequestModel::where('santri_id', Auth::user()->santri?->id)->findOrFail($requestId);

        $path = $this->surat_file->store('surat-keterangan', 'local');

        $service->unggahSuratKeterangan($request, $path);

        $this->surat_file = null;
        session()->flash('status', 'Surat keterangan berhasil diunggah, menunggu review petugas.');
    }

    public function render(PenarikanService $service)
    {
        $santri = Auth::user()->santri;

        return view('livewire.santri.penarikan.request', [
            'title' => 'Request Penarikan Tunai',
            'requests' => $santri
                ? PenarikanRequestModel::where('santri_id', $santri->id)->whereIn('status', ['menunggu', 'disetujui'])->latest()->paginate($this->perPage)
                : new LengthAwarePaginator([], 0, $this->perPage),
            'limitInfo' => $santri ? $service->ringkasanLimitHarian($santri) : null,
        ]);
    }
}
