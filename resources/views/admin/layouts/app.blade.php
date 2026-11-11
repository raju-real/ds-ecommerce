@php
    $siteName = setting('site_name', config('app.name'));
    $navActive = fn (...$patterns) => request()->routeIs(...$patterns) ? 'active' : '';
    $pending = $orderCounts['pending'] ?? 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') · {{ $siteName }} Admin</title>
    @if(setting('favicon'))<link rel="icon" type="image/png" href="{{ img_url(setting('favicon')) }}">@endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/css/bootstrap-datepicker3.standalone.min.css" rel="stylesheet">
    @stack('styles')
    <link href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}" rel="stylesheet">
</head>
<body>
    <script>try { if (localStorage.getItem('admin.sidebar') === 'collapsed') document.body.classList.add('sidebar-collapsed', 'no-anim'); } catch (e) {}</script>
    <aside class="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand" aria-label="{{ $siteName }} dashboard">
            @if(setting('logo_light'))
                <img src="{{ img_url(setting('logo_light')) }}" alt="{{ $siteName }}" class="brand-full">
                <span class="mark brand-mini">{{ strtoupper(mb_substr($siteName, 0, 1)) }}</span>
            @else
                <span class="mark"><i class="bi bi-bag-check-fill"></i></span><span class="brand-full text-truncate">{{ $siteName }}</span>
            @endif
        </a>
        <nav>
            <a class="nav-link {{ $navActive('admin.dashboard') }}" href="{{ route('admin.dashboard') }}" data-label="Dashboard"><i class="bi bi-grid-1x2"></i><span class="label">Dashboard</span></a>

            <div class="nav-title"><span>Sales</span></div>
            {{-- Notifications live in the top-bar bell (its "View all" opens the full list). --}}
            @include('admin.partials.nav-group', ['id' => 'orders', 'label' => 'Orders', 'icon' => 'bi-receipt',
                'badge' => '<span class="badge bg-warning text-dark '.($pending ? '' : 'd-none').'" data-pending-count title="Pending orders">'.$pending.'</span>',
                'items' => [
                    ['Order List', 'admin.orders.index', ['admin.orders.*']],
                    ['Coupons', 'admin.coupons.index', ['admin.coupons.*']],
                    ['Profit Reports', 'admin.reports', ['admin.reports']],
                ]])

            <div class="nav-title"><span>Catalog</span></div>
            @include('admin.partials.nav-group', ['id' => 'products', 'label' => 'Products', 'icon' => 'bi-box-seam', 'items' => [
                ['All products', 'admin.products.index', ['admin.products.index', 'admin.products.show', 'admin.products.edit']],
                ['Add product', 'admin.products.create', ['admin.products.create']],
                ['Featured products', 'admin.products.featured', ['admin.products.featured']],
                ['Categories', 'admin.categories.index', ['admin.categories.index', 'admin.categories.create', 'admin.categories.edit']],
                ['Sort categories', 'admin.categories.sort', ['admin.categories.sort*']],
                ['Subcategories', 'admin.subcategories.index', ['admin.subcategories.*']],
                ['Brands', 'admin.brands.index', ['admin.brands.*']],
                ['Short links', 'admin.short-links.index', ['admin.short-links.*']],
            ]])
            <a class="nav-link {{ $navActive('admin.vendors.*') }}" href="{{ route('admin.vendors.index') }}" data-label="Vendors"><i class="bi bi-shop"></i><span class="label">Vendors</span></a>

            <div class="nav-title"><span>Website</span></div>
            @include('admin.partials.nav-group', ['id' => 'website', 'label' => 'Website', 'icon' => 'bi-window', 'items' => [
                ['Blog', 'admin.blogs.index', ['admin.blogs.*']],
                ['Sliders', 'admin.sliders.index', ['admin.sliders.*']],
            ]])

            <div class="nav-title"><span>Settings</span></div>
            @include('admin.partials.nav-group', ['id' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear', 'items' => [
                ['Districts & charges', 'admin.districts.index', ['admin.districts.*']],
                ['Thanas', 'admin.thanas.index', ['admin.thanas.*']],
                ['Site settings', 'admin.settings', ['admin.settings', 'admin.settings.*']],
                ['Cache', 'admin.cache', ['admin.cache', 'admin.cache.*']],
            ]])
        </nav>
    </aside>
    <div class="backdrop"></div>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn" data-toggle-sidebar title="Collapse / expand menu" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
            <form action="{{ route('admin.orders.index') }}" class="d-none d-md-block" style="width: 320px" role="search">
                <div class="input-group">
                    <span class="input-group-text border-end-0 bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control border-start-0 ps-0" placeholder="Search order no, mobile, name…" maxlength="100" value="{{ request()->routeIs('admin.orders.index') && is_string(request('q')) ? request('q') : '' }}">
                </div>
            </form>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm d-none d-sm-inline-flex align-items-center gap-1"><i class="bi bi-plus-lg"></i> Product</a>
                <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="btn btn-warning btn-sm d-inline-flex align-items-center gap-1 pending-btn" title="Orders waiting for you">
                    <i class="bi bi-hourglass-split"></i><span class="d-none d-md-inline">Pending Orders</span><span class="badge bg-dark" data-pending-count>{{ $pending }}</span>
                </a>
                @include('admin.notifications.bell')
                <form method="post" action="{{ route('admin.cache.clear') }}">
                    @csrf
                    <button class="icon-btn" title="Refresh cache" aria-label="Refresh cache"><i class="bi bi-arrow-repeat"></i></button>
                </form>
                <a href="{{ route('home') }}" target="_blank" class="icon-btn" title="View store" aria-label="View store"><i class="bi bi-box-arrow-up-right"></i></a>
                <div class="dropdown">
                    <button class="btn p-0 d-flex align-items-center gap-2 border-0" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="d-none d-md-block text-start lh-sm"><span class="fw-semibold d-block">{{ auth()->user()->name }}</span><small class="text-muted">Administrator</small></span>
                        <i class="bi bi-chevron-down small text-muted d-none d-md-block"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius: 12px">
                        <li><a class="dropdown-item py-2" href="{{ route('admin.profile') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item py-2" href="{{ route('admin.settings') }}"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="dropdown-item py-2 text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content">
            @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm d-flex gap-2" style="border-radius: 12px">
                    <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                    <div><strong>Please fix the following:</strong><ul class="mb-0 mt-1 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <script>
        window.ADMIN_FLASH = { success: @json(session('success')), error: @json(session('error')) };
        window.ADMIN_NOTIFY = { url: @json(route('admin.notifications.summary')), latestId: @json($notify['latest']->first()?->id) };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
    <script src="{{ asset('js/ui.js') }}?v={{ filemtime(public_path('js/ui.js')) }}"></script>
    @stack('scripts')
    <script src="{{ asset('js/validate.js') }}?v={{ filemtime(public_path('js/validate.js')) }}"></script>
    <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
    @stack('page-scripts')
</body>
</html>
