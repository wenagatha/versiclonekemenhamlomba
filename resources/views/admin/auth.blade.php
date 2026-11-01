@extends('layouts.app')

@section('title', ($mode === 'signup' ? 'Daftar' : 'Masuk') . ' | Admin Kemenham')

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/admin-masuk.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-daftar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-auth.css') }}">
  <link rel="stylesheet" href="{{ asset('css/mobile.css') }}">
@endsection

@section('content')
<main class="auth">
  <section class="l">
    <a class="logo" href="{{ route('admin.login') }}"><i></i>Admin Kemenham</a>
    <div class="login-content">
      <h1>{{ $mode === 'signup' ? 'Buat akun admin' : 'Selamat datang kembali' }}</h1>
      <p class="sub">
        @if ($mode === 'signup')
          Sudah punya akun? <a href="{{ route('admin.login') }}">Masuk</a>
        @else
          Belum punya akun? <a href="{{ route('admin.signup') }}">Daftar</a>
        @endif
      </p>
      <form method="post" action="{{ $mode === 'signup' ? route('admin.signup.submit') : route('admin.login.submit') }}">
        @csrf
        @if ($mode === 'signup')<input type="text" name="nama" value="{{ old('nama') }}" maxlength="60" placeholder="Nama" autocomplete="name" required>@endif
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email admin" autocomplete="email" required>
        <input type="password" name="password" placeholder="{{ $mode === 'signup' ? 'Kata sandi (minimal 8 karakter)' : 'Kata sandi' }}" autocomplete="{{ $mode === 'signup' ? 'new-password' : 'current-password' }}" minlength="{{ $mode === 'signup' ? 8 : 1 }}" required>
        @if ($errors->any())<p class="err" role="alert">{{ $errors->first() }}</p>@endif
        <button class="btn" type="submit">{{ $mode === 'signup' ? 'Buat akun' : 'Masuk' }}</button>
      </form>
    </div>
  </section>
  <section class="r" aria-label="Dashboard admin">
    <div class="admin-illustration"><i></i><i></i><i></i><b>Admin<br>Dashboard</b></div>
  </section>
</main>
@endsection
