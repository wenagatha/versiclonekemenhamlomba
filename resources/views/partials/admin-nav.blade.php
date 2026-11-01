<header class="admin-top">
  <a class="admin-brand" href="{{ route('admin.dashboard') }}"><i></i>Admin Kemenham</a>
  <nav>
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a href="{{ route('admin.notifications') }}">Notifikasi</a>
    <a href="{{ route('admin.profile') }}">Profil</a>
    <span>{{ $adminName }}</span>
    <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form>
  </nav>
</header>
