<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Kementerian Hak Asasi Manusia')</title>
  @yield('styles')
  <style>
    .app-flash{width:min(1120px,calc(100% - 32px));margin:14px auto 0;padding:11px 14px;border:1px solid #86efac;border-radius:9px;background:#f0fdf4;color:#166534;font:600 14px/1.5 system-ui,sans-serif}
    .app-flash.error{border-color:#fca5a5;background:#fef2f2;color:#991b1b}
  </style>
</head>
<body>
  @if (session('success'))
    <div class="app-flash" role="status">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="app-flash error" role="alert">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="app-flash error" role="alert">{{ $errors->first() }}</div>
  @endif
  @yield('content')
  @yield('scripts')
</body>
</html>
