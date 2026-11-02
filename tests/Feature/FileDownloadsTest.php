<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FileDownloadsTest extends TestCase
{
    private string $uploadDirectory;

    protected function tearDown(): void
    {
        if (isset($this->uploadDirectory)) {
            File::deleteDirectory($this->uploadDirectory);
        }

        parent::tearDown();
    }

    public function test_profile_shows_links_to_view_each_uploaded_file(): void
    {
        $this->createTables();
        $this->createRegistrationFiles(1, 1, true);

        $this->withSession(['uid' => 1])->get('/profil?tab=upload')
            ->assertOk()
            ->assertSee('class="registration-card"', false)
            ->assertSee('Tanggal mendaftar')
            ->assertSee('Berkas yang diunggah')
            ->assertSee('Lihat PDF Karya')
            ->assertSee('Lihat Naskah Word')
            ->assertSee('Lihat Kartu Peserta')
            ->assertSee('Lihat Surat Pernyataan')
            ->assertSee(route('files.download', [1, 'pdf']), false)
            ->assertSee(route('files.download-all'), false);
    }

    public function test_participant_can_open_an_uploaded_file_again(): void
    {
        $this->createTables();
        $files = $this->createRegistrationFiles(1, 1, true);

        $response = $this->withSession(['uid' => 1])->get(route('files.download', [1, 'pdf']));

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame(file_get_contents($this->uploadDirectory.DIRECTORY_SEPARATOR.$files['pdf']), $response->baseResponse->getFile()->getContent());
    }

    public function test_zip_contains_every_uploaded_file_for_the_current_participant(): void
    {
        $this->createTables();
        $firstRegistrationFiles = $this->createRegistrationFiles(1, 1, false);
        $secondRegistrationFiles = $this->createRegistrationFiles(2, 1, true);
        $otherParticipantFiles = $this->createRegistrationFiles(3, 2, true);

        $response = $this->withSession(['uid' => 1])->get(route('files.download-all'));

        $response->assertDownload('berkas-pendaftaran.zip');
        $archive = new \ZipArchive;
        $this->assertSame(true, $archive->open($response->baseResponse->getFile()->getPathname()));
        foreach ([
            '1-karya.pdf' => $firstRegistrationFiles['pdf'],
            '1-naskah.docx' => $firstRegistrationFiles['word'],
            '1-pernyataan.pdf' => $firstRegistrationFiles['pernyataan'],
            '1-kartu.png' => $firstRegistrationFiles['kartu'],
            '2-karya.pdf' => $secondRegistrationFiles['pdf'],
            '2-naskah.docx' => $secondRegistrationFiles['word'],
            '2-pernyataan.pdf' => $secondRegistrationFiles['pernyataan'],
            '2-kartu.png' => $secondRegistrationFiles['kartu'],
        ] as $archiveName => $fileName) {
            $this->assertSame(file_get_contents($this->uploadDirectory.DIRECTORY_SEPARATOR.$fileName), $archive->getFromName($archiveName));
        }
        $this->assertFalse($archive->locateName('3-karya.pdf') !== false);
        $archive->close();
    }

    public function test_participant_cannot_open_another_participants_uploaded_file(): void
    {
        $this->createTables();
        $this->createRegistrationFiles(1, 2, true);

        $this->withSession(['uid' => 1])->get(route('files.download', [1, 'pdf']))
            ->assertNotFound();
    }

    private function createTables(): void
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
            $table->string('nama_peserta');
            $table->string('tipe_tim');
            $table->string('asal');
            $table->string('status');
            $table->string('tahap');
            $table->string('file_kartu')->nullable();
            $table->string('catatan_admin')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
        });
        Schema::create('karya', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pendaftaran_id');
            $table->string('judul')->nullable();
            $table->string('file_pdf')->nullable();
            $table->string('file_word')->nullable();
            $table->string('file_kartu')->nullable();
            $table->string('file_pernyataan')->nullable();
            $table->timestamp('dikirim_pada')->nullable();
        });
        Schema::create('notifikasi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pengguna_id');
            $table->boolean('dibaca')->default(false);
        });

        DB::table('pengguna')->insert([
            ['id' => 1, 'role' => 'peserta'],
            ['id' => 2, 'role' => 'peserta'],
        ]);
        DB::table('lomba')->insert(['id' => 1, 'kode' => 'komik', 'nama' => 'Lomba Komik']);

        $this->uploadDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kemenham-file-tests-'.bin2hex(random_bytes(6));
        File::makeDirectory($this->uploadDirectory, 0700, true);
        config(['kemenham.upload_path' => $this->uploadDirectory]);
    }

    /**
     * @return array{pdf: string, word: string, pernyataan: string, kartu: string}
     */
    private function createRegistrationFiles(int $registrationId, int $userId, bool $cardOnRegistration): array
    {
        $files = [
            'pdf' => str_repeat('a', 32).'.pdf',
            'word' => str_repeat('b', 32).'.docx',
            'pernyataan' => str_repeat('c', 32).'.pdf',
            'kartu' => str_repeat('d', 32).'.png',
        ];
        foreach ($files as $type => $fileName) {
            file_put_contents(
                $this->uploadDirectory.DIRECTORY_SEPARATOR.$fileName,
                $type === 'pdf' || $type === 'pernyataan' ? "%PDF-1.4\n".$type : $type,
            );
        }

        DB::table('pendaftaran')->insert([
            'id' => $registrationId,
            'pengguna_id' => $userId,
            'lomba_id' => 1,
            'nama_peserta' => 'Peserta '.$registrationId,
            'tipe_tim' => 'Individu',
            'asal' => 'Sekolah',
            'status' => 'Karya terkirim',
            'tahap' => 'terkirim',
            'file_kartu' => $cardOnRegistration ? $files['kartu'] : '',
            'dibuat_pada' => '2026-11-02 13:34:00',
        ]);
        DB::table('karya')->insert([
            'pendaftaran_id' => $registrationId,
            'judul' => 'Karya '.$registrationId,
            'file_pdf' => $files['pdf'],
            'file_word' => $files['word'],
            'file_kartu' => $cardOnRegistration ? '' : $files['kartu'],
            'file_pernyataan' => $files['pernyataan'],
            'dikirim_pada' => '2026-11-02 13:35:00',
        ]);

        return $files;
    }
}
