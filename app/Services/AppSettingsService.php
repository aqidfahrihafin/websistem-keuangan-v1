<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * App branding (name, pondok name, address, contact, logo) can be set by
 * an admin from the settings page (stored encrypted in the settings table)
 * or fall back to sensible defaults - useful for first deploy before
 * anyone has visited the page.
 */
class AppSettingsService
{
    private const DEFAULT_NAMA_APLIKASI = 'Sistem Keuangan Santri';

    private const DEFAULT_NAMA_PONDOK = 'Pondok Pesantren Latee (Annuqayah)';

    public function namaAplikasi(): string
    {
        return Setting::get('app_nama_aplikasi') ?: self::DEFAULT_NAMA_APLIKASI;
    }

    public function namaPondok(): string
    {
        return Setting::get('app_nama_pondok') ?: self::DEFAULT_NAMA_PONDOK;
    }

    /**
     * Short initials derived from namaAplikasi(), e.g. "Sistem Keuangan
     * Santri" -> "SKS" - used where space is tight (sidebar header) so a
     * long app name never overflows or gets awkwardly clipped.
     */
    public function namaAplikasiInisial(): string
    {
        return self::inisialDari($this->namaAplikasi());
    }

    /** Short initials derived from namaPondok(), see namaAplikasiInisial(). */
    public function namaPondokInisial(): string
    {
        return self::inisialDari($this->namaPondok());
    }

    private static function inisialDari(string $nama): string
    {
        $kata = preg_split('/[\s()]+/u', trim($nama, "() \t\n\r"), -1, PREG_SPLIT_NO_EMPTY);

        $inisial = collect($kata)->map(fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

        return $inisial !== '' ? $inisial : mb_strtoupper(mb_substr($nama, 0, 1));
    }

    public function alamat(): ?string
    {
        return Setting::get('app_alamat');
    }

    public function telepon(): ?string
    {
        return Setting::get('app_telepon');
    }

    public function email(): ?string
    {
        return Setting::get('app_email');
    }

    public function save(string $namaAplikasi, string $namaPondok, ?string $alamat, ?string $telepon, ?string $email): void
    {
        Setting::put('app_nama_aplikasi', $namaAplikasi);
        Setting::put('app_nama_pondok', $namaPondok);
        Setting::put('app_alamat', $alamat);
        Setting::put('app_telepon', $telepon);
        Setting::put('app_email', $email);
    }

    public function logoPath(): ?string
    {
        return Setting::get('app_logo_path');
    }

    public function hasLogo(): bool
    {
        return $this->logoPath() !== null;
    }

    /** Public URL for `<img>` tags in regular (non-PDF) pages. */
    public function logoUrl(): ?string
    {
        $path = $this->logoPath();

        return $path !== null ? asset('storage/'.$path) : null;
    }

    /**
     * Base64 data URI for dompdf views - dompdf doesn't reliably fetch
     * remote/HTTP images, so PDF templates embed the file's bytes directly
     * instead of pointing at logoUrl() (same convention already used by
     * KartuCardService::batikTexture()).
     */
    public function logoBase64(): ?string
    {
        $path = $this->logoPath();

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path);
        $contents = Storage::disk('public')->get($path);

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    /**
     * Stores the new logo and deletes the previous file (if any) so
     * replacing a logo doesn't leave orphaned files behind in storage.
     */
    public function simpanLogo(UploadedFile $file): void
    {
        $lama = $this->logoPath();

        $path = $file->store('logo', 'public');
        Setting::put('app_logo_path', $path);

        if ($lama !== null) {
            Storage::disk('public')->delete($lama);
        }
    }

    public function hapusLogo(): void
    {
        $path = $this->logoPath();

        if ($path !== null) {
            Storage::disk('public')->delete($path);
            Setting::put('app_logo_path', null);
        }
    }
}
