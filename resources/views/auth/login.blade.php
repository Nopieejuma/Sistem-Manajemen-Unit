<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SARANA INDUSTRIAL POINT</title>
    <x-admin-head />
</head>
<body class="admin-site bg-surface min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-[440px] bg-white rounded-2xl shadow-sm border border-line p-8">

        <div class="mb-6">
            <a href="{{ route('customer.home') }}" aria-label="SARANA INDUSTRIAL POINT Beranda" class="inline-flex text-ink"><x-brand /></a>
            <p class="mt-3 text-xs font-semibold tracking-wider uppercase text-primary">Admin Portal</p>
        </div>

        <h1 class="text-2xl font-bold text-ink mb-1">Masuk ke Akun</h1>
        <p class="text-muted text-sm mb-6">Silakan masukkan username dan password Anda.</p>

        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label for="username" class="block text-sm font-semibold text-ink mb-1.5">Username Admin</label>
                <input type="text" id="username" name="username" autocomplete="username" value="{{ old('username') }}" required autofocus
                    placeholder="Masukkan username"
                    class="w-full px-3.5 py-2.5 bg-white border border-line rounded-lg text-ink text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                @error('username')
                    <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-ink mb-1.5">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required
                    placeholder="••••••••"
                    class="w-full px-3.5 py-2.5 bg-white border border-line rounded-lg text-ink text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                @error('password')
                    <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit"
                class="w-full mt-2 bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-xl transition duration-150 shadow-sm">
                Masuk
            </button>
        </form>

    </div>

</body>
</html>
