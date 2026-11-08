<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'UPCI New Zealand') }}</title>
    <meta name="description" content="United Pentecostal Church International New Zealand — churches, departments and regions across Aotearoa.">

    <!-- The globe from the UPCI NZ logo, on the brand green (2026-10-17).
         ?v= makes browsers drop the cached Laravel icon. -->
    <link rel="icon" href="/favicon.ico?v=2" sizes="48x48">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=2">
    <link rel="manifest" href="/site.webmanifest?v=2">
    <meta name="theme-color" content="#20411e">

    <!-- Link previews (Facebook, WhatsApp, Messenger, LinkedIn): their
         crawlers do not run JavaScript, so the share card has to be in the
         page as served. usePageMeta updates the title and description once
         the app loads. The card's source: .claude/design/brand-assets/. -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="UPCI New Zealand">
    <meta property="og:title" content="UPCI New Zealand">
    <meta property="og:description" content="United Pentecostal Church International New Zealand — churches, departments and regions across Aotearoa.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ url('/og-image.jpg') }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="The UPCI New Zealand globe, with the words UPCI New Zealand: churches, departments and regions across Aotearoa.">
    <meta property="og:locale" content="en_NZ">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ url('/og-image.jpg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,500i,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>
