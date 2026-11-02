<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pengguna')) {
            Schema::create('pengguna', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama_depan', 60);
                $table->string('nama_belakang', 60)->nullable();
                $table->string('email', 120)->unique();
                $table->string('password_hash', 255);
                $table->enum('role', ['peserta', 'admin'])->default('peserta');
                $table->string('telepon', 20)->nullable();
                $table->enum('gender', ['Laki-laki', 'Perempuan']);
                $table->string('tempat_lahir', 80)->nullable();
                $table->date('tanggal_lahir');
                $table->text('alamat')->nullable();
                $table->dateTime('dibuat_pada')->useCurrent();
            });
        }

        if (! Schema::hasTable('lomba')) {
            Schema::create('lomba', function (Blueprint $table) {
                $table->tinyIncrements('id');
                $table->string('kode', 20)->unique();
                $table->string('nama', 60);
                $table->string('label_nomor', 10)->nullable();
                $table->string('label_asal', 40)->nullable();
                $table->dateTime('batas_daftar')->nullable();
                $table->boolean('butuh_word')->default(false);
            });
        }

        if (! Schema::hasTable('pendaftaran')) {
            Schema::create('pendaftaran', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('pengguna_id');
                $table->unsignedTinyInteger('lomba_id');
                $table->string('nama_peserta', 120);
                $table->enum('tipe_tim', ['Individu', 'Kelompok'])->default('Individu');
                $table->string('anggota2', 120)->nullable();
                $table->string('anggota3', 120)->nullable();
                $table->string('nomor_induk', 30);
                $table->string('asal', 150);
                $table->string('email', 120);
                $table->string('whatsapp', 20);
                $table->string('file_kartu', 255)->nullable();
                $table->enum('tahap', ['menunggu', 'terbuka', 'terkirim'])->default('terbuka');
                $table->string('status', 30)->default('Terdaftar');
                $table->string('catatan_admin', 255)->nullable();
                $table->dateTime('dibuat_pada')->useCurrent();

                $table->unique(['pengguna_id', 'lomba_id']);
                $table->foreign('pengguna_id')->references('id')->on('pengguna')->cascadeOnDelete();
                $table->foreign('lomba_id')->references('id')->on('lomba');
            });
        }

        if (! Schema::hasTable('karya')) {
            Schema::create('karya', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('pendaftaran_id')->unique();
                $table->string('judul', 200);
                $table->string('file_pdf', 255)->default('');
                $table->string('file_word', 255)->default('');
                $table->string('file_kartu', 255)->default('');
                $table->string('file_pernyataan', 255)->default('');
                $table->dateTime('dikirim_pada')->useCurrent();

                $table->foreign('pendaftaran_id')->references('id')->on('pendaftaran')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('notifikasi')) {
            Schema::create('notifikasi', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('pengguna_id');
                $table->unsignedTinyInteger('lomba_id')->nullable();
                $table->string('pesan', 255);
                $table->boolean('dibaca')->default(false);
                $table->dateTime('dibuat_pada')->useCurrent();

                $table->foreign('pengguna_id')->references('id')->on('pengguna')->cascadeOnDelete();
                $table->foreign('lomba_id')->references('id')->on('lomba')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('karya');
        Schema::dropIfExists('pendaftaran');
        Schema::dropIfExists('lomba');
        Schema::dropIfExists('pengguna');
    }
};
