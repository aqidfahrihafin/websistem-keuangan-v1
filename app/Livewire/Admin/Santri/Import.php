<?php

namespace App\Livewire\Admin\Santri;

use App\Exports\SantriTemplateExport;
use App\Imports\SantriImport;
use App\Services\WaliAccountService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts::app')]
class Import extends Component
{
    use WithFileUploads;

    public $file;

    public bool $buatAkunWaliSekaligus = true;

    public ?int $dibuat = null;

    public ?int $dilewati = null;

    public array $errors_list = [];

    public ?int $akunWaliDibuat = null;

    public function import(WaliAccountService $waliAccounts): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        $importer = new SantriImport(Auth::user());
        Excel::import($importer, $this->file->getRealPath());

        $this->dibuat = $importer->dibuat;
        $this->dilewati = $importer->dilewati;
        $this->errors_list = $importer->errors;
        $this->file = null;

        $this->akunWaliDibuat = null;

        if ($this->buatAkunWaliSekaligus) {
            $dibuatWali = $waliAccounts->buatAkunMassalUntukSemua();
            $this->akunWaliDibuat = $dibuatWali->count();

            if ($dibuatWali->isNotEmpty()) {
                // Reused by KeluargaController::unduhAkunWaliBaru() - same
                // session key & route as the manual "Buat Akun Wali untuk
                // Semua yang Belum Ada" bulk action on the Keluarga page.
                session(['akun_wali_baru_pdf' => $dibuatWali->map(fn (array $r) => [$r['nama_kepala_keluarga'], $r['no_kk'], (string) $r['jumlah_santri']])->all()]);
            }
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new SantriTemplateExport, 'template-import-santri.xlsx');
    }

    public function render()
    {
        return view('livewire.admin.santri.import', ['title' => 'Import Data Santri']);
    }
}
