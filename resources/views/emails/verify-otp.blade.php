<p>Hello,</p>
<p>Your verification code is: <strong>{{ $code }}</strong></p>
@isset($verifyUrl)
<p>Or click the link to verify instantly:</p>
<p><a href="{{ $verifyUrl }}" target="_blank" rel="noopener">Verify your email</a></p>
@endisset
<p>This code expires in 10 minutes.</p>

