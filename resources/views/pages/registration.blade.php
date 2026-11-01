@extends('layouts.app')

@section('title', 'Daftar ' . $competition['name'])

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/' . ($category === 'komik' ? 'daftar-komik.css' : 'daftar-buku-cerita.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/mobile.css') }}">
@endsection

@section('content')
<header class="wrap nav">
  <a class="brand" href="{{ route('home') }}"><img class="brand-logo" src="{{ asset('images/logo-kemenham.png') }}" alt=""><span>Kementerian Hak Asasi Manusia</span></a>
  <div class="actions">
    <a class="chip" href="{{ route('notifications') }}"><span>Notifikasi{{ $unreadCount ? ' (' . $unreadCount . ')' : '' }}</span></a>
    <a class="chip" href="{{ route('profile') }}"><span>Akun</span></a>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="chip" type="submit">Log-out</button></form>
  </div>
</header>

<main class="wrap">
  <div class="head">
    <a class="back" href="{{ route('home') }}">← Kembali ke beranda</a>
    <h1>{{ $competition['name'] }}</h1>
    <p>Isi data peserta dan kartu perwakilan. Data masih bisa diperbaiki sebelum karya dikirim. Pendaftaran 22 Oktober sampai 4 November 2026, pukul 23.59 WIB.</p>
  </div>

  @if ($registration && ($registration->status !== 'Terdaftar'))
    <section class="box form-box">
      <h2 class="sec">Pendaftaran sudah dikunci</h2>
      <p>Status saat ini: <b>{{ $registration->status }}</b>. Hubungi panitia jika ada kesalahan pada data pendaftaran.</p>
      <a class="btn" href="{{ route('profile', ['tab' => 'upload']) }}">Lihat status di profil</a>
    </section>
  @else
    <div class="layout">
      <form class="box form-box" method="post" action="{{ route('registration.save', $category) }}" enctype="multipart/form-data">
        @csrf
        <div class="group">
          <h3 class="sec">Data peserta</h3>
          <fieldset class="field">
            <legend class="lbl">Jenis peserta</legend>
            <div class="seg">
              <label><input type="radio" name="tim" value="individu" @checked(old('tim', strtolower($registration->tipe_tim ?? 'individu')) === 'individu')><span>Individu</span></label>
              <label><input type="radio" name="tim" value="kelompok" @checked(old('tim', strtolower($registration->tipe_tim ?? '')) === 'kelompok')><span>Kelompok<small>Maksimal 3 orang</small></span></label>
            </div>
          </fieldset>
          <div class="field">
            <label class="lbl" for="peserta">Nama lengkap</label>
            <input type="text" id="peserta" name="peserta" value="{{ old('peserta', $registration->nama_peserta ?? trim($authUser->nama_depan . ' ' . $authUser->nama_belakang)) }}" autocomplete="name" maxlength="120" required>
          </div>
          <div class="members" id="members">
            <div class="row">
              <div class="field"><label class="lbl" for="anggota2">Anggota 2</label><input type="text" id="anggota2" name="anggota2" value="{{ old('anggota2', $registration->anggota2 ?? '') }}" maxlength="120"></div>
              <div class="field"><label class="lbl" for="anggota3">Anggota 3</label><input type="text" id="anggota3" name="anggota3" value="{{ old('anggota3', $registration->anggota3 ?? '') }}" maxlength="120"></div>
            </div>
          </div>
          <div class="row">
            <div class="field"><label class="lbl" for="nomor">{{ $competition['id_label'] }}</label><input type="text" id="nomor" name="nomor" value="{{ old('nomor', $registration->nomor_induk ?? '') }}" maxlength="40" required></div>
            <div class="field"><label class="lbl" for="asal">{{ $competition['origin_label'] }}</label><input type="text" id="asal" name="asal" value="{{ old('asal', $registration->asal ?? '') }}" maxlength="120" required></div>
          </div>
          <div class="row">
            <div class="field"><label class="lbl" for="email">Email</label><input type="email" id="email" name="email" value="{{ old('email', $registration->email ?? $authUser->email) }}" maxlength="120" required></div>
            <div class="field"><label class="lbl" for="wa">WhatsApp aktif</label><input type="tel" id="wa" name="wa" value="{{ old('wa', $registration->whatsapp ?? $authUser->telepon) }}" maxlength="20" required><span class="hint">Panitia akan menghubungi nomor ini.</span></div>
          </div>
          <div class="field">
            <label class="lbl" for="kartu">{{ $competition['card_label'] }}</label>
            <input type="file" id="kartu" name="kartu" accept=".pdf,.jpg,.jpeg,.png" @required(!$registration || !$registration->file_kartu)>
            <span class="hint">{{ $registration && $registration->file_kartu ? 'Kartu sudah tersimpan. Pilih berkas hanya jika ingin menggantinya.' : 'PDF, JPG, atau PNG, maksimal 10 MB. Kalau berkelompok, cukup kartu perwakilan.' }}</span>
          </div>
        </div>
        <div class="group">
          <label class="agree"><input type="checkbox" name="setuju" value="1" @checked(old('setuju')) required><span>Saya menyatakan bahwa saya adalah {{ $competition['participant'] }} di Daerah Khusus Jakarta dan data yang saya isi benar serta boleh diolah panitia untuk keperluan lomba ini.</span></label>
          <button class="btn submit" type="submit">{{ $registration ? 'Simpan perubahan' : 'Kirim pendaftaran' }}</button>
        </div>
      </form>
      <aside class="box side">
        <h2>Syarat singkat</h2>
        <ul>@foreach ($competition['rules'] as $rule)<li>{{ $rule }}</li>@endforeach</ul>
        <a class="btn yellow" href="{{ asset('documents/pedoman-lomba-ham-2026.pdf') }}" target="_blank" rel="noopener">Unduh pedoman lengkap</a>
      </aside>
    </div>
  @endif
</main>
@endsection

@section('scripts')
  <script src="{{ asset('js/registration.js') }}" defer></script>
@endsection
