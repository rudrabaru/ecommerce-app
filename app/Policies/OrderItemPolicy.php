<?php

namespace App\Policies;

use App\Models\OrderItem;
use App\Models\User;

class OrderItemPolicy
{
    /**
     * Determine if the user can view the order item.
     */
    public function view(User $user, OrderItem $orderItem): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('provider')) {
            return $orderItem->provider_id === $user->id;
        }

        if ($user->hasRole('user')) {
            return $orderItem->order->user_id === $user->id;
        }

        return false;
    }

    // Tracking removed: no item status updates
    public function updateStatus(User $user, OrderItem $orderItem, string $newStatus): bool
    {
        return false;
    }

    // Tracking removed: no item cancellation
    public function cancel(User $user, OrderItem $orderItem): bool
    {
        return false;
    }
}
