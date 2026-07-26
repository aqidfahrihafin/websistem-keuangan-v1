<?php

use App\Livewire\Admin\Santri\Index as SantriIndex;
use App\Models\JenisTagihan;
use App\Models\KategoriDiskon;
use App\Models\Keluarga;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Services\TagihanService;
use Livewire\Livewire;

function seedBersaudaraKategori(int $persentase = 10): KategoriDiskon
{
    return KategoriDiskon::factory()->create([
        'kode' => KategoriDiskon::KODE_BERSAUDARA,
        'nama' => 'Santri Bersaudara',
        'persentase' => $persentase,
    ]);
}

function seedSantriBaruKategori(int $persentase = 5): KategoriDiskon
{
    return KategoriDiskon::factory()->create([
        'kode' => KategoriDiskon::KODE_SANTRI_BARU,
        'nama' => 'Santri Baru',
        'persentase' => $persentase,
    ]);
}

it('auto-assigns the bersaudara kategori once a keluarga has two or more aktif santri', function () {
    seedBersaudaraKategori();
    $keluarga = Keluarga::factory()->create();

    $santriA = Santri::factory()->create(['keluarga_id' => $keluarga->id, 'status' => Santri::STATUS_AKTIF]);

    expect($santriA->fresh()->kategori_diskon_id)->toBeNull();

    $santriB = Santri::factory()->create(['keluarga_id' => $keluarga->id, 'status' => Santri::STATUS_AKTIF]);

    expect($santriA->fresh()->kategori_diskon_id)->not->toBeNull()
        ->and($santriA->fresh()->kategori_diskon_auto)->toBeTrue()
        ->and($santriB->fresh()->kategori_diskon_id)->not->toBeNull()
        ->and($santriB->fresh()->kategori_diskon_auto)->toBeTrue();
});

it('removes the auto-assigned bersaudara kategori when a sibling stops being aktif', function () {
    seedBersaudaraKategori();
    $keluarga = Keluarga::factory()->create();

    $santriA = Santri::factory()->create(['keluarga_id' => $keluarga->id, 'status' => Santri::STATUS_AKTIF]);
    $santriB = Santri::factory()->create(['keluarga_id' => $keluarga->id, 'status' => Santri::STATUS_AKTIF]);

    expect($santriA->fresh()->kategori_diskon_id)->not->toBeNull();

    $santriB->update(['status' => Santri::STATUS_KELUAR]);

    expect($santriA->fresh()->kategori_diskon_id)->toBeNull()
        ->and($santriA->fresh()->kategori_diskon_auto)->toBeFalse();
});

it('never overrides a manually-assigned kategori diskon with the auto bersaudara sync', function () {
    seedBersaudaraKategori();
    $manual = KategoriDiskon::factory()->create(['nama' => 'Anak Pengurus Pusat', 'persentase' => 20]);
    $keluarga = Keluarga::factory()->create();

    $santriA = Santri::factory()->create([
        'keluarga_id' => $keluarga->id,
        'status' => Santri::STATUS_AKTIF,
        'kategori_diskon_id' => $manual->id,
        'kategori_diskon_auto' => false,
    ]);

    // A second sibling joins - would normally trigger auto-assignment.
    Santri::factory()->create(['keluarga_id' => $keluarga->id, 'status' => Santri::STATUS_AKTIF]);

    expect($santriA->fresh()->kategori_diskon_id)->toBe($manual->id)
        ->and($santriA->fresh()->kategori_diskon_auto)->toBeFalse();
});

it('applies the kategori discount to nominal when the jenis tagihan honors it', function () {
    $kategori = KategoriDiskon::factory()->create(['persentase' => 10]);
    $santri = Santri::factory()->create(['kategori_diskon_id' => $kategori->id]);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 150000, 'berlaku_diskon' => true]);

    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::first();

    expect($tagihan->nominal)->toBe(135000)
        ->and($tagihan->nominal_sebelum_diskon)->toBe(150000)
        ->and($tagihan->diskon_persen)->toBe(10)
        ->and($tagihan->kategori_diskon_id)->toBe($kategori->id);
});

