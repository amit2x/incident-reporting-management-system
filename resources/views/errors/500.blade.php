<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 Server Error - {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss'])
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-6 text-center">
                <div class="mb-4">
                    <i class="fas fa-exclamation-circle fa-4x text-danger"></i>
                </div>
                <h1 class="display-4 fw-bold">500</h1>
                <h5 class="text-muted mb-4">Internal Server Error</h5>
                <p class="text-muted mb-4">Something went wrong on our end. Please try again later.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">
                        <i class="fas fa-home me-1"></i>Go to Dashboard
                    </a>
                    <button onclick="location.reload()" class="btn btn-light">
                        <i class="fas fa-redo me-1"></i>Retry
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>