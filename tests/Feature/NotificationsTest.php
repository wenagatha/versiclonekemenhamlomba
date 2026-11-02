<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    public function test_notification_page_route_is_not_available(): void
    {
        $this->get('/notifikasi')->assertNotFound();
    }

    public function test_homepage_displays_participant_notifications(): void
    {
        $this->createNotificationTables();
        DB::table('pengguna')->insert(['id' => 1, 'role' => 'peserta']);
        foreach (range(1, 11) as $id) {
            DB::table('notifikasi')->insert([
                'id' => $id,
                'pengguna_id' => 1,
                'pesan' => $id === 1 ? 'Pendaftaran lomba berhasil.' : 'Notifikasi '.$id,
                'dibaca' => $id === 1 ? 0 : 1,
                'dibuat_pada' => '2026-11-02 13:34:00',
            ]);
        }

        $this->withSession(['uid' => 1])->get('/')
            ->assertOk()
            ->assertSee('Pendaftaran lomba berhasil.')
            ->assertSee('Tandai semua dibaca')
            ->assertSee('<dialog class="notification-dialog" id="notifikasi"', false)
            ->assertSee('id="notifications-trigger"', false)
            ->assertDontSee('class="notifications-section"', false);
    }

    public function test_marking_notifications_read_returns_to_home_and_updates_only_current_participant(): void
    {
        $this->createNotificationTables();
        DB::table('pengguna')->insert([
            ['id' => 1, 'role' => 'peserta'],
            ['id' => 2, 'role' => 'peserta'],
        ]);
        DB::table('notifikasi')->insert([
            ['pengguna_id' => 1, 'pesan' => 'Notifikasi peserta pertama.', 'dibaca' => 0, 'dibuat_pada' => '2026-11-02 13:34:00'],
            ['pengguna_id' => 2, 'pesan' => 'Notifikasi peserta kedua.', 'dibaca' => 0, 'dibuat_pada' => '2026-11-02 13:34:00'],
        ]);

        $this->withSession(['uid' => 1])->post('/notifikasi/baca')
            ->assertRedirect(route('home').'#notifikasi');

        $this->assertDatabaseHas('notifikasi', ['pengguna_id' => 1, 'dibaca' => 1]);
        $this->assertDatabaseHas('notifikasi', ['pengguna_id' => 2, 'dibaca' => 0]);
    }

    private function createNotificationTables(): void
    {
        foreach (['notifikasi', 'karya', 'pendaftaran', 'lomba', 'pengguna'] as $tableName) {
            Schema::dropIfExists($tableName);
        }

        Schema::create('pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('role');
        });
        Schema::create('lomba', function (Blueprint $table): void {
            $table->id();
            $table->string('kode');
            $table->string('nama');
        });
        Schema::create('pendaftaran', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id');
            $table->unsignedBigInteger('lomba_id');
            $table->timestamp('dibuat_pada')->nullable();
        });
        Schema::create('karya', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pendaftaran_id');
            $table->string('judul')->nullable();
            $table->string('file_pdf')->nullable();
            $table->string('file_word')->nullable();
            $table->string('file_pernyataan')->nullable();
            $table->string('file_kartu')->nullable();
            $table->timestamp('dikirim_pada')->nullable();
        });
        Schema::create('notifikasi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id');
            $table->string('pesan');
            $table->boolean('dibaca')->default(false);
            $table->timestamp('dibuat_pada')->nullable();
        });
    }
}
