{{-- resources/views/components/global-loader.blade.php --}}
<div id="globalLoader" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999; pointer-events: none;">
    {{-- Backdrop --}}
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); pointer-events: auto;"></div>
    
    {{-- Loader Container --}}
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
        {{-- Animated Spinner --}}
        <div style="position: relative; width: 80px; height: 80px; margin: 0 auto;">
            <div style="position: absolute; width: 100%; height: 100%; border: 4px solid rgba(255, 255, 255, 0.2); border-radius: 50%;"></div>
            <div style="position: absolute; width: 100%; height: 100%; border: 4px solid transparent; border-top-color: #fff; border-right-color: #fff; border-radius: 50%; animation: loaderSpin 0.8s linear infinite;"></div>
            <div style="position: absolute; width: 70%; height: 70%; top: 15%; left: 15%; border: 3px solid transparent; border-bottom-color: rgba(255, 255, 255, 0.8); border-left-color: rgba(255, 255, 255, 0.8); border-radius: 50%; animation: loaderSpin 0.6s linear infinite reverse;"></div>
        </div>
        
        {{-- Loading Text --}}
        <div style="margin-top: 20px; color: white; font-weight: 500; font-size: 0.9rem; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">
            <span id="loaderMessage">Loading</span><span id="loaderDots">...</span>
        </div>
        
        {{-- Subtle Tip --}}
        <div style="margin-top: 10px; font-size: 0.7rem; color: rgba(255,255,255,0.7);">
            Please wait while we process your request
        </div>
    </div>
</div>

<style>
    @keyframes loaderSpin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @keyframes loaderPulse {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 1; }
    }
    
    #globalLoader {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    }
    
    /* Fade animations */
    .loader-fade-in {
        animation: fadeIn 0.2s ease-out forwards;
    }
    
    .loader-fade-out {
        animation: fadeOut 0.2s ease-out forwards;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
</style>