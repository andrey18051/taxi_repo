<link rel="canonical" href="{{ url()->current() }}" />
<meta property="og:type" content="website" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:locale" content="uk_UA" />
@if(!empty($seoTitle))
<meta property="og:title" content="{{ $seoTitle }}" />
<meta name="twitter:title" content="{{ $seoTitle }}" />
@endif
@if(!empty($seoDescription))
<meta property="og:description" content="{{ $seoDescription }}" />
<meta name="twitter:description" content="{{ $seoDescription }}" />
@endif
@php
    $seoImageUrl = $seoImageUrl ?? url(asset('img/logo.jpg'));
@endphp
<meta property="og:image" content="{{ $seoImageUrl }}" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:image" content="{{ $seoImageUrl }}" />
