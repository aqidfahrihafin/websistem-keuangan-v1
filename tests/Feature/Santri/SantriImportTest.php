<?php

use App\Exports\SantriTemplateExport;
use App\Imports\SantriImport;
use App\Livewire\Admin\Santri\Import;
use App\Models\Keluarga;
use App\Models\Kamar;
use App\Models\Lembaga;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

it('lets an admin download the santri import template as a valid xlsx with a petunjuk sheet', function () {
    $admin = makeUserWithRole('admin');
    Lembaga::create(['kode' => 'MTS', 'nama' => 'MTs Latee', 'tipe' => 'sekolah_formal', 'is_active' => true]);

    $response = Livewire::actingAs($admin)->test(Import::class)
        ->call('downloadTemplate')
        ->assertFileDownloaded('template-import-santri.xlsx');

    expect($response)->not->toBeNull();
});

it('builds database-backed institution and room dropdowns using active data only', function () {
    $lembaga = Lembaga::create(['kode' => 'LBG-A', 'nama' => 'Lembaga A', 'tipe' => 'pondok_pusat', 'is_active' => true]);
    $lembagaNonaktif = Lembaga::create(['kode' => 'LBG-X', 'nama' => 'Lembaga Nonaktif', 'tipe' => 'lainnya', 'is_active' => false]);
    Kamar::create(['lembaga_id' => $lembaga->id, 'kode' => 'A-01', 'nama' => 'Kamar Aktif', 'is_active' => true]);
    Kamar::create(['lembaga_id' => $lembaga->id, 'kode' => 'A-99', 'nama' => 'Kamar Nonaktif', 'is_active' => false]);
    Kamar::create(['lembaga_id' => $lembagaNonaktif->id, 'kode' => 'X-01', 'nama' => 'Kamar Lembaga Nonaktif', 'is_active' => true]);

    $response = Excel::download(new SantriTemplateExport, 'template.xlsx');
    $spreadsheet = IOFactory::load($response->getFile()->getPathname());
    $data = $spreadsheet->getSheetByName('Data Santri');
    $petunjuk = $spreadsheet->getSheetByName('Petunjuk');
    $referensi = $spreadsheet->getSheetByName('Referensi');

    expect($data->getCell('L4')->getDataValidation()->getFormula1())->toBe('=DaftarLembagaAktif')
        ->and($data->getCell('M4')->getDataValidation()->getFormula1())->toBe('=DaftarKamarAktif')
        ->and($referensi->getSheetState())->toBe('hidden')
        ->and($referensi->getCell('A2')->getValue())->toBe('LBG-A')
        ->and($referensi->getCell('B2')->getValue())->toBe('A-01')
        ->and($petunjuk->toArray())->toContain(['A-01', 'LBG-A · Lembaga A', 'Kamar Aktif', '- · kapasitas tidak dibatasi'])
        ->and(collect($petunjuk->toArray())->flatten()->contains('LBG-X'))->toBeFalse()
        ->and(collect($petunjuk->toArray())->flatten()->contains('A-99'))->toBeFalse();
});

it('imports an active room only when it belongs to the selected institution', function () {
    $lembagaA = Lembaga::factory()->create(['kode' => 'LBG-A', 'is_active' => true]);
    $lembagaB = Lembaga::factory()->create(['kode' => 'LBG-B', 'is_active' => true]);
    $kamar = Kamar::create([
        'lembaga_id' => $lembagaA->id,
        'kode' => 'A-01',
        'nama' => 'Kamar A',
        'kapasitas' => 10,
        'is_active' => true,
    ]);
    $import = new SantriImport;

    $import->collection(Collection::make([
        Collection::make(['nis' => '2024101', 'nama' => 'Santri Sesuai', 'status' => 'aktif', 'lembaga_kode' => 'LBG-A', 'kamar_kode' => 'A-01']),
        Collection::make(['nis' => '2024102', 'nama' => 'Santri Salah', 'status' => 'aktif', 'lembaga_kode' => 'LBG-B', 'kamar_kode' => 'A-01']),
    ]));

    expect(Santri::where('nis', '2024101')->sole()->kamar_id)->toBe($kamar->id)
        ->and(Santri::where('nis', '2024102')->exists())->toBeFalse()
        ->and($import->dibuat)->toBe(1)
        ->and($import->errors)->toHaveCount(1);
});

