{{-- Standalone error page: no database, cache or layout, so it still renders when those are the problem. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f6f7fb; color: #0f172a; padding: 16px; }
        .box { background: #fff; border-radius: 18px; box-shadow: 0 10px 30px -12px rgba(15, 23, 42, .18); padding: 40px 28px; max-width: 460px; width: 100%; text-align: center; }
        .code { font-size: 64px; font-weight: 800; color: #4f46e5; line-height: 1; letter-spacing: -.04em; }
        h1 { font-size: 22px; margin: 14px 0 8px; }
        p { color: #64748b; line-height: 1.6; margin: 0 0 24px; }
        a { display: inline-block; background: #4f46e5; color: #fff; text-decoration: none; padding: 12px 22px; border-radius: 10px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="box">
        <div class="code">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ $link ?? url('/') }}">{{ $linkText ?? 'Go to the home page' }}</a>
    </div>
</body>
</html>
