<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Forbidden - {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss'])
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-6 text-center">
                <div class="mb-4">
                    <i class="fas fa-lock fa-4x text-danger"></i>
                </div>
                <h1 class="display-4 fw-bold">403</h1>
                <h5 class="text-muted mb-4">Access Forbidden</h5>
                <p class="text-muted mb-4">You don't have permission to access this resource.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">
                        <i class="fas fa-home me-1"></i>Go to Dashboard
                    </a>
                    <a href="javascript:history.back()" class="btn btn-light">
                        <i class="fas fa-arrow-left me-1"></i>Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>