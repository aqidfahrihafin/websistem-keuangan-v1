<?php

use App\Models\MutasiKas;
use App\Models\Device;
use App\Models\JenisTagihan;
use App\Models\KartuSantri;
use App\Models\Tagihan;
use App\Models\Santri;
use App\Models\SesiKas;
use App\Models\Transaksi;
use App\Models\TransaksiTabungan;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\SesiKasService;
use App\Services\TabunganService;
use App\Services\WalletService;
use App\Services\PushNotificationService;
use App\Services\LaporanKeuanganService;
use App\Services\LegerKasPondokService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use App\Livewire\PetugasKios\Dashboard as DashboardPetugasKios;
use App\Livewire\Kios\Tabungan as KiosTabungan;

function bukaSesiKasUji(User $petugas, int $saldoAwal = 0): SesiKas
{
    $device = Device::factory()->create([
        'tipe' => Device::TIPE_KIOSK_PENARIKAN,
        'status' => 'aktif',
    ]);
    $device->petugasTerdaftar()->attach($petugas->id, [
        'aktif' => true,
        'ditugaskan_at' => now(),
    ]);

    return app(SesiKasService::class)->buka($petugas, 'Kios Utama', $saldoAwal, $device);
}

it('menormalisasi foreign key sesi kas dari driver mysql menjadi integer', function () {
    $sesi = new SesiKas();
    $sesi->setRawAttributes([
        'petugas_id' => '17',
        'device_id' => '23',
        'diverifikasi_oleh' => '31',
    ]);

    $device = new Device();
    $device->setRawAttributes([
        'petugas_jaga_id' => '17',
        'sesi_kas_aktif_id' => '41',
    ]);

    expect($sesi->petugas_id)->toBe(17)
        ->and($sesi->device_id)->toBe(23)
        ->and($sesi->diverifikasi_oleh)->toBe(31)
        ->and($device->petugas_jaga_id)->toBe(17)
        ->and($device->sesi_kas_aktif_id)->toBe(41);
});

it('mencatat setoran tunai ke tabungan dan kas dalam satu transaksi', function () {
    $petugas = makeUserWithRole('petugas_kios');
    $santri = Santri::factory()->create();
    $sesi = bukaSesiKasUji($petugas, 100000);

    $transaksi = app(TabunganService::class)->setorTunai(
        $santri,
        50000,
        $sesi,
        $petugas,
        'uji-setor-1',
    );

    expect($transaksi->saldo_sebelum)->toBe(0)
        ->and($transaksi->saldo_sesudah)->toBe(50000)
        ->and($santri->rekeningTabungan->saldo)->toBe(50000)
        ->and($sesi->fresh()->total_masuk)->toBe(50000)
        ->and($sesi->fresh()->saldo_seharusnya)->toBe(150000)
        ->and(MutasiKas::count())->toBe(1);
});

it('mengirim notifikasi setoran tabungan tunai tanpa id transaksi saldo yang keliru', function () {
    $petugas = makeUserWithRole('petugas_kios');
    $wali = makeUserWithRole('wali');
    $santri = Santri::factory()->create();
    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);
    $sesi = bukaSesiKasUji($petugas);

    $push = $this->mock(PushNotificationService::class);
    $push->shouldReceive('notify')
        ->once()
        ->with(
            Mockery::on(fn ($u) => $u->is($wali)),
            'Setoran Tabungan Berhasil',
            Mockery::type('string'),
            Mockery::on(fn ($data) => $data['type'] === 'setoran_tabungan_tunai'
                && isset($data['tabungan_transaksi_id'])
                && ! isset($data['transaksi_id'])),
        );

    app(TabunganService::class)->setorTunai($santri, 25000, $sesi, $petugas, 'notif-tabungan');
});

it('tidak menggandakan setoran ketika permintaan yang sama dikirim ulang', function () {
    $petugas = makeUserWithRole('petugas_kios');
    $santri = Santri::factory()->create();
    $sesi = bukaSesiKasUji($petugas);
    $service = app(TabunganService::class);

    $pertama = $service->setorTunai($santri, 25000, $sesi, $petugas, 'permintaan-sama');
    $kedua = $service->setorTunai($santri, 25000, $sesi, $petugas, 'permintaan-sama');

    expect($kedua->id)->toBe($pertama->id)
        ->and($santri->rekeningTabungan->fresh()->saldo)->toBe(25000)
        ->and(TransaksiTabungan::count())->toBe(1)
        ->and(MutasiKas::count())->toBe(1);
});

