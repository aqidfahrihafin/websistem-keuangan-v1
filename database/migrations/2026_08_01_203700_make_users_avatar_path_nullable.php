<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'avatar_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('avatar_path')->nullable()->default(null)->after('phone');
            });

            return;
        }

        // Beberapa database hosting sudah memiliki kolom ini dalam keadaan
        // NOT NULL. Foto profil bersifat opsional, sehingga akun baru harus
        // tetap dapat dibuat tanpa mengirim avatar_path.
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // Sengaja tidak mengembalikan kolom menjadi NOT NULL karena baris
        // pengguna yang tidak memiliki foto akan membuat rollback gagal.
    }
};
