{{-- Celebration Styles --}}
<style>
/* ============================================================
   CELEBRATION BANNER - BASE STYLES
   ============================================================ */
.celebration-banner {
    position: relative;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    z-index: 9999;
    animation: celebrationFadeSlideDown 0.6s ease-out;
    background: #ffffff; /* Fallback background */
}

.celebration-banner .celebration-content {
    position: relative;
    z-index: 2;
    max-width: 100%;
    min-height: 82px;
    margin: 150px auto;
    padding: 12px 55px 12px 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 25px;
}

.celebration-banner .celebration-close {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    width: 30px;
    height: 30px;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.06);
    color: #333;
    font-size: 22px;
    font-weight: 400;
    line-height: 28px;
    cursor: pointer;
    transition: background 0.2s ease, transform 0.2s ease;
    z-index: 10;
}

.celebration-banner .celebration-close:hover {
    background: rgba(0, 0, 0, 0.14);
    transform: translateY(-50%) scale(1.08);
}

/* ----- Tricolour Line ----- */
.celebration-banner .tricolour-line {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    display: flex;
    z-index: 1;
}

.celebration-banner .tricolour-saffron { width: 33.33%; background: #ff9933; }
.celebration-banner .tricolour-white   { width: 33.33%; background: #ffffff; }
.celebration-banner .tricolour-green   { width: 33.33%; background: #138808; }

/* ----- Indian Flag ----- */
.celebration-banner .indian-flag-wrapper {
    display: flex;
    align-items: flex-end;
    flex-shrink: 0;
    padding-left: 4px;
}

.celebration-banner .indian-flag {
    width: 76px;
    height: 51px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.12);
    border-radius: 2px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
    transform-origin: left center;
    animation: flagWave 2.8s ease-in-out infinite;
}

.celebration-banner .flag-saffron { height: 33.33%; background: #ff9933; }
.celebration-banner .flag-white {
    height: 33.33%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
}
.celebration-banner .flag-green { height: 33.33%; background: #138808; }

.celebration-banner .ashoka-chakra {
    color: #000080;
    font-size: 23px;
    line-height: 1;
    display: inline-block;
    animation: chakraRotate 8s linear infinite;
}

.celebration-banner .flag-pole {
    width: 3px;
    height: 58px;
    margin-left: 2px;
    background: #777;
    border-radius: 2px;
}

/* ----- Celebration Text ----- */
.celebration-banner .celebration-text {
    text-align: center;
    min-width: 300px;
}

.celebration-banner .celebration-title {
    font-size: 24px;
    font-weight: 800;
    line-height: 1.2;
    color: #161616;
    letter-spacing: 0.2px;
    text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
}

.celebration-banner .celebration-icon {
    display: inline-block;
    margin: 0 5px;
    font-size: 20px;
    animation: iconPulse 2s ease-in-out infinite;
}

.celebration-banner .celebration-subtitle {
    margin-top: 5px;
    font-size: 13px;
    font-weight: 600;
    color: #444;
    letter-spacing: 0.3px;
}

.celebration-banner .celebration-subtitle span {
    margin: 0 4px;
    color: #ff9933;
    font-weight: 800;
}

.celebration-banner .celebration-brand {
    margin-top: 3px;
    font-size: 12px;
    font-weight: 700;
    color: #555;
}

/* ----- Celebration Date ----- */
.celebration-banner .celebration-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-left: 20px;
    border-left: 1px solid rgba(0, 0, 0, 0.12);
    color: #222;
    line-height: 1;
}

.celebration-banner .celebration-date strong {
    font-size: 29px;
    font-weight: 900;
}

.celebration-banner .celebration-date span {
    margin-top: 2px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.5px;
}

.celebration-banner .celebration-date small {
    margin-top: 4px;
    font-size: 8px;
    color: #777;
}

/* ----- Celebration Particles ----- */
.celebration-banner .celebration-particles {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 0;
}

.celebration-banner .celebration-particles span {
    position: absolute;
    top: -25px;
    font-size: 13px;
    opacity: 0;
    animation: celebrationFall 5s linear infinite;
}

.celebration-banner .celebration-particles span:nth-child(1)  { left: 7%;  animation-delay: 0s; }
.celebration-banner .celebration-particles span:nth-child(2)  { left: 16%; animation-delay: 1.1s; }
.celebration-banner .celebration-particles span:nth-child(3)  { left: 27%; animation-delay: 2.2s; }
.celebration-banner .celebration-particles span:nth-child(4)  { left: 39%; animation-delay: 0.7s; }
.celebration-banner .celebration-particles span:nth-child(5)  { left: 52%; animation-delay: 2.8s; }
.celebration-banner .celebration-particles span:nth-child(6)  { left: 64%; animation-delay: 1.5s; }
.celebration-banner .celebration-particles span:nth-child(7)  { left: 73%; animation-delay: 3.2s; }
.celebration-banner .celebration-particles span:nth-child(8)  { left: 82%; animation-delay: 0.9s; }
.celebration-banner .celebration-particles span:nth-child(9)  { left: 90%; animation-delay: 2.4s; }
.celebration-banner .celebration-particles span:nth-child(10) { left: 96%; animation-delay: 1.8s; }

/* ----- Banner Variants ----- */
.celebration-banner.independence-banner,
.celebration-banner.republic-banner {
    background: linear-gradient(135deg, rgba(255, 153, 51, 0.15) 0%, rgba(255, 255, 255, 0.98) 45%, rgba(255, 255, 255, 0.98) 55%, rgba(19, 136, 8, 0.15) 100%);
}

.celebration-banner.newyear-banner {
    background: linear-gradient(135deg, #0b0e1a 0%, #1a1f35 100%);
    border-bottom: 2px solid #f7d44a;
}

.celebration-banner.newyear-banner .celebration-title {
    color: #f7e05e;
    text-shadow: 0 0 20px rgba(247, 212, 74, 0.3);
}

.celebration-banner.newyear-banner .celebration-subtitle {
    color: #b9c2e0;
}

.celebration-banner.newyear-banner .celebration-brand {
    color: #aab4d6;
}

.celebration-banner.newyear-banner .celebration-date {
    border-left-color: rgba(255, 255, 255, 0.15);
    color: #ddd;
}

.celebration-banner.newyear-banner .celebration-close {
    background: rgba(255, 255, 255, 0.08);
    color: #eee;
}

.celebration-banner.newyear-banner .celebration-close:hover {
    background: rgba(255, 255, 255, 0.18);
}

/* ----- Animations ----- */
@keyframes celebrationFadeSlideDown {
    from { opacity: 0; transform: translateY(-15px); }
    to   { opacity: 1; transform: translateY(0); }
}

@keyframes flagWave {
    0%, 100% { transform: perspective(250px) rotateY(-2deg) rotateZ(-1deg); }
    50%      { transform: perspective(250px) rotateY(4deg) rotateZ(1deg); }
}

@keyframes chakraRotate {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); }
    50%      { transform: scale(1.12); }
}

@keyframes celebrationFall {
    0%   { transform: translateY(-20px) rotate(0deg); opacity: 0; }
    15%  { opacity: 0.65; }
    80%  { opacity: 0.5; }
    100% { transform: translateY(110px) rotate(360deg); opacity: 0; }
}

/* ============================================================
   NEW YEAR OVERLAY
   ============================================================ */
.new-year-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    pointer-events: none;
    overflow: hidden;
}

.new-year-overlay .ny-message {
    position: absolute;
    top: 12%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.75);
    color: #fff;
    padding: 16px 28px;
    border-radius: 12px;
    font-size: 1.1rem;
    text-align: center;
    animation: fadeMessage 7s ease forwards;
    pointer-events: none;
    z-index: 10;
}

