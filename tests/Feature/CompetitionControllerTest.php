<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CompetitionControllerTest extends TestCase
{
    #[DataProvider('registrationStates')]
    public function test_homepage_shows_the_correct_action_for_each_registration_state(
        string $category,
        bool $workSubmitted,
        string $expectedAction,
    ): void {
        $this->createHomepageTables();
        $this->createParticipant();
        $this->createRegistration($category, $workSubmitted);

        $this->withSession(['uid' => 1])->get('/')
            ->assertOk()
            ->assertSee($expectedAction)
            ->assertSee(
                $workSubmitted
                    ? route('profile', ['tab' => 'upload'])
                    : route('registration.show', $category),
                false,
            )
            ->assertSee('Lihat lomba yang dipilih')
            ->assertDontSee('Daftar Lomba '.($category === 'komik' ? 'Komik' : 'Buku Cerita'));
    }

    public static function registrationStates(): array
    {
        return [
            'comic registration needs completion' => [
                'komik',
                false,
                'Lanjutkan pendaftaran',
            ],
            'comic work was submitted' => [
                'komik',
                true,
                'Lihat hasil upload',
            ],
            'book story registration needs completion' => [
                'buku-cerita',
                false,
                'Lanjutkan pendaftaran',
            ],
            'book story work was submitted' => [
                'buku-cerita',
                true,
                'Lihat hasil upload',
            ],
        ];
    }

    private function createHomepageTables(): void
    {
        foreach (['notifikasi', 'karya', 'pendaftaran', 'lomba', 'pengguna'] as $tableName) {
            Schema::dropIfExists($tableName);
        }

        Schema::create('pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('role');
        });
        Schema::create('notifikasi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id');
            $table->boolean('dibaca')->default(false);
            $table->string('pesan')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
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
            $table->string('nama_peserta');
            $table->string('tipe_tim');
            $table->string('asal');
            $table->string('status');
            $table->string('tahap');
            $table->string('catatan_admin')->nullable();
            $table->string('file_kartu')->nullable();
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

        DB::table('lomba')->insert([
            ['id' => 1, 'kode' => 'buku-cerita', 'nama' => 'Lomba Buku Cerita HAM'],
            ['id' => 2, 'kode' => 'komik', 'nama' => 'Lomba Serial Komik Bergambar'],
        ]);
    }

    private function createParticipant(): void
    {
        DB::table('pengguna')->insert(['id' => 1, 'role' => 'peserta']);
    }

    private function createRegistration(string $category, bool $workSubmitted = false): void
    {
        $registrationId = 1;
        $competitionId = $category === 'komik' ? 2 : 1;
        DB::table('pendaftaran')->insert([
            'id' => $registrationId,
            'pengguna_id' => 1,
            'lomba_id' => $competitionId,
            'nama_peserta' => 'Peserta Uji',
            'tipe_tim' => 'Individu',
            'asal' => 'Sekolah',
            'status' => $workSubmitted ? 'Karya terkirim' : 'Terdaftar',
            'tahap' => $workSubmitted ? 'terkirim' : 'terbuka',
            'dibuat_pada' => '2026-11-02 13:34:00',
        ]);

        if ($workSubmitted) {
            DB::table('karya')->insert([
                'pendaftaran_id' => $registrationId,
                'judul' => 'Karya Uji',
                'file_pdf' => 'karya.pdf',
                'file_pernyataan' => 'pernyataan.pdf',
                'dikirim_pada' => '2026-11-02 13:35:00',
            ]);
        }
    }
}
