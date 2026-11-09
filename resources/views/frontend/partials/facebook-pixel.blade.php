{{-- Facebook Pixel: base code + PageView + this page's events (ViewContent, AddToCart, Purchase …) from App\Services\FacebookPixel.
     Each event carries the event_id the server also sends to the Conversions API, so Facebook counts it once. --}}
@php
    $fbPixel = app(\App\Services\FacebookPixel::class);
    $fbPixel->rememberClick();
@endphp
@if($fbPixel->enabled())
    <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', @json($fbPixel->pixelId()));
    fbq('track', 'PageView');
    window.fbPixelFire = function (events) { (events || []).forEach(function (e) { if (window.fbq) fbq('track', e.event, e.data || {}, { eventID: e.id }); }); };
    window.fbPixelFire(@json($fbPixel->browserEvents()));
    </script>
    <noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $fbPixel->pixelId() }}&ev=PageView&noscript=1"></noscript>
@endif