.new-year-overlay .flower {
    position: absolute;
    top: -12px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    opacity: 0.8;
    animation: fall linear infinite;
    z-index: 1;
}

.new-year-overlay .firework {
    position: absolute;
    bottom: -10px;
    width: 6px;
    height: 18px;
    background: linear-gradient(to top, #ffe066, #ff4d4d);
    animation: shootUp 1.6s ease-out forwards;
    z-index: 2;
}

.new-year-overlay .spark {
    position: absolute;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    animation: explode 1.2s ease-out forwards;
    z-index: 3;
}

@keyframes fall {
    from { transform: translateY(-10px) rotate(0deg); }
    to   { transform: translateY(110vh) rotate(360deg); }
}

@keyframes shootUp {
    from { transform: translateY(0); opacity: 1; }
    to   { transform: translateY(-65vh); opacity: 0; }
}

@keyframes explode {
    from { transform: translate(0, 0) scale(1); opacity: 1; }
    to   { transform: translate(var(--x), var(--y)) scale(0); opacity: 0; }
}

@keyframes fadeMessage {
    0%   { opacity: 0; transform: translate(-50%, -20px); }
    15%  { opacity: 1; transform: translate(-50%, 0); }
    85%  { opacity: 1; }
    100% { opacity: 0; transform: translate(-50%, -20px); }
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 991px) {
    .celebration-banner .celebration-content {
        min-height: 70px;
        padding: 10px 42px 10px 12px;
        gap: 10px;
    }
    
    .celebration-banner .indian-flag-wrapper {
        transform: scale(0.78);
        transform-origin: center;
    }
    
    .celebration-banner .indian-flag {
        width: 62px;
        height: 41px;
    }
    
    .celebration-banner .flag-pole {
        height: 48px;
    }
    
    .celebration-banner .ashoka-chakra {
        font-size: 19px;
    }
    
    .celebration-banner .celebration-text {
        min-width: 0;
        flex: 1;
    }
    
    .celebration-banner .celebration-title {
        font-size: 17px;
    }
    
    .celebration-banner .celebration-icon {
        font-size: 15px;
        margin: 0 2px;
    }
    
    .celebration-banner .celebration-subtitle {
        font-size: 10px;
        line-height: 1.35;
    }
    
    .celebration-banner .celebration-subtitle span {
        margin: 0 2px;
    }
    
    .celebration-banner .celebration-brand {
        font-size: 10px;
    }
    
    .celebration-banner .celebration-date {
        display: none;
    }
    
    .celebration-banner .celebration-close {
        right: 8px;
        width: 27px;
        height: 27px;
        font-size: 20px;
    }
}

@media (max-width: 576px) {
    .celebration-banner .celebration-content {
        gap: 5px;
        padding: 10px 36px 10px 8px;
    }
    
    .celebration-banner .indian-flag-wrapper {
        transform: scale(0.68);
        margin-left: -8px;
        margin-right: -8px;
    }
    
    .celebration-banner .celebration-title {
        font-size: 15px;
    }
    
    .celebration-banner .celebration-subtitle {
        font-size: 9px;
    }
    
    .celebration-banner .celebration-brand {
        font-size: 9px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .celebration-banner,
    .celebration-banner .indian-flag,
    .celebration-banner .ashoka-chakra,
    .celebration-banner .celebration-icon,
    .celebration-banner .celebration-particles span,
    .new-year-overlay .flower,
    .new-year-overlay .firework,
    .new-year-overlay .spark {
        animation: none !important;
    }
}
</style>