@extends('layouts.app')

@section('title', 'Dashboard | Admin Kemenham')

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/admin-panel.css') }}">
@endsection

@section('content')
@include('partials.admin-nav')
<main class="admin-wrap">
  <div class="page-heading"><div><h1>Monitoring karya peserta</h1><p>Pantau pendaftaran dan karya yang masuk, lalu perbarui statusnya.</p></div></div>
  <section class="admin-kpis">
    <article><span>Total pendaftar</span><strong>{{ $totals->total ?? 0 }}</strong></article>
    <article><span>Karya masuk</span><strong>{{ $totals->submitted ?? 0 }}</strong></article>
    <article><span>Perlu ditinjau</span><strong class="yellow">{{ $totals->review ?? 0 }}</strong></article>
    <article><span>Sudah diverifikasi</span><strong>{{ $totals->verified ?? 0 }}</strong></article>
  </section>
  <section class="admin-charts">
    <article class="admin-card"><h2>Status pendaftaran</h2>
      @forelse ($statusCounts as $item)
        @php($share = $totals->total ? round($item->total / $totals->total * 100) : 0)
        <div class="chart-row"><div><span>{{ $item->status }}</span><b>{{ $item->total }}</b></div><div class="meter"><i style="width:{{ $share }}%"></i></div></div>
      @empty<p class="muted">Belum ada data pendaftaran.</p>@endforelse
    </article>
    <article class="admin-card"><h2>Pendaftar dan karya per lomba</h2>
      @forelse ($competitionCounts as $item)
        <div class="competition-row"><div><b>{{ $item->nama }}</b><span>{{ $item->daftar }} pendaftar · {{ $item->karya }} karya</span></div><div class="bar-pair"><i style="height:{{ min(100, max(4, $item->daftar * 8)) }}%"></i><b style="height:{{ min(100, max(4, $item->karya * 8)) }}%"></b></div></div>
      @empty<p class="muted">Belum ada lomba.</p>@endforelse
    </article>
    <article class="admin-card"><h2>Pendaftaran terbaru per hari</h2>
      @forelse ($dailyCounts->take(-8) as $item)
        <div class="daily-row"><time>{{ \Illuminate\Support\Carbon::parse($item->hari)->format('d M') }}</time><div class="meter"><i style="width:{{ min(100, max(4, $item->total * 10)) }}%"></i></div><b>{{ $item->total }}</b></div>
      @empty<p class="muted">Belum ada pendaftaran.</p>@endforelse
    </article>
  </section>
  <section class="admin-card table-card">
    <form class="filters" method="get" action="{{ route('admin.dashboard') }}">
      <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama, asal, atau judul" aria-label="Cari">
      <select name="lomba" aria-label="Filter lomba"><option value="0">Semua lomba</option>@foreach ($competitions as $competition)<option value="{{ $competition->id }}" @selected($selectedCompetition === (int)$competition->id)>{{ $competition->nama }}</option>@endforeach</select>
      <select name="status" aria-label="Filter status"><option value="">Semua status</option>@foreach ($statusList as $status)<option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $status }}</option>@endforeach</select>
      <button class="admin-button" type="submit">Terapkan</button><a class="admin-button ghost" href="{{ route('admin.dashboard') }}">Atur ulang</a>
    </form>
    <div class="table-scroll"><table>
      <thead><tr><th>Peserta</th><th>Lomba</th><th>Judul karya</th><th>Dikirim</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @forelse ($registrations as $entry)
          <tr>
            <td><b>{{ $entry->nama_peserta }}</b><small>{{ $entry->asal }}</small></td>
            <td>{{ $entry->lomba }}</td>
            <td>{{ $entry->judul ?: 'Belum mengirim' }}</td>
            <td>{{ $entry->dikirim_pada ? \Illuminate\Support\Carbon::parse($entry->dikirim_pada)->format('d M Y') : '-' }}</td>
            <td><span class="status-badge">{{ $entry->status }}</span></td>
            <td><a class="admin-button ghost" href="{{ route('admin.review', $entry->id) }}">Tinjau</a></td>
          </tr>
        @empty
          <tr><td class="muted" colspan="6">Belum ada data yang cocok dengan filter ini.</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </section>
</main>
@endsection
