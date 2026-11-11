@extends('admin.layouts.app')

@section('title', 'Cache')

{{-- Cache checker: driver set in config/cache.php vs really used, live figures, write/read test, cached data groups.
     Data: App\Support\CacheHealth::report() (same as `php artisan cache:check`). --}}
@section('content')
    <div class="page-header">
        <div>
            <h1>Cache</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Cache</li></ol></nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.cache') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-repeat me-1"></i> Check again</a>
            <form method="post" action="{{ route('admin.cache.refresh') }}" onsubmit="return confirm('Refresh all cached site data? Pages rebuild from the database on their next visit (the first visits are a little slower).')">
                @csrf
                <button class="btn btn-primary"><i class="bi bi-lightning-charge me-1"></i> Refresh cached data</button>
            </form>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="small text-muted mb-1">Driver in use</div>
                <div class="fs-4 fw-bold" id="cacheDriver">{{ $r['label'] }}</div>
                <div class="small text-muted">Set in <code>config/cache.php</code>: <strong>{{ $r['requested'] }}</strong></div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="small text-muted mb-1">Status</div>
                @if($r['ok'])
                    <div class="fs-4 fw-bold text-success" id="cacheStatus"><i class="bi bi-check-circle me-1"></i>Working</div>
                @else
                    <div class="fs-4 fw-bold text-danger" id="cacheStatus"><i class="bi bi-exclamation-triangle me-1"></i>Needs attention</div>
                @endif
                <div class="small text-muted">Checked {{ now()->format('d M Y, h:i:s A') }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="small text-muted mb-1">Write / read test</div>
                <div class="fs-4 fw-bold {{ $r['test']['ok'] ? '' : 'text-danger' }}" id="cacheTest">{{ $r['test']['ok'] ? $r['test']['ms'].' ms' : 'Failed' }}</div>
                <div class="small text-muted">{{ $r['test']['message'] }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                @if($x = $r['redis'])
                    <div class="small text-muted mb-1">Keys stored</div>
                    <div class="fs-4 fw-bold">{{ $x['ok'] ? number_format($x['keys']) : '—' }}</div>
                    <div class="small text-muted">in Redis database {{ $x['database'] }}</div>
                @elseif($f = $r['file'])
                    <div class="small text-muted mb-1">Cache files</div>
                    <div class="fs-4 fw-bold">{{ number_format($f['files']) }}{{ $f['more'] ? '+' : '' }}</div>
                    <div class="small text-muted">{{ $f['size'] }} on disk</div>
                @else
                    <div class="small text-muted mb-1">Keep cached for</div>
                    <div class="fs-4 fw-bold">{{ round($r['ttl'] / 60) }} min</div>
                @endif
            </div></div>
        </div>
    </div>

    @if($r['fallback'])
        <div class="p-3 rounded-3 mb-4 bg-soft-danger" id="cacheFallback">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>{{ ucfirst($r['requested']) }} is set in config/cache.php, but this server's PHP cannot use it</strong>, so the file cache is used instead. The site works, just without {{ ucfirst($r['requested']) }}.
            @if($r['fix'])<div class="small mt-1">Fix on the server: <code>{{ $r['fix'] }}</code></div>@endif
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5><i class="bi bi-hdd-stack me-1"></i> {{ $r['label'] }} details</h5></div>
                <div class="card-body">
                    <table class="table table-sm mb-0 cache-table">
                        <tbody>
                            <tr><th>Driver</th><td>{{ $r['label'] }} <span class="text-muted">({{ $r['used'] }})</span></td></tr>
                            @if($x = $r['redis'])
                                <tr><th>Connection</th><td>
                                    @if($x['ok'])<span class="badge badge-soft-success"><i class="bi bi-check-circle me-1"></i>Connected</span> ping {{ $x['ping_ms'] }} ms
                                    @else<span class="badge badge-soft-danger">Cannot connect</span> <span class="small text-danger">{{ $x['error'] }}</span>@endif
                                </td></tr>
                                <tr><th>Server</th><td><code>{{ $x['host'] }}:{{ $x['port'] }}</code>, database <strong>{{ $x['database'] }}</strong></td></tr>
                                <tr><th>Client</th><td>{{ $r['client'] }} <span class="text-muted small">(PHP extension {{ $x['extension'] ? 'loaded' : 'not loaded' }})</span></td></tr>
                                <tr><th>Key prefix</th><td><code>{{ $x['key_prefix'] }}</code></td></tr>
                                @if($x['ok'])
                                    <tr><th>Redis version</th><td>{{ $x['version'] }} <span class="text-muted small">· running for {{ $x['uptime'] }}</span></td></tr>
                                    <tr><th>Memory used</th><td>{{ $x['memory'] }} <span class="text-muted small">· limit: {{ $x['max_memory'] }}</span></td></tr>
                                    <tr><th>Keys in database {{ $x['database'] }}</th><td>{{ number_format($x['keys']) }}</td></tr>
                                    <tr><th>Hit rate</th><td>{{ $x['hit_rate'] !== null ? $x['hit_rate'].'%' : '—' }} <span class="text-muted small">· {{ number_format($x['hits']) }} hits, {{ number_format($x['misses']) }} misses (whole server, since it started)</span></td></tr>
                                    <tr><th>Connected clients</th><td>{{ $x['clients'] }}</td></tr>
                                @endif
                            @elseif($f = $r['file'])
                                <tr><th>Folder</th><td><code class="small">{{ $f['path'] }}</code></td></tr>
                                <tr><th>Writable</th><td>{!! $f['writable'] ? '<span class="badge badge-soft-success">Yes</span>' : '<span class="badge badge-soft-danger">No — the web server cannot write here</span>' !!}</td></tr>
                                <tr><th>Files</th><td>{{ number_format($f['files']) }}{{ $f['more'] ? '+' : '' }}</td></tr>
                                <tr><th>Size</th><td>{{ $f['size'] }}</td></tr>
                            @endif
                            <tr><th>Keep cached for</th><td>{{ round($r['ttl'] / 60) }} min <span class="text-muted small">· lists and filters {{ round($r['list_ttl'] / 60) }} min</span></td></tr>
                            <tr><th>App key prefix</th><td><code>{{ $r['prefix'] }}</code></td></tr>
                            <tr><th>PHP</th><td>{{ $r['php'] }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5><i class="bi bi-collection me-1"></i> Cached site data</h5></div>
                <div class="card-body">
                    <p class="small text-muted">Each group refreshes by itself when you change something in the admin (e.g. saving a product refreshes Products).</p>
                    <table class="table table-sm mb-0 cache-table" id="cacheGroups">
                        <thead><tr><th>Data</th><th>Last refreshed</th><th class="text-end">Version</th></tr></thead>
                        <tbody>
                            @foreach($r['groups'] as $g)
                                <tr>
                                    <td>{{ $g['label'] }}</td>
                                    <td>@if($g['at'])<span title="{{ $g['at']->format('d M Y, h:i:s A') }}">{{ $g['at']->diffForHumans() }}</span>@else<span class="text-muted">—</span>@endif</td>
                                    <td class="text-end"><code class="small">{{ $g['version'] ?? '—' }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="small text-muted mt-3"><i class="bi bi-info-circle me-1"></i>The cache driver and Redis server are set in <code>config/cache.php</code>. After changing them, run <code>php artisan config:cache</code> on the server. The same check runs with <code>php artisan cache:check</code>.</div>
@endsection

@push('styles')
    <style>
        .cache-table th { width: 38%; font-weight: 600; color: #475569; font-size: .85rem; }
        .cache-table td { font-size: .88rem; }
        .cache-table thead th { width: auto; }
    </style>
@endpush
