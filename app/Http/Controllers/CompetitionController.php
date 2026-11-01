<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CompetitionController extends Controller
{
    public function home(): View
    {
        return view('pages.home', $this->sharedData());
    }

    public function registration(string $category): View|RedirectResponse
    {
        $competition = config('kemenham.competitions.'.$category);
        abort_unless($competition, 404);
        $user = $this->participant();
        if (! $user) {
            return redirect()->route('login', ['next' => route('registration.show', $category, false)]);
        }

        $lomba = DB::table('lomba')->where('kode', $category)->first();
        abort_unless($lomba, 404);
        $registration = DB::table('pendaftaran as p')
            ->leftJoin('karya as k', 'k.pendaftaran_id', '=', 'p.id')
            ->where('p.pengguna_id', $user->id)->where('p.lomba_id', $lomba->id)
            ->select('p.*', 'k.id as karya_id')
            ->first();

        return view('pages.registration', $this->sharedData() + compact('competition', 'category', 'registration', 'lomba'));
    }

    public function saveRegistration(Request $request, string $category): RedirectResponse
    {
        $competition = config('kemenham.competitions.'.$category);
        abort_unless($competition, 404);
        $user = $this->participant();
        if (! $user) {
            return redirect()->route('login');
        }

        $lomba = DB::table('lomba')->where('kode', $category)->first();
        abort_unless($lomba, 404);
        $existing = DB::table('pendaftaran')->where('pengguna_id', $user->id)->where('lomba_id', $lomba->id)->first();
        if ($existing && ($existing->status !== 'Terdaftar' || DB::table('karya')->where('pendaftaran_id', $existing->id)->exists())) {
            return back()->with('error', 'Data pendaftaran sudah dikunci setelah karya dikirim atau ditinjau.');
        }

        $rules = [
            'tim' => ['required', 'in:individu,kelompok'],
            'peserta' => ['required', 'string', 'max:120'],
            'anggota2' => ['nullable', 'string', 'max:120'],
            'anggota3' => ['nullable', 'string', 'max:120'],
            'nomor' => ['required', 'string', 'max:40'],
            'asal' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'wa' => ['required', 'string', 'max:20'],
            'setuju' => ['accepted'],
            'kartu' => [$existing && $existing->file_kartu ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
        $data = $request->validate($rules);
        $phone = preg_replace('/[\s-]/', '', $data['wa']);
        if (! preg_match('/^(\+62|62|0)8[0-9]{7,12}$/', $phone)) {
            return back()->withErrors(['wa' => 'Nomor WhatsApp tidak valid.'])->withInput();
        }

        if (! $existing) {
            if (DB::table('pendaftaran')->where('pengguna_id', $user->id)->exists()) {
                return back()->with('error', 'Akun ini sudah terdaftar pada lomba lain.');
            }
            if ($lomba->batas_daftar && now()->toDateString() > $lomba->batas_daftar) {
                return back()->with('error', 'Pendaftaran sudah ditutup.');
            }
        }

        $newFile = $request->hasFile('kartu') ? $this->storeFile($request->file('kartu')) : null;
        $oldFile = $existing->file_kartu ?? null;

        try {
            DB::transaction(function () use ($data, $user, $lomba, $existing, $newFile, $oldFile, $phone): void {
                $values = [
                    'nama_peserta' => trim($data['peserta']),
                    'tipe_tim' => $data['tim'] === 'kelompok' ? 'Kelompok' : 'Individu',
                    'anggota2' => $data['tim'] === 'kelompok' ? trim($data['anggota2'] ?? '') : '',
                    'anggota3' => $data['tim'] === 'kelompok' ? trim($data['anggota3'] ?? '') : '',
                    'nomor_induk' => trim($data['nomor']),
                    'asal' => trim($data['asal']),
                    'email' => mb_strtolower(trim($data['email'])),
                    'whatsapp' => $phone,
                    'file_kartu' => $newFile ?? $oldFile,
                ];

                if ($existing) {
                    DB::table('pendaftaran')->where('id', $existing->id)->update($values);

                    return;
                }

                $id = DB::table('pendaftaran')->insertGetId($values + [
                    'pengguna_id' => $user->id,
                    'lomba_id' => $lomba->id,
                    'tahap' => 'terbuka',
                    'status' => 'Terdaftar',
                    'dibuat_pada' => now(),
                ]);
                DB::table('notifikasi')->insert([
                    'pengguna_id' => $user->id,
                    'lomba_id' => $lomba->id,
                    'pesan' => 'Pendaftaran lomba '.$lomba->nama.' berhasil. Kamu bisa melanjutkan dengan mengunggah karya.',
                    'dibuat_pada' => now(),
                ]);
            });
        } catch (\Throwable $error) {
            if ($newFile) {
                File::delete($this->uploadPath($newFile));
            }
            throw $error;
        }

        if ($newFile && $oldFile) {
            File::delete($this->uploadPath($oldFile));
        }

        return redirect()->route('work.show', $category)->with('success', $existing ? 'Data pendaftaran berhasil diperbarui.' : 'Pendaftaran berhasil. Kamu bisa mengunggah karya sekarang.');
    }

    public function work(string $category): View|RedirectResponse
    {
        $competition = config('kemenham.competitions.'.$category);
        abort_unless($competition, 404);
        $user = $this->participant();
        if (! $user) {
            return redirect()->route('login', ['next' => route('work.show', $category, false)]);
        }

        $registration = DB::table('pendaftaran as p')
            ->join('lomba as l', 'l.id', '=', 'p.lomba_id')
            ->leftJoin('karya as k', 'k.pendaftaran_id', '=', 'p.id')
            ->where('p.pengguna_id', $user->id)
            ->where('l.kode', $category)
            ->select('p.*', 'k.judul', 'k.file_pdf', 'k.file_word', 'k.file_pernyataan')
            ->first();

        return view('pages.upload', $this->sharedData() + compact('competition', 'category', 'registration'));
    }

    public function saveWork(Request $request, string $category): RedirectResponse
    {
        $competition = config('kemenham.competitions.'.$category);
        abort_unless($competition, 404);
        $user = $this->participant();
        if (! $user) {
            return redirect()->route('login');
        }

        $registration = DB::table('pendaftaran as p')
            ->join('lomba as l', 'l.id', '=', 'p.lomba_id')
            ->where('p.pengguna_id', $user->id)->where('l.kode', $category)
            ->select('p.*', 'l.nama as lomba')->first();
        abort_unless($registration, 404, 'Pendaftaran lomba tidak ditemukan.');
        abort_unless($registration->tahap === 'terbuka', 403, 'Pengumpulan karya tidak sedang dibuka.');

        $rules = [
            'judul' => ['required', 'string', 'max:200'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:102400'],
            'pernyataan' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'setuju' => ['accepted'],
        ];
        if ($category === 'buku-cerita') {
            $rules['word'] = ['required', 'file', 'mimes:doc,docx', 'max:102400'];
        } else {
            $rules['word'] = ['nullable', 'file', 'mimes:doc,docx', 'max:102400'];
        }
        $data = $request->validate($rules);

        $old = DB::table('karya')->where('pendaftaran_id', $registration->id)->first();
        $newFiles = [];
        try {
            foreach (['pdf', 'word', 'pernyataan'] as $name) {
                if ($request->hasFile($name)) {
                    $newFiles[$name] = $this->storeFile($request->file($name));
                } else {
                    $newFiles[$name] = $old->{'file_'.$name} ?? '';
                }
            }
            DB::transaction(function () use ($data, $registration, $old, $newFiles, $user, $competition): void {
                $values = [
                    'judul' => trim($data['judul']),
                    'file_pdf' => $newFiles['pdf'],
                    'file_word' => $newFiles['word'],
                    'file_pernyataan' => $newFiles['pernyataan'],
                    'dikirim_pada' => now(),
                ];
                if ($old) {
                    DB::table('karya')->where('id', $old->id)->update($values);
                } else {
                    DB::table('karya')->insert($values + [
                        'pendaftaran_id' => $registration->id,
                        'file_kartu' => '',
                    ]);
                }
                DB::table('pendaftaran')->where('id', $registration->id)->update([
                    'tahap' => 'terkirim',
                    'status' => 'Karya terkirim',
                ]);
                DB::table('notifikasi')->insert([
                    'pengguna_id' => $user->id,
                    'lomba_id' => $registration->lomba_id,
                    'pesan' => 'Karya "'.trim($data['judul']).'" berhasil diunggah untuk '.$competition['name'].'.',
                    'dibuat_pada' => now(),
                ]);
            });
        } catch (\Throwable $error) {
            foreach ($newFiles as $filename) {
                if ($filename) {
                    File::delete($this->uploadPath($filename));
                }
            }
            throw $error;
        }

        foreach (['file_pdf', 'file_word', 'file_pernyataan'] as $column) {
            $previous = $old->{$column} ?? '';
            if ($previous && ! in_array($previous, $newFiles, true)) {
                File::delete($this->uploadPath($previous));
            }
        }

        return redirect()->route('profile', ['tab' => 'upload'])->with('success', 'Karya berhasil diunggah dan tercatat.');
    }

    public function profile(): View|RedirectResponse
    {
        $user = $this->participant();
        if (! $user) {
            return redirect()->route('login');
        }
        $registrations = $this->registrations((int) $user->id);
        $profile = $user;

        return view('pages.profile', $this->sharedData() + compact('profile', 'registrations'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $this->participant();
        abort_unless($user, 401);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'telepon' => ['required', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
            'tempat' => ['nullable', 'string', 'max:80'],
            'tgl' => ['required', 'date', 'before:today'],
            'alamat' => ['nullable', 'string', 'max:255'],
        ]);
        $name = preg_split('/\s+/', trim($data['nama']), 2);
        DB::table('pengguna')->where('id', $user->id)->update([
            'nama_depan' => $name[0],
            'nama_belakang' => $name[1] ?? '',
            'telepon' => preg_replace('/[\s-]/', '', $data['telepon']),
            'gender' => $data['gender'],
            'tempat_lahir' => trim($data['tempat'] ?? ''),
            'tanggal_lahir' => $data['tgl'],
            'alamat' => trim($data['alamat'] ?? ''),
        ]);

        return redirect()->route('profile', ['tab' => 'edit'])->with('success', 'Profil berhasil diperbarui.');
    }

    public function deleteRegistration(Request $request, int $registration): RedirectResponse
    {
        $user = $this->participant();
        abort_unless($user, 401);
        $row = DB::table('pendaftaran')->where('id', $registration)->where('pengguna_id', $user->id)->first();
        abort_unless($row, 404);
        abort_if($row->tahap === 'terkirim' || $row->status !== 'Terdaftar' || DB::table('karya')->where('pendaftaran_id', $row->id)->exists(), 403, 'Pendaftaran tidak bisa dihapus karena karya telah dikirim atau ditinjau.');

        if ($row->file_kartu) {
            File::delete($this->uploadPath($row->file_kartu));
        }
        DB::table('pendaftaran')->where('id', $row->id)->delete();

        return redirect()->route('profile', ['tab' => 'upload'])->with('success', 'Pendaftaran berhasil dihapus.');
    }

    public function notifications(): View|RedirectResponse
    {
        $user = $this->participant();
        if (! $user) {
            return redirect()->route('login');
        }
        $notifications = DB::table('notifikasi')->where('pengguna_id', $user->id)->orderByDesc('id')->limit(50)->get();

        return view('pages.notifications', $this->sharedData() + compact('notifications'));
    }

    public function markNotificationsRead(): RedirectResponse
    {
        $user = $this->participant();
        abort_unless($user, 401);
        DB::table('notifikasi')->where('pengguna_id', $user->id)->where('dibaca', 0)->update(['dibaca' => 1]);

        return redirect()->route('notifications')->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function downloadFile(int $registration, string $type): BinaryFileResponse
    {
        $user = $this->participant();
        abort_unless($user || session()->has('admin_id'), 401);
        $row = DB::table('pendaftaran as p')
            ->leftJoin('karya as k', 'k.pendaftaran_id', '=', 'p.id')
            ->where('p.id', $registration)
            ->when(! session()->has('admin_id'), fn ($query) => $query->where('p.pengguna_id', $user->id))
            ->select('p.nama_peserta', 'p.file_kartu', 'k.file_pdf', 'k.file_word', 'k.file_pernyataan', 'k.file_kartu as karya_kartu')
            ->first();
        abort_unless($row, 404);
        $columns = [
            'pdf' => $row->file_pdf,
            'word' => $row->file_word,
            'pernyataan' => $row->file_pernyataan,
            'kartu' => $row->file_kartu ?: $row->karya_kartu,
        ];
        abort_unless(isset($columns[$type]) && $columns[$type], 404);
        $path = $this->uploadPath($columns[$type]);
        abort_unless(is_file($path), 404);

        return response()->file($path, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function downloadAll(): BinaryFileResponse|RedirectResponse
    {
        $user = $this->participant();
        if (! $user) {
            return redirect()->route('login');
        }
        abort_unless(class_exists(\ZipArchive::class), 500, 'Ekstensi PHP zip diperlukan untuk mengunduh semua berkas.');

        $rows = DB::table('pendaftaran as p')
            ->leftJoin('karya as k', 'k.pendaftaran_id', '=', 'p.id')
            ->where('p.pengguna_id', $user->id)
            ->select('p.id', 'p.nama_peserta', 'p.file_kartu', 'k.file_pdf', 'k.file_word', 'k.file_pernyataan')
            ->get();
        $tmp = tempnam(sys_get_temp_dir(), 'kemenham-');
        $zip = new \ZipArchive;
        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            abort(500, 'Arsip berkas tidak dapat dibuat.');
        }

        foreach ($rows as $row) {
            foreach (['file_pdf' => 'karya', 'file_word' => 'naskah', 'file_pernyataan' => 'pernyataan', 'file_kartu' => 'kartu'] as $column => $label) {
                $name = $row->{$column} ?? '';
                $path = $name ? $this->uploadPath($name) : '';
                if ($path && is_file($path)) {
                    $zip->addFile($path, $row->id.'-'.$label.'.'.pathinfo($name, PATHINFO_EXTENSION));
                }
            }
        }
        $zip->close();

        return response()->download($tmp, 'berkas-pendaftaran.zip')->deleteFileAfterSend(true);
    }

    private function participant(): ?object
    {
        $id = session('uid');

        return $id ? DB::table('pengguna')->where('id', $id)->where('role', 'peserta')->first() : null;
    }

    private function sharedData(): array
    {
        $user = $this->participant();
        $notifications = $user
            ? DB::table('notifikasi')->where('pengguna_id', $user->id)->orderByDesc('id')->limit(10)->get()
            : collect();

        return [
            'authUser' => $user,
            'notifications' => $notifications,
            'unreadCount' => $notifications->where('dibaca', 0)->count(),
        ];
    }

    private function registrations(int $userId): Collection
    {
        return DB::table('pendaftaran as p')
            ->join('lomba as l', 'l.id', '=', 'p.lomba_id')
            ->leftJoin('karya as k', 'k.pendaftaran_id', '=', 'p.id')
            ->where('p.pengguna_id', $userId)
            ->select('p.*', 'l.kode', 'l.nama as lomba', 'k.judul', 'k.file_pdf', 'k.file_word', 'k.file_pernyataan', 'k.file_kartu as karya_file_kartu', 'k.dikirim_pada')
            ->orderBy('p.dibuat_pada')
            ->get();
    }

    private function storeFile(UploadedFile $file): string
    {
        $directory = config('kemenham.upload_path');
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0700, true);
        }
        abort_unless(File::isWritable($directory), 500, 'Folder penyimpanan berkas tidak dapat ditulis.');
        $name = bin2hex(random_bytes(16)).'.'.strtolower($file->getClientOriginalExtension());
        $file->move($directory, $name);

        return $name;
    }

    private function uploadPath(string $filename): string
    {
        abort_unless(preg_match('/^[a-f0-9]{32}\.(pdf|doc|docx|jpg|jpeg|png)$/i', basename($filename)), 404);

        return config('kemenham.upload_path').DIRECTORY_SEPARATOR.basename($filename);
    }
}
