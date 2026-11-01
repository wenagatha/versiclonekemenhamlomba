<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(Request $request): View
    {
        $statusList = config('kemenham.status');
        $query = DB::table('pendaftaran as p')
            ->join('lomba as l', 'l.id', '=', 'p.lomba_id')
            ->leftJoin('karya as k', 'k.pendaftaran_id', '=', 'p.id')
            ->select('p.*', 'l.nama as lomba', 'k.judul', 'k.file_pdf', 'k.file_word', 'k.file_pernyataan', 'k.dikirim_pada');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($where) use ($search): void {
                $where->where('p.nama_peserta', 'like', '%'.$search.'%')
                    ->orWhere('p.asal', 'like', '%'.$search.'%')
                    ->orWhere('k.judul', 'like', '%'.$search.'%');
            });
        }
        if ($competitionId = (int) $request->query('lomba', 0)) {
            $query->where('p.lomba_id', $competitionId);
        }
        if (in_array($request->query('status'), $statusList, true)) {
            $query->where('p.status', $request->query('status'));
        }

        $registrations = $query->orderByDesc('p.dibuat_pada')->get();
        $totals = DB::table('pendaftaran')->selectRaw(
            "COUNT(*) as total, SUM(status <> 'Terdaftar') as submitted, SUM(status = 'Karya terkirim') as review, SUM(status = 'Diverifikasi') as verified"
        )->first();
        $statusCounts = DB::table('pendaftaran')->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->get();
        $competitionCounts = DB::table('lomba as l')
            ->leftJoin('pendaftaran as p', 'p.lomba_id', '=', 'l.id')
            ->leftJoin('karya as k', 'k.pendaftaran_id', '=', 'p.id')
            ->select('l.nama')
            ->selectRaw('COUNT(DISTINCT p.id) as daftar, COUNT(DISTINCT k.id) as karya')
            ->groupBy('l.id', 'l.nama')
            ->get();
        $dailyCounts = DB::table('pendaftaran')
            ->selectRaw('DATE(dibuat_pada) as hari, COUNT(*) as total')
            ->groupByRaw('DATE(dibuat_pada)')
            ->orderBy('hari')
            ->get();
        $competitions = DB::table('lomba')->orderBy('id')->get();

        return view('admin.dashboard', compact(
            'registrations', 'statusList', 'totals', 'statusCounts',
            'competitionCounts', 'dailyCounts', 'competitions'
        ) + [
            'adminName' => session('admin'),
            'adminEmail' => session('email'),
            'search' => $request->query('q', ''),
            'selectedCompetition' => (int) $request->query('lomba', 0),
            'selectedStatus' => $request->query('status', ''),
        ]);
    }

    public function review(int $registration): View
    {
        $entry = DB::table('pendaftaran as p')
            ->join('lomba as l', 'l.id', '=', 'p.lomba_id')
            ->leftJoin('karya as k', 'k.pendaftaran_id', '=', 'p.id')
            ->where('p.id', $registration)
            ->select('p.*', 'l.nama as lomba', 'k.judul', 'k.file_pdf', 'k.file_word', 'k.file_pernyataan', 'k.file_kartu as karya_file_kartu', 'k.dikirim_pada')
            ->first();
        abort_unless($entry, 404);

        return view('admin.review', [
            'entry' => $entry,
            'statusList' => config('kemenham.status'),
            'adminName' => session('admin'),
        ]);
    }

    public function updateReview(Request $request, int $registration): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', config('kemenham.status'))],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);
        $entry = DB::table('pendaftaran')->where('id', $registration)->first();
        abort_unless($entry, 404);

        DB::transaction(function () use ($data, $entry, $registration): void {
            DB::table('pendaftaran')->where('id', $registration)->update([
                'status' => $data['status'],
                'catatan_admin' => trim($data['catatan'] ?? ''),
                'tahap' => $data['status'] === 'Perlu revisi'
                    ? 'terbuka'
                    : (DB::table('karya')->where('pendaftaran_id', $registration)->exists() ? 'terkirim' : 'terbuka'),
            ]);

            $message = 'Status pendaftaran diperbarui menjadi: '.$data['status'].'.';
            if ($data['status'] === 'Perlu revisi') {
                $message .= ' Kamu bisa mengunggah ulang karyamu.';
            }
            if (trim($data['catatan'] ?? '') !== '') {
                $message .= ' Catatan panitia: '.mb_substr(trim($data['catatan']), 0, 140);
            }
            DB::table('notifikasi')->insert([
                'pengguna_id' => $entry->pengguna_id,
                'lomba_id' => $entry->lomba_id,
                'pesan' => mb_substr($message, 0, 255),
                'dibuat_pada' => now(),
            ]);
        });

        return redirect()->route('admin.review', $registration)->with('success', 'Status tersimpan dan notifikasi telah dikirim kepada peserta.');
    }

    public function notifications(): View
    {
        $events = DB::table('pendaftaran as p')
            ->join('lomba as l', 'l.id', '=', 'p.lomba_id')
            ->leftJoin('karya as k', 'k.pendaftaran_id', '=', 'p.id')
            ->select('p.id', 'p.nama_peserta', 'p.status', 'p.dibuat_pada', 'l.nama as lomba', 'k.judul', 'k.dikirim_pada')
            ->orderByDesc('p.dibuat_pada')
            ->limit(100)->get();

        return view('admin.notifications', [
            'events' => $events,
            'adminName' => session('admin'),
        ]);
    }

    public function profile(): View
    {
        $admin = DB::table('pengguna')->where('id', session('admin_id'))->where('role', 'admin')->first();
        abort_unless($admin, 403);

        return view('admin.profile', compact('admin') + ['adminName' => session('admin')]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate(['nama' => ['required', 'string', 'max:60']]);
        $name = trim($data['nama']);
        DB::table('pengguna')->where('id', session('admin_id'))->where('role', 'admin')->update(['nama_depan' => $name]);
        $request->session()->put('admin', $name);

        return redirect()->route('admin.profile')->with('success', 'Profil admin berhasil diperbarui.');
    }
}