it('memindahkan saldo ke tabungan tanpa mencampurkan kedua ledger', function () {
    $wali = makeUserWithRole('wali');
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);

    $tabungan = app(TabunganService::class)->pindahDariSaldo(
        $santri,
        100000,
        $wali,
        TransaksiTabungan::KANAL_WALI,
        'transfer-tabungan-1',
    );

    expect($santri->saldo->fresh()->saldo)->toBe(200000)
        ->and($tabungan->saldo_sesudah)->toBe(100000)
        ->and($santri->rekeningTabungan->saldo)->toBe(100000)
        ->and(Transaksi::where('jenis', Transaksi::JENIS_TRANSFER_KE_TABUNGAN)->count())->toBe(1);
});

it('memindahkan saldo ke tabungan melalui kios setelah kartu dan sidik jari terverifikasi', function () {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create([
        'santri_id' => $santri->id,
        'uid_kartu' => 'UID-TABUNGAN-KIOS',
        'fingerprint_template_ref' => 'FP-TABUNGAN-KIOS',
    ]);
    $device = Device::factory()->create([
        'tipe' => Device::TIPE_KIOSK_SALDO,
        'status' => 'aktif',
    ]);

    Livewire::test(KiosTabungan::class, ['device' => $device])
        ->set('uid', 'UID-TABUNGAN-KIOS')
        ->assertSet('langkah', 'nominal')
        ->set('nominal', 100000)
        ->call('lanjutSidikJari')
        ->assertSet('langkah', 'sidik_jari')
        ->set('fingerprint_ref', 'FP-TABUNGAN-KIOS')
        ->assertSet('langkah', 'selesai');

    expect($santri->saldo->fresh()->saldo)->toBe(200000)
        ->and($santri->rekeningTabungan->saldo)->toBe(100000)
        ->and(TransaksiTabungan::where('kanal', TransaksiTabungan::KANAL_KIOS)->count())->toBe(1)
        ->and(TransaksiTabungan::first()->device_id)->toBe($device->id)
        ->and(MutasiKas::count())->toBe(0);
});

it('menahan transfer yang melanggar saldo minimum', function () {
    $wali = makeUserWithRole('wali');
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 150000, Transaksi::JENIS_TOPUP_TUNAI);

    expect(fn () => app(TabunganService::class)->pindahDariSaldo(
        $santri,
        60000,
        $wali,
        TransaksiTabungan::KANAL_WALI,
        'transfer-ditolak',
    ))->toThrow(RuntimeException::class);

    expect($santri->saldo->fresh()->saldo)->toBe(150000)
        ->and($santri->rekeningTabungan)->toBeNull();
});

it('menghitung selisih saat sesi kas ditutup dan diverifikasi', function () {
    $petugas = makeUserWithRole('petugas_kios');
    $bendahara = makeUserWithRole('bendahara');
    $service = app(SesiKasService::class);
    $sesi = bukaSesiKasUji($petugas, 100000);

    $ditutup = $service->tutup($sesi, $petugas, 95000);
    $selesai = $service->verifikasi($ditutup, $bendahara);

    expect($ditutup->status)->toBe(SesiKas::STATUS_MENUNGGU_VERIFIKASI)
        ->and($ditutup->selisih)->toBe(-5000)
        ->and($selesai->status)->toBe(SesiKas::STATUS_SELISIH)
        ->and($selesai->diverifikasi_oleh)->toBe($bendahara->id);
});

it('hanya mengizinkan wali tertaut melihat dan memindahkan saldo ke tabungan', function () {
    $wali = makeUserWithRole('wali', ['pin' => Hash::make('123456')]);
    $santri = Santri::factory()->create();
    $santriLain = Santri::factory()->create();
    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/anak/{$santri->id}/tabungan")
        ->assertOk()
        ->assertJsonPath('saldo_tabungan', 0)
        ->assertJsonPath('saldo_bisa_dipindahkan', 200000);

    $this->getJson("/api/wali/anak/{$santriLain->id}/tabungan")->assertForbidden();

    $this->postJson("/api/wali/anak/{$santri->id}/tabungan/dari-saldo", [
        'nominal' => 50000,
        'pin' => '123456',
        'request_id' => 'api-tabungan-1',
    ])->assertCreated()
        ->assertJsonPath('saldo_tabungan', 50000);

    expect($santri->saldo->fresh()->saldo)->toBe(250000)
        ->and($santri->rekeningTabungan->saldo)->toBe(50000);
});

