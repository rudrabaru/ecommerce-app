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

    /**
     * Determine if the user can update the order item status.
     */
    public function updateStatus(User $user, OrderItem $orderItem, string $newStatus): bool
    {
        return $orderItem->canTransitionTo($newStatus);
    }

    /**
     * Determine if the user can cancel the order item.
     */
    public function cancel(User $user, OrderItem $orderItem): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('provider')) {
            return $orderItem->provider_id === $user->id && 
                   $orderItem->order_status === OrderItem::STATUS_PENDING;
        }

        if ($user->hasRole('user')) {
            return $orderItem->order->user_id === $user->id && 
                   $orderItem->order_status === OrderItem::STATUS_PENDING;
        }

        return false;
    }
}
