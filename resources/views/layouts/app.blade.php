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
    <link rel="manifest" href="{{ asset('menifest.json') }}">

    {{-- Icons --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icon-180.png') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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

    @stack('scripts')
</body>
</html>