it('does not apply a discount when the jenis tagihan does not honor it, even if santri has a kategori', function () {
    $kategori = KategoriDiskon::factory()->create(['persentase' => 10]);
    $santri = Santri::factory()->create(['kategori_diskon_id' => $kategori->id]);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 150000, 'berlaku_diskon' => false]);

    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::first();

    expect($tagihan->nominal)->toBe(150000)
        ->and($tagihan->nominal_sebelum_diskon)->toBeNull()
        ->and($tagihan->diskon_persen)->toBeNull();
});

it('does not apply a discount from an inactive kategori', function () {
    $kategori = KategoriDiskon::factory()->create(['persentase' => 10, 'is_active' => false]);
    $santri = Santri::factory()->create(['kategori_diskon_id' => $kategori->id]);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 150000, 'berlaku_diskon' => true]);

    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::first();

    expect($tagihan->nominal)->toBe(150000);
});

it('snapshots the discount at generation time so later kategori changes do not retroactively alter issued tagihan', function () {
    $kategori = KategoriDiskon::factory()->create(['persentase' => 10]);
    $santri = Santri::factory()->create(['kategori_diskon_id' => $kategori->id]);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 150000, 'berlaku_diskon' => true]);

    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::first();
    expect($tagihan->nominal)->toBe(135000);

    $kategori->update(['persentase' => 50]);

    expect($tagihan->fresh()->nominal)->toBe(135000)
        ->and($tagihan->fresh()->diskon_persen)->toBe(10);
});

it('excludes santri with status baru (menunggu verifikasi) from tagihan generation', function () {
    Santri::factory()->create(['status' => Santri::STATUS_BARU]);
    Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    $result = app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07');

    expect($result['dibuat'])->toBe(1)
        ->and(Tagihan::count())->toBe(1);
});

it('excludes santri with status baru from the bersaudara sibling count', function () {
    seedBersaudaraKategori();
    $keluarga = Keluarga::factory()->create();

    Santri::factory()->create(['keluarga_id' => $keluarga->id, 'status' => Santri::STATUS_AKTIF]);
    $pending = Santri::factory()->create(['keluarga_id' => $keluarga->id, 'status' => Santri::STATUS_BARU]);

    expect($pending->fresh()->kategori_diskon_id)->toBeNull();
});

it('auto-assigns the santri baru kategori when a pending santri is verified/activated', function () {
    seedSantriBaruKategori();
    $santri = Santri::factory()->create(['status' => Santri::STATUS_BARU]);

    expect($santri->kategori_diskon_id)->toBeNull();

    $santri->update(['status' => Santri::STATUS_AKTIF]);

    expect($santri->fresh()->kategori_diskon_id)->not->toBeNull()
        ->and($santri->fresh()->kategoriDiskon->kode)->toBe(KategoriDiskon::KODE_SANTRI_BARU)
        ->and($santri->fresh()->kategori_diskon_auto)->toBeTrue();
});

it('does not override a manually-assigned kategori when verifying a pending santri', function () {
    seedSantriBaruKategori();
    $manual = KategoriDiskon::factory()->create(['nama' => 'Pengurus Pusat', 'persentase' => 20]);

    $santri = Santri::factory()->create([
        'status' => Santri::STATUS_BARU,
        'kategori_diskon_id' => $manual->id,
        'kategori_diskon_auto' => false,
    ]);

    $santri->update(['status' => Santri::STATUS_AKTIF]);

    expect($santri->fresh()->kategori_diskon_id)->toBe($manual->id)
        ->and($santri->fresh()->kategori_diskon_auto)->toBeFalse();
});

it('lets an admin verify a pending santri from the santri list via the Livewire action', function () {
    seedSantriBaruKategori();
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create(['status' => Santri::STATUS_BARU]);

    Livewire::actingAs($admin)
        ->test(SantriIndex::class)
        ->call('verifikasi', $santri->id)
        ->assertHasNoErrors();

    expect($santri->fresh()->status)->toBe(Santri::STATUS_AKTIF)
        ->and($santri->fresh()->kategori_diskon_id)->not->toBeNull();
});
