<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'provider', 'user']);
    }

    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('provider')) {
            return $order->containsProvider($user->id);
        }

        if ($user->hasRole('user')) {
            return $order->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can create orders.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'provider']);
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('provider')) {
            return $order->containsProvider($user->id);
        }

        if ($user->hasRole('user')) {
            // Users can only update (cancel) their own pending orders
            return $order->user_id === $user->id && 
                   $order->order_status === Order::STATUS_PENDING;
        }

        return false;
    }

    /**
     * Determine if the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can update the order status.
     */
    public function updateStatus(User $user, Order $order, string $newStatus): bool
    {
        return $order->canTransitionTo($newStatus);
    }

    /**
     * Determine if the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('provider')) {
            return $order->containsProvider($user->id) && 
                   $order->order_status === Order::STATUS_PENDING;
        }

        if ($user->hasRole('user')) {
            return $order->user_id === $user->id && 
                   $order->order_status === Order::STATUS_PENDING;
        }

        return false;
    }
}