it('mengizinkan admin membuat akun dengan role petugas kios', function () {
    $admin = makeUserWithRole('admin');
    Role::findOrCreate('petugas_kios', 'web');

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Admin\Users\Index::class)
        ->call('openCreate')
        ->set('name', 'Petugas Kios Baru')
        ->set('email', 'petugas.baru@example.test')
        ->set('password', 'password123')
        ->set('role', 'petugas_kios')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(User::where('email', 'petugas.baru@example.test')->firstOrFail()->hasRole('petugas_kios'))
        ->toBeTrue();
});

it('memasukkan setoran tabungan ke laporan dan leger tanpa menghitung transfer internal sebagai kas baru', function () {
    $petugas = makeUserWithRole('petugas_kios');
    $wali = makeUserWithRole('wali');
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);

    $sesi = bukaSesiKasUji($petugas);
    app(TabunganService::class)->setorTunai($santri, 50000, $sesi, $petugas, 'laporan-tunai');
    app(TabunganService::class)->pindahDariSaldo(
        $santri,
        100000,
        $wali,
        TransaksiTabungan::KANAL_WALI,
        'laporan-transfer-internal',
    );

    $dari = Carbon::now()->startOfMonth();
    $sampai = Carbon::now()->endOfMonth();
    $laporan = app(LaporanKeuanganService::class)->generate($dari, $sampai);
    $leger = app(LegerKasPondokService::class)->generate($dari, $sampai);

    expect($laporan['saldo_tabungan_saat_ini'])->toBe(150000)
        ->and($laporan['transaksi']['total_kredit'])->toBe(350000)
        ->and($leger['total_masuk'])->toBe(350000)
        ->and($leger['saldo_tabungan_saat_ini'])->toBe(150000)
        ->and($leger['uang_milik_pondok'])->toBe(0)
        ->and(collect($leger['entri'])->where('jenis', 'Setoran Tunai Tabungan')->sum('masuk'))->toBe(50000);
});

it('tetap memakai sesi aktif ketika penunjuk perangkat tidak sinkron', function () {
    $petugas = makeUserWithRole('petugas_kios');
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $sesi = bukaSesiKasUji($petugas);
    $sesi->device->update(['sesi_kas_aktif_id' => null]);

    Livewire::actingAs($petugas)->test(DashboardPetugasKios::class)
        ->set('aksi', 'saldo')
        ->set('santriId', $santri->id)
        ->set('nominal', 50000)
        ->call('prosesTunai')
        ->assertHasNoErrors();

    expect($santri->fresh()->saldo->saldo)->toBe(50000)
        ->and($sesi->fresh()->total_masuk)->toBe(50000);
});

it('menolak petugas kedua ketika perangkat masih memiliki sesi aktif', function () {
    $petugasPertama = makeUserWithRole('petugas_kios');
    $petugasKedua = makeUserWithRole('petugas_kios');
    $device = Device::factory()->create(['status' => 'aktif']);
    $device->petugasTerdaftar()->attach([
        $petugasPertama->id => ['aktif' => true, 'ditugaskan_at' => now()],
        $petugasKedua->id => ['aktif' => true, 'ditugaskan_at' => now()],
    ]);

    $sesiPertama = app(SesiKasService::class)->buka($petugasPertama, 'Kios A', 0, $device);

    expect(fn () => app(SesiKasService::class)->buka($petugasKedua, 'Kios A', 0, $device))
        ->toThrow(RuntimeException::class, 'Perangkat masih memiliki sesi aktif milik')
        ->and(SesiKas::where('device_id', $device->id)->where('status', SesiKas::STATUS_AKTIF)->count())->toBe(1)
        ->and($device->fresh()->sesi_kas_aktif_id)->toBe($sesiPertama->id);
});

it('menolak petugas membuka sesi baru sebelum penutupan sebelumnya diverifikasi', function () {
    $petugas = makeUserWithRole('petugas_kios');
    $device = Device::factory()->create(['status' => 'aktif']);
    $device->petugasTerdaftar()->attach($petugas->id, [
        'aktif' => true,
        'ditugaskan_at' => now(),
    ]);
    $service = app(SesiKasService::class);
    $sesi = $service->buka($petugas, 'Kios A', 100000, $device);

    $service->tutup($sesi, $petugas, 100000);

    expect(fn () => $service->buka($petugas, 'Kios A', 100000, $device))
        ->toThrow(RuntimeException::class, 'masih menunggu verifikasi admin')
        ->and($sesi->fresh()->status)->toBe(SesiKas::STATUS_MENUNGGU_VERIFIKASI);
});

