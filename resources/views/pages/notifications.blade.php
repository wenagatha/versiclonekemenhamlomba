@extends('layouts.app')

@section('title', 'Notifikasi | Kementerian Hak Asasi Manusia')

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/profil.css') }}">
  <link rel="stylesheet" href="{{ asset('css/mobile.css') }}">
@endsection

@section('content')
<header class="wrap nav">
  <a class="brand" href="{{ route('home') }}"><img class="brand-logo" src="{{ asset('images/logo-kemenham.png') }}" alt=""><span>Kementerian Hak Asasi Manusia</span></a>
  <div class="actions"><a class="chip on" href="{{ route('notifications') }}"><span>Notifikasi</span></a><a class="chip" href="{{ route('profile') }}"><span>Akun</span></a>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="chip" type="submit">Log-out</button></form>
  </div>
</header>
<main class="wrap">
  <div class="head"><a class="back" href="{{ route('home') }}">← Kembali ke beranda</a><h1>Notifikasi</h1><p>Pembaruan pendaftaran dan karya lombamu.</p></div>
  @if ($notifications->contains('dibaca', 0))
    <form method="post" action="{{ route('notifications.read') }}">@csrf<button class="btn" type="submit">Tandai semua dibaca</button></form>
  @endif
  <section class="notification-list">
    @forelse ($notifications as $notification)
      <article class="notification-item {{ !$notification->dibaca ? 'unread' : '' }}"><p>{{ $notification->pesan }}</p><time>{{ \Illuminate\Support\Carbon::parse($notification->dibuat_pada)->format('d M Y, H:i') }}</time></article>
    @empty
      <p class="empty">Belum ada notifikasi.</p>
    @endforelse
  </section>
</main>
@endsection
