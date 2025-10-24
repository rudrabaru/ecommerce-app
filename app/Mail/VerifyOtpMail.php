<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyOtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $code;

    public ?string $verifyUrl;

    public function __construct(string $code, ?string $verifyUrl = null)
    {
        $this->code = $code;
        $this->verifyUrl = $verifyUrl;
    }

    public function build()
    {
        return $this->subject('Verify your email')
            ->view('emails.verify-otp', ['code' => $this->code, 'verifyUrl' => $this->verifyUrl]);
    }
}
