<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Not Found - {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss'])
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-6 text-center">
                <div class="mb-4">
                    <i class="fas fa-search fa-4x text-warning"></i>
                </div>
                <h1 class="display-4 fw-bold">404</h1>
                <h5 class="text-muted mb-4">Page Not Found</h5>
                <p class="text-muted mb-4">The page you're looking for doesn't exist or has been moved.</p>
                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                    <i class="fas fa-home me-1"></i>Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>