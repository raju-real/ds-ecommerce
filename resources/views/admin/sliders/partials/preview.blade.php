{{-- Small copy of the home page banner (Sliders list): the same partial the website uses, so it looks exactly like the site.
     Expects $slide. Styles: admin.css .slide-preview --}}
<div class="slide-preview slide-preview-flat">
    <div class="carousel-item active d-block">
        <img src="{{ img_url($slide->image) }}" alt="{{ $slide->title }}" loading="lazy">
        @include('frontend.partials.hero-slide-content', ['preview' => true, 'link' => null])
    </div>
</div>
