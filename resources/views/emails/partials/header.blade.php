<div style="padding: 24px 40px 16px; background: #ffffff; border-bottom: 3px solid #0d47a1;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <div style="display: inline-block; text-align: center;">

                    {{-- 1. Logo Container --}}
                    <div id="logo-container" style="margin-bottom: 8px;">
                        <img src="{{ isset($message) ? $message->embed(public_path('images/samanvay-email-logo.png')) : asset('images/samanvay-email-logo.png') }}"
                            alt="समन्वय"
                            style="width: auto; height: 100px; max-width: 100%; display: block; margin: 0 auto;"
                            onerror="this.style.display='none'; document.getElementById('text-branding').style.display='block';">
                    </div>


                    {{-- 2. Text Branding Container (Hidden by default, shown via onerror if logo fails) --}}
                    <div id="text-branding" style="display: none;">
                        <div style="font-weight: 700; font-size: 22px; color: #0d47a1; letter-spacing: 0.5px;">
                            समन्वय
                        </div>
                        <div style="font-size: 13px; color: #64748b; font-weight: 500; letter-spacing: 0.3px;">
                            Integrated Incident Reporting &amp; Management System
                        </div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                            Airports Authority of India
                        </div>
                    </div>

                </div>
            </td>
        </tr>
    </table>
</div>
