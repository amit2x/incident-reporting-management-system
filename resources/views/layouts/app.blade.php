<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Dynamic Title --}}
    <title>@yield('title', 'IRMS') - Incident Reporting & Management</title>

    {{-- SEO Meta --}}
    <meta name="description" content="@yield('meta_description', 'Enterprise Incident Reporting & Management System')">
    <meta name="keywords" content="incident management, reporting system, enterprise, safety">
    <meta name="author" content="IRMS">

    {{-- PWA Meta --}}
    <meta name="theme-color" content="#1a56db">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="IRMS">
    <meta name="user-authenticated" content="{{ auth()->check() ? 'true' : 'false' }}">

    <link rel="manifest" href="{{ asset('menifest.json') }}">

    {{-- Icons --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icon-180.png') }}">



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

    </div>

    {{-- Core Scripts --}}
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
