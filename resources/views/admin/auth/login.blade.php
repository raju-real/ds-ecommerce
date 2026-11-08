@php($siteName = setting('site_name', config('app.name')))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · {{ $siteName }} Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}" rel="stylesheet">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-side">
            <div class="d-flex align-items-center gap-2 fw-bold fs-5">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width:40px;height:40px;background:rgba(255,255,255,.12)"><i class="bi bi-bag-check-fill"></i></span>
                {{ $siteName }}
            </div>
            <div>
                <h1 class="fw-bold display-6 mb-3" style="letter-spacing:-.02em">Run your dropshipping store from one place.</h1>
                <p class="opacity-75 fs-6 mb-0">Manage products and vendors, forward orders, and track profit on every sale.</p>
            </div>
            <div class="d-flex gap-4 small opacity-75">
                <span><i class="bi bi-lightning-charge me-1"></i>Cache-first</span>
                <span><i class="bi bi-shield-check me-1"></i>OTP-verified orders</span>
                <span><i class="bi bi-graph-up me-1"></i>Profit tracking</span>
            </div>
        </div>
        <div class="auth-form">
            <div class="inner">
                <h2 class="fw-bold mb-1">Welcome back 👋</h2>
                <p class="text-muted mb-4">Sign in to the admin panel</p>
                <form method="post" action="{{ route('admin.login.submit') }}" data-validate novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control form-control-lg @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="admin@example.com" required maxlength="150" autofocus autocomplete="username">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="Enter your password" required maxlength="100" autocomplete="current-password">
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                        <label class="form-check-label small" for="remember">Keep me signed in</label>
                    </div>
                    <button class="btn btn-primary btn-lg w-100">Sign in <i class="bi bi-arrow-right ms-1"></i></button>
                </form>
                <p class="text-center small text-muted mt-4 mb-0"><a href="{{ route('home') }}" class="text-muted"><i class="bi bi-arrow-left me-1"></i>Back to store</a></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="{{ asset('js/ui.js') }}?v={{ filemtime(public_path('js/ui.js')) }}"></script>
    <script src="{{ asset('js/validate.js') }}?v={{ filemtime(public_path('js/validate.js')) }}"></script>
</body>
</html>
