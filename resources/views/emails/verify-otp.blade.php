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
                            <p style="margin:0 0 16px 0;">Your verification code is: <strong style="font-size:18px;">{{ $code }}</strong></p>
                            @if(!empty($verifyUrl))
                            <p style="margin:0 0 12px 0;">Or click the link to verify instantly:</p>
                            <p style="margin:0 0 20px 0;"><a href="{{ $verifyUrl }}" style="color:#0d6efd;">Verify your email</a></p>
                            @endif
                            <p style="margin:0; color:#555;">This code expires in 10 minutes.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
<p>Hello,</p>
<p>Your verification code is: <strong>{{ $code }}</strong></p>
@isset($verifyUrl)
<p>Or click the link to verify instantly:</p>
<p><a href="{{ $verifyUrl }}" target="_blank" rel="noopener">Verify your email</a></p>
@endisset
<p>This code expires in 10 minutes.</p>