it('menonaktifkan seluruh ruang kerja petugas yang belum tertaut perangkat', function () {
    $petugas = makeUserWithRole('petugas_kios');
    $device = Device::factory()->create(['status' => 'aktif']);

    Livewire::actingAs($petugas)->test(DashboardPetugasKios::class)
        ->assertSee('Belum ditugaskan ke perangkat kios')
        ->assertDontSee('Buka Sesi')
        ->set('deviceId', $device->id)
        ->call('bukaKas')
        ->assertHasErrors('sesi');

    expect(SesiKas::where('petugas_id', $petugas->id)->exists())->toBeFalse();
});

it('mencari santri dan menampilkan konfirmasi sebelum transaksi tunai', function () {
    $petugas = makeUserWithRole('petugas_kios');
    $santriDicari = Santri::factory()->create([
        'nama' => 'Ahmad Santri Dicari',
        'nis' => 'NIS-CARI-001',
        'status' => Santri::STATUS_AKTIF,
    ]);
    Santri::factory()->create([
        'nama' => 'Santri Lain',
        'nis' => 'NIS-LAIN-001',
        'status' => Santri::STATUS_AKTIF,
    ]);
    bukaSesiKasUji($petugas);

    Livewire::actingAs($petugas)->test(DashboardPetugasKios::class)
        ->set('santriSearch', 'NIS-CARI')
        ->assertSee('Ahmad Santri Dicari')
        ->assertDontSee('Santri Lain')
        ->set('santriId', $santriDicari->id)
        ->set('nominal', 50000)
        ->assertSee('Tinjau &amp; Proses', false)
        ->assertSee('Konfirmasi Setor Saldo Tunai');
});

it('mempertahankan halaman operasional kios sesuai rute yang dibuka', function () {
    $petugas = makeUserWithRole('petugas_kios');
    bukaSesiKasUji($petugas);

    $this->actingAs($petugas)
        ->get(route('petugas-kios.transaksi'))
        ->assertOk()
        ->assertSee('Catat transaksi tunai')
        ->assertDontSee('Menu cepat');

    $this->get(route('petugas-kios.tutup-sesi'))
        ->assertOk()
        ->assertSee('Hasil hitung uang fisik')
        ->assertSee('Uang fisik yang akan dilaporkan')
        ->assertSee('Kas sistem:')
        ->assertDontSee('Menu cepat');

    $this->get(route('petugas-kios.mutasi'))
        ->assertOk()
        ->assertSee('Riwayat Mutasi Kas Sesi')
        ->assertDontSee('Menu cepat');
});

it('mencatat opsi cepat setor saldo dan tabungan ke sesi perangkat yang sama', function () {
    $petugas = makeUserWithRole('petugas_kios');
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $sesi = bukaSesiKasUji($petugas);

    Livewire::actingAs($petugas)->test(DashboardPetugasKios::class)
        ->set('aksi', 'saldo')
        ->set('santriId', $santri->id)
        ->set('nominal', 40000)
        ->call('prosesTunai')
        ->assertHasNoErrors()
        ->set('aksi', 'tabungan')
        ->set('santriId', $santri->id)
        ->set('nominal', 25000)
        ->call('prosesTunai')
        ->assertHasNoErrors();

    expect($santri->saldo->saldo)->toBe(40000)
        ->and($santri->rekeningTabungan->saldo)->toBe(25000)
        ->and($sesi->fresh()->total_masuk)->toBe(65000)
        ->and($sesi->mutasi()->count())->toBe(2);
});

it('mencatat pembayaran tagihan tunai dari dashboard ke kas sesi', function () {
    $petugas = makeUserWithRole('petugas_kios');
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $jenis = JenisTagihan::factory()->create(['bisa_dicicil' => true]);
    $tagihan = Tagihan::create([
        'santri_id' => $santri->id,
        'jenis_tagihan_id' => $jenis->id,
        'periode_label' => 'Juli 2026',
        'nominal' => 100000,
        'nominal_terbayar' => 0,
        'status' => Tagihan::STATUS_BELUM_LUNAS,
        'jatuh_tempo' => now()->addDays(7),
        'generated_by' => $petugas->id,
    ]);
    $sesi = bukaSesiKasUji($petugas);

    Livewire::actingAs($petugas)->test(DashboardPetugasKios::class)
        ->set('aksi', 'tagihan')
        ->set('santriId', $santri->id)
        ->set('tagihanId', $tagihan->id)
        ->set('nominal', 60000)
        ->call('prosesTunai')
        ->assertHasNoErrors();

    expect($tagihan->fresh()->nominal_terbayar)->toBe(60000)
        ->and($tagihan->fresh()->status)->toBe(Tagihan::STATUS_SEBAGIAN)
        ->and($sesi->fresh()->total_masuk)->toBe(60000)
        ->and($sesi->mutasi()->where('kategori', 'pembayaran_tagihan')->count())->toBe(1);
});
