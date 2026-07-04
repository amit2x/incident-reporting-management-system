<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="auth-check" content="{{ auth()->check() ? '1' : '0' }}">

    {{-- Dynamic Title --}}
    <title>@yield('title', 'SAMANVAY') - Incident Reporting & Management</title>

    {{-- SEO Meta --}}
    <meta name="description" content="@yield('meta_description', 'SAMANVAY- Enterprise Incident Reporting & Management System')">
    <meta name="keywords" content="SAMANVAY, incident management, reporting system, enterprise, safety, Airports Authority of India, NSCBI Airport, Kolkata Airport">
    <meta name="author" content="SAMANVAY">

    {{-- PWA Meta --}}
    <meta name="theme-color" content="#1a56db">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SAMANVAY">
    <meta name="user-authenticated" content="{{ auth()->check() ? 'true' : 'false' }}">


    {{-- Icons --}}
    <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('icons/apple-icon-57x57.png') }}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{ asset('icons/apple-icon-60x60.png') }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('icons/apple-icon-72x72.png') }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('icons/apple-icon-76x76.png') }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('icons/apple-icon-114x114.png') }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('icons/apple-icon-120x120.png') }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('icons/apple-icon-144x144.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('icons/apple-icon-152x152.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-icon-180x180.png') }}">
    
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/android-icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('icons/favicon-96x96.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('icons/favicon-16x16.png') }}">
    
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ asset('icons/ms-icon-144x144.png') }}">
    <meta name="theme-color" content="#ffffff">

    
    
    <!--<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">-->
    <!--<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">-->
    <!--<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">-->

    @include('layouts.partials.styles.icons')

    {{-- Vite Assets --}}
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    {{-- Core Styles --}}
    @include('layouts.partials.styles.core')

    {{-- Component Styles --}}
    @include('layouts.partials.styles.components')

    {{-- Layout Styles --}}
    @include('layouts.partials.styles.layout')

    {{-- Animation Styles --}}
    @include('layouts.partials.styles.animations')
    @include('layouts.partials.styles.loader')

    {{-- Responsive Styles --}}
    @include('layouts.partials.styles.responsive')

    @stack('styles')
</head>
<body>
    <div id="app" class="app-wrapper">

        {{-- ========================================== --}}
        {{-- DESKTOP SIDEBAR (Visible on Desktop Only) --}}
        {{-- ========================================== --}}
        @auth
            @include('layouts.partials.desktop.sidebar')
        @endauth

        {{-- ========================================== --}}
        {{-- MAIN CONTENT AREA --}}
        {{-- ========================================== --}}
        <div class="main-content @auth content-with-sidebar @else content-full @endauth">

            {{-- Desktop Top Navigation Bar --}}
            @include('layouts.partials.desktop.topbar')

            {{-- Page Content --}}
            <main class="page-content">
                <div class="container-fluid px-3 px-md-4">

                    {{-- Breadcrumbs --}}
                    @if(isset($breadcrumbs) || View::hasSection('breadcrumbs'))
                        <div class="py-2">
                            @include('layouts.partials.components.breadcrumbs')
                        </div>
                    @endif

                    {{-- Alert Messages --}}
                    @include('layouts.partials.components.alerts')

                    {{-- Main Yield Content --}}
                    @yield('content')

                </div>
            </main>

            {{-- Desktop Footer --}}
            @include('layouts.partials.components.footer')
        </div>

        {{-- ========================================== --}}
        {{-- MOBILE BOTTOM NAVIGATION (Android Style) --}}
        {{-- ========================================== --}}
        @auth
            @include('layouts.partials.mobile.bottom-nav')
        @else
            @include('layouts.partials.mobile.bottom-nav-guest')
        @endauth

        {{-- ========================================== --}}
        {{-- MOBILE SIDEBAR DRAWER --}}
        {{-- ========================================== --}}
        @include('layouts.partials.mobile.drawer')

        {{-- ========================================== --}}
        {{-- FLOATING ACTION BUTTON (Mobile) --}}
        {{-- ========================================== --}}
        @auth
            @include('layouts.partials.mobile.fab')
        @endauth

        {{-- ========================================== --}}
        {{-- BACK TO TOP BUTTON --}}
        {{-- ========================================== --}}
        @include('layouts.partials.components.back-to-top')

    {{-- Global Loader --}}
    @include('components.global-loader')
    </div>
    
    

    
    {{-- Loader Scripts --}}
    @push('scripts')
    <script src="{{ asset('js/global-loaders.js') }}"></script>
    @endpush

    {{-- Core Scripts --}}
    @include('layouts.partials.scripts.icons')
    @include('layouts.partials.scripts.core')
    @auth
    <script>
    // Register Firebase Service Worker
    if ('serviceWorker' in navigator && 'Notification' in window) {
        // Only register if not already registered
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            const hasFirebaseSW = registrations.some(reg => reg.scope.includes('firebase'));

            if (!hasFirebaseSW) {
                navigator.serviceWorker.register('/firebase-messaging-sw.js')
                    .then(function(registration) {
                        console.log('Firebase Service Worker registered:', registration.scope);
                    })
                    .catch(function(error) {
                        console.error('Service Worker registration failed:', error);
                    });
            }
        });
    }
    </script>
    @endauth
    @stack('scripts')
    
    


</body>
</html>
