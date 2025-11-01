<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kode OTP Reset PIN</title>
    <style>
        @media (prefers-color-scheme: dark) {

            body,
            .wrapper {
                background: #0b0b0b !important;
            }

            .card {
                background: #111 !important;
                border-color: #222 !important;
            }

            .muted,
            .footnote {
                color: #a1a1aa !important;
            }

            .heading,
            .text {
                color: #e5e7eb !important;
            }

            .code {
                background: #18181b !important;
                color: #e5e7eb !important;
            }

            .badge {
                background: #1f2937 !important;
                color: #e5e7eb !important;
                border-color: #374151 !important;
            }
        }
    </style>
</head>

<body style="margin:0;padding:0;background:#f6f9fc;">
    <!-- Preheader -->
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        Kode OTP untuk reset PIN akun {{ config('app.name') }}. Berlaku {{ $ttlSeconds ?? 300 }} detik.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f9fc;">
        <tr>
            <td align="center" style="padding:24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="wrapper"
                    style="max-width:600px;">
                    <tr>
                        <td>
                            <!-- Card -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="card"
                                style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                <!-- Header -->
                                <tr>
                                    <td style="padding:20px 24px;border-bottom:1px solid #eef2f7;">
                                        <table role="presentation" width="100%">
                                            <tr>
                                                <td>
                                                    <div class="heading"
                                                        style="font:600 18px ui-sans-serif, -apple-system, Segoe UI, Roboto, Helvetica, Arial; color:#111827;">
                                                        {{ config('app.name', 'PPOB System') }}
                                                    </div>
                                                    <div class="muted"
                                                        style="margin-top:2px;font:400 12px ui-sans-serif, -apple-system, Segoe UI, Roboto, Helvetica, Arial;color:#6b7280;">
                                                        Reset PIN
                                                    </div>
                                                </td>
                                                <td align="right">
                                                    <span class="badge"
                                                        style="display:inline-block;font:600 11px ui-sans-serif, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
                                   color:#374151;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:999px;
                                   padding:6px 10px;letter-spacing:.02em;">
                                                        OTP
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Body -->
                                <tr>
                                    <td style="padding:24px;">
                                        <div class="text"
                                            style="font:400 14px/1.6 ui-sans-serif, -apple-system, Segoe UI, Roboto, Helvetica, Arial;color:#111827;">
                                            Halo <strong>{{ $name ?? $email ?? 'Pengguna' }}</strong>,<br><br>
                                            Kami menerima permintaan untuk mereset <strong>kode PIN</strong> akun Anda.
                                            Gunakan kode di bawah ini untuk melanjutkan proses reset PIN.
                                        </div>

                                        <!-- OTP -->
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            style="margin:20px 0 8px 0;">
                                            <tr>
                                                <td align="center">
                                                    <div class="code"
                                                        style="display:inline-block;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;
                                      padding:14px 20px;font:700 28px/1.2 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;
                                      letter-spacing:.18em;color:#0f172a;">
                                                        {{ $otp ?? $code ?? '0000' }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- TTL / Info -->
                                        <div class="muted"
                                            style="text-align:center;margin-top:6px;font:500 12px ui-sans-serif, -apple-system, Segoe UI, Roboto, Helvetica, Arial;color:#6b7280;">
                                            Kode berlaku {{ $ttlSeconds ?? 300 }} detik ({{ floor(($ttlSeconds ?? 300)/60) }} menit). Jangan bagikan kepada siapa pun.
                                        </div>

                                        <!-- Divider -->
                                        <div
                                            style="height:1px;background:linear-gradient(to right,transparent,#eaeaea,transparent);margin:24px 0;">
                                        </div>

                                        <!-- Notes -->
                                        <div class="text"
                                            style="font:400 13px/1.6 ui-sans-serif, -apple-system, Segoe UI, Roboto, Helvetica, Arial;color:#374151;">
                                            • Tim kami tidak akan pernah meminta kode OTP melalui telepon, chat, atau media sosial.<br>
                                            • Abaikan email ini jika Anda tidak meminta reset PIN.
                                        </div>
                                    </td>
                                </tr>

                                <!-- Footer -->
                                <tr>
                                    <td style="padding:18px 24px;border-top:1px solid #eef2f7;" align="center">
                                        <div class="footnote"
                                            style="font:400 12px ui-sans-serif, -apple-system, Segoe UI, Roboto, Helvetica, Arial;color:#9ca3af;">
                                            Email otomatis
                                            @if(!empty($supportUrl) || !empty($supportEmail))
                                            <br>
                                            Butuh bantuan?
                                            @if(!empty($supportUrl))
                                            <a href="{{ $supportUrl }}" style="color:#6b7280;text-decoration:underline;">Pusat
                                                Bantuan</a>
                                            @endif
                                            @if(!empty($supportEmail))
                                            @if(!empty($supportUrl)) · @endif
                                            <a href="mailto:{{ $supportEmail }}"
                                                style="color:#6b7280;text-decoration:underline;">{{ $supportEmail }}</a>
                                            @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <!-- /Card -->
                        </td>
                    </tr>
                </table>

                <!-- Legal tiny -->
                <div
                    style="margin-top:12px;font:400 11px ui-sans-serif, -apple-system, Segoe UI, Roboto, Helvetica, Arial;color:#9ca3af;">
                    © {{ date('Y') }} {{ config('app.name','PPOB System') }}
                </div>
            </td>
        </tr>
    </table>
</body>

</html>