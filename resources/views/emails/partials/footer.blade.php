<!-- Main Footer Wrapper -->
<div
    style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 32px 24px 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; text-align: center;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="table-layout: fixed;">
        <tr>


            <!-- Brand Signature Block -->
            <td align="center" style="padding-bottom: 20px;">

                {{-- 1. Footer Logo Container --}}

                <div id="footer-logo-container" style="margin-bottom: 8px;">

                    <img src="{{ isset($message) ? $message->embed(public_path('images/logo.png')) : asset('images/logo.png') }}"
                        alt="SAMANVAY Logo" style="max-width: 140px; height: auto; display: block; margin: 0 auto;"
                        onerror="this.style.display='none'; document.getElementById('footer-text-branding').style.display='block';">
                </div>

                {{-- 2. Text Branding Fallback (Hidden by default, shows only if logo fails) --}}
                <div id="footer-text-branding" style="display: none;">
                    <div style="font-weight: 700; font-size: 22px; color: #0d47a1; letter-spacing: 0.5px;">
                        समन्वय
                    </div>
                </div>

                {{-- 3. Sub-branding Text (Always visible to maintain system context) --}}
                <div style="font-size: 12px; color: #475569; font-weight: 600; letter-spacing: 0.2px; margin-top: 6px;">
                    Integrated Incident Reporting &amp; Management System
                </div>
                <div style="font-size: 11px; color: #64748b; font-weight: 500; margin-top: 2px;">
                    NSCBI Airport Kolkata &bull; Airports Authority of India
                </div>
            </td>

        </tr>

        <!-- Divider -->
        <tr>
            <td align="center" style="padding: 0 0 16px 0;">
                <table width="60" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="border-bottom: 2px solid #cbd5e1; font-size: 0; line-height: 0;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Quick Assistance Links -->
        <tr>
            <td align="center" style="padding-bottom: 24px;">
                <table cellpadding="0" cellspacing="0" border="0" style="display: inline-block;">
                    <tr>
                        <td style="font-size: 12px; color: #64748b;">
                            <span style="font-weight: 600; color: #475569;">📞 Quick Assistance:</span>
                            <span style="margin: 0 8px; color: #cbd5e1;">|</span>
                            <a href="mailto:team@ddg.nscbiairport.com"
                                style="color: #0d47a1; text-decoration: none; font-weight: 500;">
                                team@ddg.nscbiairport.com
                            </a>
                            <span style="margin: 0 8px; color: #cbd5e1;">|</span>
                            <a href="tel:+917633947548"
                                style="color: #0d47a1; text-decoration: none; font-weight: 500; white-space: nowrap;">
                                +91 7633947548
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Automated Message Notice -->
        <tr>
            <td align="center" style="padding-bottom: 16px;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td align="center"
                            style="background-color: #f1f5f9; border-radius: 6px; padding: 10px 16px; font-size: 11px; color: #64748b; line-height: 1.5; max-width: 480px;">
                            🔒 <strong>Automated System Notification</strong><br>
                            Please do not reply directly to this email address. The mailbox is not monitored.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Copyright & Legal -->
        <tr>
            <td align="center" style="font-size: 11px; color: #94a3b8; line-height: 1.6; padding-top: 8px;">
                &copy; {{ date('Y') }} SAMANVAY. All rights reserved.<br>
                Information Technology Department &bull; Airports Authority of India.
            </td>
        </tr>
    </table>

</div>
