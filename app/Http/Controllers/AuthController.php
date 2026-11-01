<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showParticipant(string $mode = 'login'): View|RedirectResponse
    {
        if (session()->has('uid')) {
            return redirect()->route('home');
        }

        return view('auth.participant', [
            'mode' => $mode === 'signup' ? 'signup' : 'login',
            'next' => is_string(request()->query('next')) ? request()->query('next') : '',
        ]);
    }

    public function registerParticipant(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['nullable', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:120', 'unique:pengguna,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
            'birth_place' => ['nullable', 'string', 'max:80'],
            'birth_date' => ['required', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $id = DB::table('pengguna')->insertGetId([
            'nama_depan' => trim($data['first_name']),
            'nama_belakang' => trim($data['last_name'] ?? ''),
            'email' => mb_strtolower(trim($data['email'])),
            'password_hash' => Hash::make($data['password']),
            'role' => 'peserta',
            'telepon' => trim($data['phone'] ?? ''),
            'gender' => $data['gender'],
            'tempat_lahir' => trim($data['birth_place'] ?? ''),
            'tanggal_lahir' => $data['birth_date'],
            'alamat' => trim($data['address'] ?? ''),
            'dibuat_pada' => now(),
        ]);

        DB::table('notifikasi')->insert([
            'pengguna_id' => $id,
            'pesan' => 'Akun berhasil dibuat. Selamat datang! Lengkapi profil dan pilih lomba yang ingin kamu ikuti.',
            'dibuat_pada' => now(),
        ]);

        $request->session()->regenerate();
        $request->session()->put('uid', $id);

        return $this->authenticatedRedirect($request, route('home'), 'Akun berhasil dibuat. Selamat datang!');
    }

    public function loginParticipant(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = DB::table('pengguna')
            ->where('email', mb_strtolower(trim($credentials['email'])))
            ->where('role', 'peserta')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password_hash)) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('uid', (int) $user->id);

        return $this->authenticatedRedirect($request, route('home'), 'Selamat datang kembali!');
    }

    public function showAdmin(string $mode = 'login'): View|RedirectResponse
    {
        if (session()->has('admin_id')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth', ['mode' => $mode === 'signup' ? 'signup' : 'login']);
    }

    public function registerAdmin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:120', 'unique:pengguna,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $id = DB::table('pengguna')->insertGetId([
            'nama_depan' => trim($data['nama']),
            'email' => mb_strtolower(trim($data['email'])),
            'password_hash' => Hash::make($data['password']),
            'role' => 'admin',
            'gender' => 'Laki-laki',
            'tanggal_lahir' => '1990-01-01',
            'dibuat_pada' => now(),
        ]);

        $request->session()->regenerate();
        $request->session()->put(['admin_id' => $id, 'admin' => trim($data['nama']), 'email' => mb_strtolower(trim($data['email']))]);

        return redirect()->route('admin.dashboard')->with('success', 'Akun admin berhasil dibuat.');
    }

    public function loginAdmin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = DB::table('pengguna')
            ->where('email', mb_strtolower(trim($credentials['email'])))
            ->where('role', 'admin')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password_hash)) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah, atau akun ini bukan admin.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put(['admin_id' => (int) $user->id, 'admin' => $user->nama_depan, 'email' => $user->email]);

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Berhasil masuk.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $wasAdmin = $request->session()->has('admin_id');
        $request->session()->forget(['uid', 'admin_id', 'admin', 'email']);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route($wasAdmin ? 'admin.login' : 'login')->with('success', 'Kamu berhasil keluar.');
    }

    private function authenticatedRedirect(Request $request, string $fallback, string $message): RedirectResponse
    {
        $next = (string) $request->input('next', '');
        if (
            str_starts_with($next, '/')
            && ! str_starts_with($next, '//')
            && ! str_contains($next, '\\')
            && parse_url($next, PHP_URL_HOST) === null
        ) {
            return redirect($next)->with('success', $message);
        }

        return redirect()->intended($fallback)->with('success', $message);
    }
}
