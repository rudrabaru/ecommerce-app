<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountVerificationChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public bool $verified;
    public ?string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, bool $verified, ?string $reason = null)
    {
        $this->user = $user;
        $this->verified = $verified;
        $this->reason = $reason;
    }
}
