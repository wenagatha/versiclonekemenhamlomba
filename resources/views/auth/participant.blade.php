@extends('layouts.app')

@section('title', $mode === 'signup' ? 'Daftar | Kementerian Hak Asasi Manusia' : 'Masuk | Kementerian Hak Asasi Manusia')

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/auth-page.css') }}">
  <link rel="stylesheet" href="{{ asset('css/mobile.css') }}">
@endsection

@section('content')
<main class="card" data-mode="{{ $mode }}">
  <aside class="hero" id="hero" aria-roledescription="carousel" aria-label="Galeri gambar">
    <div class="track" id="track">
      <div class="slide"><img src="{{ asset('images/auth-slide-meja.jpg') }}" alt="Komik Satu Meja, Banyak Cerita tentang musyawarah dan kerja sama"></div>
      <div class="slide"><img src="{{ asset('images/auth-slide-es-krim.jpg') }}" alt="Komik tentang seorang anak yang ingin menjadi pembela kebenaran"></div>
      <div class="slide"><img src="{{ asset('images/auth-slide-pancasila.jpg') }}" alt="Komik tentang nilai-nilai Pancasila dalam kehidupan sehari-hari"></div>
    </div>
    <div class="logo"><img src="{{ asset('images/logo-kemenham.png') }}" alt="Logo Kementerian Hak Asasi Manusia"></div>
    <button type="button" class="arrow prev" id="prev" aria-label="Gambar sebelumnya"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 5l-7 7 7 7"/></svg></button>
    <button type="button" class="arrow next" id="next" aria-label="Gambar berikutnya"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 5l7 7-7 7"/></svg></button>
    <div class="dots" id="dots"></div>
  </aside>
  <section class="panel">
    <h1>{{ $mode === 'signup' ? 'Buat akun' : 'Selamat datang' }}</h1>
    <p class="switch">
      @if ($mode === 'signup')
        Sudah punya akun? <a href="{{ route('login', array_filter(['next' => $next])) }}">Masuk</a>
      @else
        Belum punya akun? <a href="{{ route('signup', array_filter(['next' => $next])) }}">Daftar</a>
      @endif
    </p>

    <form method="post" action="{{ $mode === 'signup' ? route('signup.submit') : route('login.submit') }}">
      @csrf
      @if ($mode === 'signup')
        <div class="row">
          <input type="text" name="first_name" value="{{ old('first_name') }}" maxlength="60" required placeholder="Nama depan" aria-label="Nama depan" autocomplete="given-name">
          <input type="text" name="last_name" value="{{ old('last_name') }}" maxlength="60" placeholder="Nama belakang" aria-label="Nama belakang" autocomplete="family-name">
        </div>
      @endif
      <input type="email" name="email" value="{{ old('email') }}" maxlength="120" required placeholder="Email" aria-label="Email" autocomplete="email">
      @if ($mode === 'signup')
        <div class="row">
          <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="20" placeholder="Nomor telepon" aria-label="Nomor telepon" inputmode="tel" autocomplete="tel">
          <select name="gender" required aria-label="Jenis kelamin">
            <option value="" disabled @selected(!old('gender'))>Jenis kelamin</option>
            <option value="Laki-laki" @selected(old('gender') === 'Laki-laki')>Laki-laki</option>
            <option value="Perempuan" @selected(old('gender') === 'Perempuan')>Perempuan</option>
          </select>
        </div>
        <div class="row">
          <input type="text" name="birth_place" value="{{ old('birth_place') }}" maxlength="80" placeholder="Tempat lahir" aria-label="Tempat lahir">
          <input type="date" name="birth_date" value="{{ old('birth_date') }}" required aria-label="Tanggal lahir">
        </div>
        <textarea name="address" rows="2" maxlength="255" placeholder="Alamat" aria-label="Alamat">{{ old('address') }}</textarea>
      @else
        <label class="check"><input type="checkbox" name="remember"> <span>Ingat saya</span></label>
      @endif
      <div class="field">
        <input type="password" name="password" id="pw" required minlength="{{ $mode === 'signup' ? 8 : 1 }}" placeholder="{{ $mode === 'signup' ? 'Kata sandi (minimal 8 karakter)' : 'Kata sandi' }}" aria-label="Kata sandi" autocomplete="{{ $mode === 'signup' ? 'new-password' : 'current-password' }}">
        <button type="button" class="eye" id="eye" aria-label="Tampilkan kata sandi">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
      @if ($errors->any())
        <p class="auth-err" role="alert">{{ $errors->first() }}</p>
      @endif
      @if ($next)
        <input type="hidden" name="next" value="{{ $next }}">
      @endif
      <button class="primary" type="submit">{{ $mode === 'signup' ? 'Buat akun' : 'Masuk' }}</button>
    </form>
  </section>
</main>
@endsection

@section('scripts')
  <script src="{{ asset('js/auth-page.js') }}" defer></script>
@endsection
