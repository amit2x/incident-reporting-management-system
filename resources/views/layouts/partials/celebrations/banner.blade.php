@php
    $today = now();
    $monthDay = $today->format('m-d');
    $isIndependence = ($monthDay === '08-15');
    $isRepublic = ($monthDay === '01-26');
    $isNewYearBanner = ($today->month == 1 && $today->day <= 5);
@endphp

@if($isIndependence)
    {{-- Independence Day Banner --}}
    <div id="independenceBanner" class="celebration-banner independence-banner mt-5" data-banner-id="independence">
        <div class="tricolour-line">
            <span class="tricolour-saffron"></span>
            <span class="tricolour-white"></span>
            <span class="tricolour-green"></span>
        </div>
        <div class="celebration-content">
            <div class="indian-flag-wrapper">
                <div class="indian-flag" aria-label="Indian Flag">
                    <span class="flag-saffron"></span>
                    <span class="flag-white"><span class="ashoka-chakra">☸</span></span>
                    <span class="flag-green"></span>
                </div>
                <div class="flag-pole"></div>
            </div>
            <div class="celebration-text">
                <div class="celebration-title">
                    <span class="celebration-icon">🇮🇳</span> Happy Independence Day <span class="celebration-icon">🇮🇳</span>
                </div>
                <div class="celebration-subtitle">Freedom to Report <span>•</span> Freedom to Respond <span>•</span> Freedom to Resolve</div>
                <div class="celebration-brand">— SAMANVAY</div>
            </div>
            <div class="celebration-date">
                <strong>15</strong><span>AUGUST</span><small>1947 — ∞</small>
            </div>
            <button type="button" class="celebration-close" onclick="closeCelebrationBanner('independenceBanner', 'independence')" aria-label="Close">&times;</button>
        </div>
        <div class="celebration-particles" aria-hidden="true">
            <span>🇮🇳</span><span>✦</span><span>🟠</span><span>✦</span><span>🟢</span><span>🇮🇳</span><span>✦</span><span>🟠</span><span>🟢</span><span>✦</span>
        </div>
    </div>
@endif

@if($isRepublic)
    {{-- Republic Day Banner --}}
    <div id="republicBanner" class="celebration-banner republic-banner" data-banner-id="republic">
        <div class="tricolour-line">
            <span class="tricolour-saffron"></span>
            <span class="tricolour-white"></span>
            <span class="tricolour-green"></span>
        </div>
        <div class="celebration-content">
            <div class="indian-flag-wrapper">
                <div class="indian-flag" aria-label="Indian Flag">
                    <span class="flag-saffron"></span>
                    <span class="flag-white"><span class="ashoka-chakra">☸</span></span>
                    <span class="flag-green"></span>
                </div>
                <div class="flag-pole"></div>
            </div>
            <div class="celebration-text">
                <div class="celebration-title">
                    <span class="celebration-icon">🇮🇳</span> Happy Republic Day <span class="celebration-icon">🇮🇳</span>
                </div>
                <div class="celebration-subtitle">Justice <span>•</span> Liberty <span>•</span> Equality <span>•</span> Fraternity</div>
                <div class="celebration-brand">— SAMANVAY</div>
            </div>
            <div class="celebration-date">
                <strong>26</strong><span>JANUARY</span><small>1950 — ∞</small>
            </div>
            <button type="button" class="celebration-close" onclick="closeCelebrationBanner('republicBanner', 'republic')" aria-label="Close">&times;</button>
        </div>
        <div class="celebration-particles" aria-hidden="true">
            <span>🇮🇳</span><span>✦</span><span>🟠</span><span>✦</span><span>🟢</span><span>🇮🇳</span><span>✦</span><span>🟠</span><span>🟢</span><span>✦</span>
        </div>
    </div>
@endif

@if($isNewYearBanner)
    {{-- New Year Banner --}}
    <div id="newYearBanner" class="celebration-banner newyear-banner" data-banner-id="newyear">
        <div class="celebration-content">
            <div style="font-size: 2.4rem; line-height: 1;">🎉</div>
            <div class="celebration-text">
                <div class="celebration-title">
                    <span class="celebration-icon">✨</span> Happy New Year {{ now()->year }} <span class="celebration-icon">✨</span>
                </div>
                <div class="celebration-subtitle">Fresh Start <span>•</span> New Goals <span>•</span> Great Achievements</div>
                <div class="celebration-brand">— SAMANVAY</div>
            </div>
            <div class="celebration-date">
                <strong>01</strong><span>JANUARY</span><small>{{ now()->year }}</small>
            </div>
            <button type="button" class="celebration-close" onclick="closeCelebrationBanner('newYearBanner', 'newyear')" aria-label="Close">&times;</button>
        </div>
        <div class="celebration-particles" aria-hidden="true">
            <span>🎆</span><span>✨</span><span>⭐</span><span>🎇</span><span>🌟</span><span>🎉</span><span>✨</span><span>⭐</span><span>🎆</span><span>🌟</span>
        </div>
    </div>
@endif