@extends('layouts.app')

@section('title', 'Tinjau pendaftaran | Admin Kemenham')

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/admin-panel.css') }}">
@endsection

@section('content')
@include('partials.admin-nav')
<main class="admin-wrap">
  <a class="back-link" href="{{ route('admin.dashboard') }}">← Kembali ke dashboard</a>
  <div class="page-heading">
    <div><p class="eyebrow">TINJAU PENDAFTARAN #{{ $entry->id }}</p><h1>{{ $entry->nama_peserta }}</h1><p>{{ $entry->asal }} · {{ $entry->lomba }}</p></div>
    <span class="status-badge">{{ $entry->status }}</span>
  </div>
  <section class="review-layout">
    <div class="review-main">
      <article class="admin-card">
        <p class="eyebrow">KARYA PESERTA</p><h2>{{ $entry->judul ?: 'Belum ada karya dikirim' }}</h2>
        @if ($entry->file_pdf)
          <iframe class="work-preview" title="Pratinjau naskah PDF" src="{{ route('files.download', [$entry->id, 'pdf']) }}"></iframe>
          <a href="{{ route('files.download', [$entry->id, 'pdf']) }}" target="_blank" rel="noopener">Buka atau unduh naskah PDF</a>
        @else
          <p class="empty-state">Peserta belum mengirim naskah karya.</p>
        @endif
      </article>
      <article class="admin-card">
        <p class="eyebrow">BERKAS PENDAFTARAN</p>
        <div class="review-files">
          @foreach (['pdf' => $entry->file_pdf, 'word' => $entry->file_word, 'kartu' => ($entry->file_kartu ?: $entry->karya_file_kartu), 'pernyataan' => $entry->file_pernyataan] as $kind => $filename)
            <div><b>{{ ucfirst($kind) }}</b>@if ($filename)<a href="{{ route('files.download', [$entry->id, $kind]) }}" target="_blank" rel="noopener">Buka berkas ↗</a>@else<span class="muted">Belum tersedia</span>@endif</div>
          @endforeach
        </div>
      </article>
    </div>
    <aside class="review-side">
      <article class="admin-card">
        <p class="eyebrow">INFORMASI PESERTA</p>
        <dl class="review-details">
          <div><dt>Jenis peserta</dt><dd>{{ $entry->tipe_tim }}</dd></div>
          @if ($entry->tipe_tim === 'Kelompok')<div><dt>Anggota 2</dt><dd>{{ $entry->anggota2 ?: '-' }}</dd></div><div><dt>Anggota 3</dt><dd>{{ $entry->anggota3 ?: '-' }}</dd></div>@endif
          <div><dt>Nomor induk</dt><dd>{{ $entry->nomor_induk }}</dd></div><div><dt>Email</dt><dd><a href="mailto:{{ $entry->email }}">{{ $entry->email }}</a></dd></div>
          <div><dt>WhatsApp</dt><dd>{{ $entry->whatsapp }}</dd></div>
          <div><dt>Terdaftar</dt><dd>{{ \Illuminate\Support\Carbon::parse($entry->dibuat_pada)->format('d M Y, H:i') }}</dd></div>
          <div><dt>Karya dikirim</dt><dd>{{ $entry->dikirim_pada ? \Illuminate\Support\Carbon::parse($entry->dikirim_pada)->format('d M Y, H:i') : '-' }}</dd></div>
        </dl>
      </article>
      <article class="admin-card">
        <p class="eyebrow">HASIL PENINJAUAN</p>
        <form method="post" action="{{ route('admin.review.update', $entry->id) }}" class="review-form">
          @csrf
          <label for="status">Status</label>
          <select id="status" name="status" required>@foreach ($statusList as $status)<option value="{{ $status }}" @selected($entry->status === $status)>{{ $status }}</option>@endforeach</select>
          <label for="catatan">Catatan untuk peserta</label>
          <textarea id="catatan" name="catatan" rows="5" maxlength="255">{{ old('catatan', $entry->catatan_admin) }}</textarea>
          <button class="admin-button" type="submit">Simpan status</button>
          <p class="muted">Peserta akan menerima notifikasi. “Perlu revisi” membuka kembali pengiriman karya.</p>
        </form>
      </article>
    </aside>
  </section>
</main>
@endsection
