<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - KawasanIndo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-[440px] bg-white rounded-2xl shadow-sm border border-slate-100 p-8">

        <div class="flex items-center gap-3 mb-6">
            <div class="bg-blue-600 p-2.5 rounded-xl text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0h4m-4 4h4m-4 0H9m4 0v4"></path>
                </svg>
            </div>
            <div>
                <span class="font-bold text-slate-900 text-lg tracking-tight block leading-tight">KawasanIndo</span>
                <span class="text-[10px] font-semibold tracking-wider uppercase text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">Admin Portal</span>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-slate-900 mb-1">Masuk ke Akun</h1>
        <p class="text-slate-500 text-sm mb-6">Silakan masukkan username dan password Anda.</p>

        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label for="username" class="block text-sm font-semibold text-slate-700 mb-1.5">Username Admin</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus
                    placeholder="Masukkan username"
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition">
                @error('username')
                    <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required
                    placeholder="••••••••"
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition">
                @error('password')
                    <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit"
                class="w-full mt-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition duration-150 shadow-sm">
                Masuk
            </button>
        </form>

    </div>

</body>
</html>
