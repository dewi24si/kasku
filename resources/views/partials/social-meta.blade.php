{{--
    Meta deskripsi, Open Graph, dan Twitter Card untuk pratinjau link. Dipakai oleh layout guest dan app.
    Di luar environment local, URL dipaksa https supaya pratinjau tetap jalan walau aplikasi di belakang proxy.
--}}
@php
    $siteTitle = 'Kasku – Catat keuangan cukup lewat chat';
    $siteDescription = 'Asisten keuangan pribadi berbasis AI. Catat pengeluaran lewat chat atau foto struk, pantau budget, dan lihat ringkasan keuanganmu.';
    $imageAlt = 'Kasku: catat keuangan cukup lewat chat';
    $toHttps = fn (string $absoluteUrl): string => app()->isLocal()
        ? $absoluteUrl
        : preg_replace('#^http://#i', 'https://', $absoluteUrl);
    $pageUrl = $toHttps(url()->current());
    $imageUrl = $toHttps(asset('og-image.png'));
@endphp
<meta name="description" content="{{ $siteDescription }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Kasku">
<meta property="og:title" content="{{ $siteTitle }}">
<meta property="og:description" content="{{ $siteDescription }}">
<meta property="og:url" content="{{ $pageUrl }}">
<meta property="og:image" content="{{ $imageUrl }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $imageAlt }}">
<meta property="og:locale" content="id_ID">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $siteTitle }}">
<meta name="twitter:description" content="{{ $siteDescription }}">
<meta name="twitter:image" content="{{ $imageUrl }}">
<meta name="twitter:image:alt" content="{{ $imageAlt }}">