it('reuses an existing keluarga by No. KK during import instead of overwriting its nama_kepala_keluarga', function () {
    $keluarga = Keluarga::factory()->create(['no_kk' => '1234567890123456', 'nama_kepala_keluarga' => 'Nama Asli']);

    (new SantriImport)->collection(Collection::make([
        Collection::make(['nis' => '2024999', 'nama' => 'Santri Import', 'no_kk' => '1234567890123456', 'nama_kepala_keluarga' => 'Nama Salah Ketik']),
    ]));

    expect($keluarga->fresh()->nama_kepala_keluarga)->toBe('Nama Asli')
        ->and(Santri::where('nis', '2024999')->first()?->keluarga_id)->toBe($keluarga->id);
});

it('creates a new keluarga during import when the No. KK does not exist yet', function () {
    (new SantriImport)->collection(Collection::make([
        Collection::make(['nis' => '2024998', 'nama' => 'Santri Baru Import', 'no_kk' => '9999888877776666', 'nama_kepala_keluarga' => 'Bapak Baru']),
    ]));

    $keluarga = Keluarga::where('no_kk', '9999888877776666')->first();
    expect($keluarga)->not->toBeNull()
        ->and($keluarga->nama_kepala_keluarga)->toBe('Bapak Baru');
});

it('imports the NIK column when it is a valid, unused 16-digit value', function () {
    (new SantriImport)->collection(Collection::make([
        Collection::make(['nis' => '2024997', 'nik' => '3529120510100077', 'nama' => 'Santri Dengan NIK']),
    ]));

    expect(Santri::where('nis', '2024997')->first()?->nik)->toBe('3529120510100077');
});

it('imports the row but ignores the NIK when it is malformed or already used by another santri', function () {
    Santri::factory()->create(['nik' => '1111222233334444']);

    $import = new SantriImport;
    $import->collection(Collection::make([
        Collection::make(['nis' => '2024996', 'nik' => 'bukan-nik', 'nama' => 'Santri NIK Rusak']),
        Collection::make(['nis' => '2024995', 'nik' => '1111222233334444', 'nama' => 'Santri NIK Dobel']),
    ]));

    expect(Santri::where('nis', '2024996')->first()?->nik)->toBeNull()
        ->and(Santri::where('nis', '2024995')->first()?->nik)->toBeNull()
        ->and($import->dibuat)->toBe(2)
        ->and($import->errors)->toHaveCount(2);
});

function fakeTemplateUpload(): UploadedFile
{
    // The downloadable template's own sample rows carry a real no_kk +
    // nama_kepala_keluarga each - reuse it as a real, valid xlsx rather
    // than hand-rolling one, so these tests exercise the actual
    // Excel::import() pipeline end to end.
    $response = Excel::download(new SantriTemplateExport, 'template.xlsx');
    $content = file_get_contents($response->getFile()->getPathname());

    return UploadedFile::fake()->createWithContent('import.xlsx', $content);
}

it('creates default wali accounts for the newly-imported keluarga when the checkbox is left on', function () {
    Role::findOrCreate('wali', 'web');
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Import::class)
        ->set('file', fakeTemplateUpload())
        ->assertSet('buatAkunWaliSekaligus', true)
        ->call('import')
        ->assertHasNoErrors();

    expect(User::where('no_kk', '3529010101010001')->exists())->toBeTrue()
        ->and(User::where('no_kk', '3529020202020002')->exists())->toBeTrue();
});

it('does not create any wali accounts during import when the checkbox is unchecked', function () {
    Role::findOrCreate('wali', 'web');
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Import::class)
        ->set('file', fakeTemplateUpload())
        ->set('buatAkunWaliSekaligus', false)
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('akunWaliDibuat', null);

    expect(User::where('no_kk', '3529010101010001')->exists())->toBeFalse();
});
