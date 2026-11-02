@extends('layouts.app')

@section('title', $competition['work_title'])

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/' . ($category === 'komik' ? 'unggah-komik.css' : 'unggah-buku-cerita.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/mobile.css') }}">
@endsection

@section('content')
<header class="wrap nav">
  <a class="brand" href="{{ route('home') }}"><img class="brand-logo" src="{{ asset('images/logo-kemenham.png') }}" alt=""><span>Kementerian Hak Asasi Manusia</span></a>
  <div class="actions">
    <a class="chip" href="{{ route('home') }}#notifikasi"><span>Notifikasi{{ $unreadCount ? ' (' . $unreadCount . ')' : '' }}</span></a>
    <a class="chip" href="{{ route('profile') }}"><span>Akun</span></a>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="chip" type="submit">Log-out</button></form>
  </div>
</header>

<main class="wrap">
  <div class="head">
    <a class="back" href="{{ route('registration.show', $category) }}">← Kembali ke data pendaftaran</a>
    <h1>{{ $competition['work_title'] }}</h1>
    <p>Unggah karya dan lembar pernyataan keaslian. Karyamu tersimpan langsung ke database dan berkas penyimpanan lomba.</p>
  </div>
  @if (!$registration)
    <section class="box form-box">
      <h2>Kamu belum terdaftar</h2>
      <p>Daftar lomba ini terlebih dahulu untuk mengunggah karya.</p>
      <a class="btn" href="{{ route('registration.show', $category) }}">Daftar lomba</a>
    </section>
  @elseif ($registration->tahap !== 'terbuka')
    <section class="box form-box">
      <h2>Karya sudah dikirim</h2>
      <p>Status saat ini: <b>{{ $registration->status }}</b>. Kamu dapat melihat pembaruan dan catatan panitia di profil.</p>
      <a class="btn" href="{{ route('profile', ['tab' => 'upload']) }}">Lihat status di profil</a>
    </section>
  @else
    <div class="layout">
      <form class="box form-box" method="post" action="{{ route('work.save', $category) }}" enctype="multipart/form-data">
        @csrf
        <div class="group">
          <h3 class="sec">Karya</h3>
          <div class="field"><label class="lbl" for="judul">Judul karya</label><input type="text" id="judul" name="judul" value="{{ old('judul', $registration->judul ?? '') }}" maxlength="200" required></div>
          <div class="field"><label class="lbl" for="pdf">Karya dalam format PDF (A5)</label><input type="file" id="pdf" name="pdf" accept=".pdf,application/pdf" required><span class="hint">PDF, maksimal 100 MB.</span></div>
          @if ($category === 'buku-cerita')
            <div class="field"><label class="lbl" for="word">Karya dalam format Word</label><input type="file" id="word" name="word" accept=".doc,.docx" required><span class="hint">DOC atau DOCX, maksimal 100 MB.</span></div>
          @endif
        </div>
        <div class="group">
          <h3 class="sec">Dokumen pendukung</h3>
          <div class="field">
            <label class="lbl" for="pernyataan">Lembar pernyataan keaslian karya</label>
            <p class="template">Belum punya? <a class="btn yellow" href="{{ asset('documents/' . ($category === 'komik' ? 'Lembar_Keaslian_Lomba_Serial_Komik_Bergambar.pdf' : 'Lembar_Keaslian_Lomba_Buku_Cerita_HAM.pdf')) }}" target="_blank" rel="noopener">Unduh template</a></p>
            <input type="file" id="pernyataan" name="pernyataan" accept=".pdf,.jpg,.jpeg,.png" required>
            <span class="hint">PDF, JPG, atau PNG, maksimal 10 MB.</span>
          </div>
        </div>
        <div class="group">
          <label class="agree"><input type="checkbox" name="setuju" value="1" required><span>Saya menyatakan karya ini orisinal, bukan murni hasil AI, belum pernah diikutkan lomba lain, dan data yang saya isi benar.</span></label>
          <button class="btn submit" type="submit">Kirim karya</button>
        </div>
      </form>
      <aside class="box side">
        <h2>Cek sebelum mengirim</h2>
        <ul>@foreach ($competition['rules'] as $rule)<li>{{ $rule }}</li>@endforeach</ul>
      </aside>
    </div>
  @endif
</main>
@endsection
