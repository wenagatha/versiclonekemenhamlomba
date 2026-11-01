@extends('layouts.app')

@section('title', 'Notifikasi | Admin Kemenham')

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/admin-panel.css') }}">
@endsection

@section('content')
@include('partials.admin-nav')
<main class="admin-wrap narrow">
  <div class="page-heading"><div><h1>Notifikasi</h1><p>Pendaftar baru dan karya yang baru masuk.</p></div></div>
  <section class="admin-card event-list">
    @forelse ($events as $event)
      <a class="event" href="{{ route('admin.review', $event->id) }}">
        <b>{{ $event->nama_peserta }}</b>
        <span>{{ $event->judul ? 'Mengirim karya “' . $event->judul . '”' : 'Mendaftar lomba' }} · {{ $event->lomba }}</span>
        <small>{{ \Illuminate\Support\Carbon::parse($event->dikirim_pada ?: $event->dibuat_pada)->format('d M Y, H:i') }} · {{ $event->status }}</small>
      </a>
    @empty
      <p class="empty-state">Belum ada pendaftar.</p>
    @endforelse
  </section>
</main>
@endsection
