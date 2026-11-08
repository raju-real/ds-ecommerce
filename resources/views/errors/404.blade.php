@extends('layouts.frontend')

@section('title', 'Page not found')
@section('description', 'The page you are looking for could not be found. Browse our products or search the shop.')
@section('robots', 'noindex, follow')

@section('content')
    <div class="container py-5">
        <div class="card-plain text-center mx-auto py-5 px-3" style="max-width: 560px">
            <div class="display-1 fw-bold" style="color: var(--brand); letter-spacing: -.04em">404</div>
            <h1 class="h3 fw-bold mb-2">Page not found</h1>
            <p class="text-muted mb-4">The page may have been moved or is no longer available. Try searching, or continue shopping.</p>
            <form action="{{ route('products.index') }}" method="get" class="d-flex gap-2 mb-4" role="search">
                <input type="search" name="q" class="form-control" placeholder="Search products…" maxlength="100" aria-label="Search products">
                <button class="btn btn-primary px-4" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="{{ route('home') }}" class="btn btn-outline-primary"><i class="bi bi-house me-1"></i> Home</a>
                <a href="{{ route('products.index') }}" class="btn btn-accent"><i class="bi bi-bag me-1"></i> Shop all products</a>
            </div>
        </div>
    </div>
@endsection
