<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Masuk — ATV Arsip</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body class="login-page"><div class="login-card"><div class="login-brand">ATV</div><h1>Selamat datang</h1><p>Masuk ke sistem arsip digital Kominfo Kota Batu.</p>
<form method="post" action="{{ route('login.store') }}">@csrf
<label>Email<input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@atv.test"></label>
<label>Kata sandi<input type="password" name="password" required placeholder="••••••••"></label>
@error('email')<div class="error">{{ $message }}</div>@enderror
<label class="check"><input type="checkbox" name="remember"> Ingat saya</label><button class="btn primary full">Masuk</button></form>
<small class="hint">Akun awal: admin@atv.test / password</small></div></body></html>
