@props([
    'url' => config('app.frontend_url', config('app.url')),
    'logo' => config('app.branding.logo_url') ?: env('MAIL_LOGO_URL'),
    'logoWidth' => (int) (config('app.branding.logo_width') ?: env('MAIL_LOGO_WIDTH', 180)),
    'logoHeight' => config('app.branding.logo_height') ?: env('MAIL_LOGO_HEIGHT', 'auto'),
    'backgroundColor' => '#ffffff',
    'textColor' => '#2c3e50',
    'borderColor' => '#e9ecef',
    'gradient' => false,
])

@php
    $appName = config('app.name', 'Mi Aplicación');
    $headerStyle = $gradient
        ? 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#ffffff;'
        : "background-color: {$backgroundColor}; color: {$textColor};";
    $nameColor = $gradient ? '#ffffff' : $textColor;

    // Asegura que el logo sea URL absoluta
    $logoUrl = null;
    if (!empty($logo)) {
        $logoUrl = preg_match('~^https?://~', $logo) ? $logo : url($logo);
    }
@endphp

<tr>
    <td style="{{ $headerStyle }} padding:30px 20px; text-align:center; border-bottom:2px solid {{ $borderColor }};">
        <a href="{{ $url }}" style="text-decoration:none; display:inline-block;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto;">
                @if ($logoUrl)
                    <tr>
                        <td align="center" style="padding-bottom:10px;">
                            <img
                                src="{{ $logoUrl }}"
                                alt="{{ $appName }}"
                                width="{{ $logoWidth }}"
                                @if($logoHeight !== 'auto') height="{{ (int) $logoHeight }}" @endif
                                style="
                                    display:block;
                                    border:0;
                                    outline:none;
                                    text-decoration:none;
                                    height:auto;
                                    max-width:100%;
                                    margin:0 auto;
                                "
                            >
                        </td>
                    </tr>
                @endif

                @if (!empty($appName))
                    <tr>
                        <td align="center">
                            <div style="
                                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                                font-size:20px;
                                font-weight:700;
                                letter-spacing:-0.3px;
                                color: {{ $nameColor }};
                                text-align:center;
                                margin-top:8px;
                            ">
                                {{ $slot ?: $appName }}
                            </div>
                        </td>
                    </tr>
                @endif
            </table>
        </a>
    </td>
</tr>
