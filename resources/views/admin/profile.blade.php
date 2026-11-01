@extends('layouts.app')

@section('title', 'Profil | Admin Kemenham')

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/admin-panel.css') }}">
@endsection

@section('content')
@include('partials.admin-nav')
<main class="admin-wrap narrow">
  <div class="page-heading"><div><h1>Profil admin</h1><p>Kelola informasi akun admin.</p></div></div>
  <section class="admin-card">
    <div class="admin-profile"><div>{{ mb_strtoupper(mb_substr($admin->nama_depan, 0, 1)) }}</div><p><b>{{ $admin->nama_depan }}</b><span>{{ $admin->email }}</span><span>Akun dibuat {{ \Illuminate\Support\Carbon::parse($admin->dibuat_pada)->format('d M Y') }}</span></p></div>
    <form method="post" action="{{ route('admin.profile.update') }}" class="review-form">
      @csrf
      <label for="nama">Nama</label><input id="nama" name="nama" value="{{ old('nama', $admin->nama_depan) }}" maxlength="60" required>
      <button class="admin-button" type="submit">Simpan perubahan</button>
    </form>
  </section>
</main>
@endsection
