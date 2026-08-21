@php
    $today = now();
    $isNewYearOverlay = ($today->month == 1 && $today->day <= 5);
@endphp

@if($isNewYearOverlay)
    <div id="newYearOverlay" class="new-year-overlay">
        <div id="nyMessage" class="ny-message">
            🎉 Happy New Year {{ now()->year }}!<br>
            Welcome to <strong>SAMANVAY</strong>
        </div>
    </div>
@endif