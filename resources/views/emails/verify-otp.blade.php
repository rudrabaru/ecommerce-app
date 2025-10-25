<!doctype html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify your email</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;color:#111111;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#ffffff;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;margin:0 auto;padding:24px;">
                    <tr>
                        <td style="font-size:16px;line-height:24px;">
                            <p style="margin:0 0 16px 0;">Hello,</p>
                            <p style="margin:0 0 20px 0;">Please verify your email address to complete your registration.</p>
                            
                            @if(!empty($verifyUrl))
                            <div style="text-align:center;margin:24px 0;">
                                <a href="{{ $verifyUrl }}" style="display:inline-block;background:#0d6efd;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;">Verify Your Email</a>
                            </div>
                            <p style="margin:0 0 16px 0;text-align:center;color:#666;">Or use the verification code below:</p>
                            @endif
                            
                            <div style="background:#f8f9fa;padding:16px;border-radius:6px;text-align:center;margin:16px 0;">
                                <p style="margin:0 0 8px 0;color:#666;font-size:14px;">Your verification code:</p>
                                <p style="margin:0;font-size:24px;font-weight:bold;letter-spacing:2px;color:#111;">{{ $code }}</p>
                            </div>
                            
                            <p style="margin:0; color:#555;font-size:14px;">This code expires in 15 minutes.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

