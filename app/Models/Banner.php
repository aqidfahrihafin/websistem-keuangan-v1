<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['judul', 'gambar_path', 'link_url', 'aktif', 'urutan'])]
class Banner extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function gambarUrl(): string
    {
        return asset('storage/'.$this->gambar_path);
    }

    protected static function booted(): void
    {
        // Centralized here (rather than left to whichever admin action
        // triggers a delete) so the uploaded file can never be orphaned in
        // storage regardless of where Banner::destroy()/delete() is called
        // from.
        static::deleting(function (self $banner) {
            Storage::disk('public')->delete($banner->gambar_path);
        });
    }
}
