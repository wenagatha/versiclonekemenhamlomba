@extends('layouts.app')

@section('title', 'Akun Saya | Kementerian Hak Asasi Manusia')

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/profil.css') }}">
  <link rel="stylesheet" href="{{ asset('css/mobile.css') }}">
@endsection

@section('content')
<header class="wrap nav">
  <a class="brand" href="{{ route('home') }}"><img class="brand-logo" src="{{ asset('images/logo-kemenham.png') }}" alt=""><span>Kementerian Hak Asasi Manusia</span></a>
  <div class="actions">
    <a class="chip" href="{{ route('notifications') }}"><span>Notifikasi</span>@if ($unreadCount)<i class="dot" aria-label="{{ $unreadCount }} notifikasi belum dibaca"></i>@endif</a>
    <a class="chip on" href="{{ route('profile') }}"><span>Akun</span></a>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="chip" type="submit">Log-out</button></form>
  </div>
</header>

<main class="wrap">
  <div class="head">
    <a class="back" href="{{ route('home') }}">← Kembali ke beranda</a>
    <h1>Akun Saya</h1>
    <p>Kelola profil dan lihat pendaftaran lombamu.</p>
  </div>
  <div class="layout">
    <nav class="box tabs" aria-label="Menu akun">
      <a class="tab {{ request('tab', 'info') === 'info' ? 'on' : '' }}" href="{{ route('profile', ['tab' => 'info']) }}">Info Profil</a>
      <a class="tab {{ request('tab') === 'edit' ? 'on' : '' }}" href="{{ route('profile', ['tab' => 'edit']) }}">Edit Profil</a>
      <a class="tab {{ request('tab') === 'upload' ? 'on' : '' }}" href="{{ route('profile', ['tab' => 'upload']) }}">Hasil Upload</a>
    </nav>
    <div class="box main">
      @if (request('tab', 'info') === 'edit')
        <section>
          <h2>Edit Profil</h2>
          <p class="sub">Perbarui datamu. Email tidak bisa diubah.</p>
          <form method="post" action="{{ route('profile.update') }}" class="profile-form">
            @csrf
            <label class="field"><span class="lbl">Nama lengkap</span><input type="text" name="nama" value="{{ old('nama', trim($profile->nama_depan . ' ' . $profile->nama_belakang)) }}" maxlength="120" required></label>
            <label class="field"><span class="lbl">Email</span><input type="email" value="{{ $profile->email }}" readonly></label>
            <label class="field"><span class="lbl">Nomor telepon</span><input type="tel" name="telepon" value="{{ old('telepon', $profile->telepon) }}" required></label>
            <fieldset class="field"><legend class="lbl">Jenis kelamin</legend>
              <label><input type="radio" name="gender" value="Laki-laki" @checked(old('gender', $profile->gender) === 'Laki-laki')> Laki-laki</label>
              <label><input type="radio" name="gender" value="Perempuan" @checked(old('gender', $profile->gender) === 'Perempuan')> Perempuan</label>
            </fieldset>
            <label class="field"><span class="lbl">Tempat lahir</span><input type="text" name="tempat" value="{{ old('tempat', $profile->tempat_lahir) }}" maxlength="80"></label>
            <label class="field"><span class="lbl">Tanggal lahir</span><input type="date" name="tgl" value="{{ old('tgl', $profile->tanggal_lahir) }}" required></label>
            <label class="field"><span class="lbl">Alamat</span><textarea name="alamat" rows="3" maxlength="255">{{ old('alamat', $profile->alamat) }}</textarea></label>
            <button class="btn" type="submit">Simpan perubahan</button>
          </form>
        </section>
      @elseif (request('tab') === 'upload')
        <section>
          <h2>Hasil Upload</h2>
          <p class="sub">Pendaftaran lomba dan berkas yang sudah kamu kirim.</p>
          @if ($registrations->isNotEmpty())
            <a class="btn download-all-btn" href="{{ route('files.download-all') }}">Unduh semua berkas (ZIP)</a>
          @endif
          @forelse ($registrations as $entry)
            <article class="registration-card">
              <div class="registration-heading"><h3>{{ $entry->lomba }}</h3><span class="badge">{{ $entry->status }}</span></div>
              <p>Peserta: {{ $entry->nama_peserta }} · {{ $entry->tipe_tim }}<br>Asal: {{ $entry->asal }}<br>Mendaftar: {{ \Illuminate\Support\Carbon::parse($entry->dibuat_pada)->format('d M Y, H:i') }}</p>
              @if ($entry->judul)
                <p>Karya: <b>{{ $entry->judul }}</b> · dikirim {{ \Illuminate\Support\Carbon::parse($entry->dikirim_pada)->format('d M Y, H:i') }}</p>
              @else
                <p>Karya belum dikirim.</p>
              @endif
              @if ($entry->catatan_admin)<p class="review-note">Catatan panitia: {{ $entry->catatan_admin }}</p>@endif
              <div class="file-links">
                @foreach (['pdf' => $entry->file_pdf, 'word' => $entry->file_word, 'kartu' => ($entry->file_kartu ?: $entry->karya_file_kartu), 'pernyataan' => $entry->file_pernyataan] as $kind => $filename)
                  @if ($filename)<a href="{{ route('files.download', [$entry->id, $kind]) }}" target="_blank" rel="noopener">{{ ucfirst($kind) }}</a>@endif
                @endforeach
              </div>
              @if ($entry->tahap === 'terbuka')
                <a class="btn" href="{{ route('work.show', $entry->kode) }}">{{ $entry->judul ? 'Perbaiki karya' : 'Unggah karya' }} →</a>
              @endif
              @if ($entry->status === 'Terdaftar' && !$entry->judul)
                <form method="post" action="{{ route('registration.delete', $entry->id) }}" onsubmit="return confirm('Hapus pendaftaran ini?')">
                  @csrf @method('DELETE')
                  <button class="del" type="submit">Hapus pendaftaran</button>
                </form>
              @endif
            </article>
          @empty
            <div class="empty">Kamu belum mendaftar lomba apa pun.
              <p><a class="btn" href="{{ route('registration.show', 'buku-cerita') }}">Daftar Buku Cerita</a> <a class="btn" href="{{ route('registration.show', 'komik') }}">Daftar Komik</a></p>
            </div>
          @endforelse
        </section>
      @else
        <section>
          <h2>Info Profil</h2>
          <p class="sub">Data akun yang kamu isi saat membuat akun.</p>
          <div class="who"><div class="avatar">{{ mb_strtoupper(mb_substr($profile->nama_depan, 0, 1)) }}</div><div><b>{{ trim($profile->nama_depan . ' ' . $profile->nama_belakang) }}</b><span>{{ $profile->email }}</span></div></div>
          <dl class="info">
            <div><dt>Nomor telepon</dt><dd>{{ $profile->telepon ?: '-' }}</dd></div>
            <div><dt>Jenis kelamin</dt><dd>{{ $profile->gender ?: '-' }}</dd></div>
            <div><dt>Tempat lahir</dt><dd>{{ $profile->tempat_lahir ?: '-' }}</dd></div>
            <div><dt>Tanggal lahir</dt><dd>{{ $profile->tanggal_lahir ? \Illuminate\Support\Carbon::parse($profile->tanggal_lahir)->format('d M Y') : '-' }}</dd></div>
            <div class="full"><dt>Alamat</dt><dd>{{ $profile->alamat ?: '-' }}</dd></div>
            <div class="full"><dt>Akun dibuat</dt><dd>{{ \Illuminate\Support\Carbon::parse($profile->dibuat_pada)->format('d M Y') }}</dd></div>
          </dl>
        </section>
      @endif
    </div>
  </div>
</main>
@endsection
