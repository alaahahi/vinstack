<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $appName = config('app.name', 'KAML KAMAL');
        $appUrl = rtrim(config('app.url', url('/')), '/');
        $seoTitle = $appName . ' — منصة موثوقة لتجار المركبات والشحن';
        $seoDescription = 'KAML KAMAL — منصة احترافية سريعة وآمنة وموثوقة لإدارة المركبات والشحن وتجار السيارات. تجربة سلسة، بيانات دقيقة، وثقة في كل خطوة. | Fast, safe, and reliable vehicle & shipping dealer platform you can trust.';
        $ogImage = $appUrl . '/images/og-share.jpg';
        $canonicalUrl = $appUrl;
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="application-name" content="{{ $appName }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <link rel="icon" href="{{ $appUrl }}/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $appUrl }}/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $appUrl }}/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $appUrl }}/apple-touch-icon.png">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:locale" content="ar_SA">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <script>
        (function () {
            var t = localStorage.getItem('theme');
            document.documentElement.setAttribute('data-theme', t === 'dark' ? 'dark' : 'light');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
