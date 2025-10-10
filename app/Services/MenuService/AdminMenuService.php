<?php

namespace App\Services\MenuService;

use Illuminate\Support\Facades\Route;

class AdminMenuService
{
    public function getMenu(): array
    {
        return [
            'Dashboard' => [
                [
                    'id' => 'dashboard',
                    'label' => 'Dashboard',
                    'route' => route('admin.dashboard'),
                    'icon' => 'lucide:layout-dashboard',
                    'active' => request()->routeIs('admin.dashboard')
                ]
            ],
            'User Management' => [
                [
                    'id' => 'users',
                    'label' => 'Users',
                    'route' => route('admin.users.index'),
                    'icon' => 'lucide:users',
                    'active' => request()->routeIs('admin.users.*')
                ],
                [
                    'id' => 'providers',
                    'label' => 'Providers',
                    'route' => route('admin.providers.index'),
                    'icon' => 'lucide:user-check',
                    'active' => request()->routeIs('admin.providers.*')
                ]
            ],
            'Content Management' => [
                [
                    'id' => 'products',
                    'label' => 'Products',
                    'route' => route('admin.products.index'),
                    'icon' => 'lucide:package',
                    'active' => request()->routeIs('admin.products.*')
                ],
                [
                    'id' => 'categories',
                    'label' => 'Categories',
                    'route' => route('admin.categories.index'),
                    'icon' => 'lucide:tag',
                    'active' => request()->routeIs('admin.categories.*')
                ],
                [
                    'id' => 'discounts',
                    'label' => 'Discount Codes',
                    'route' => route('admin.discounts.index'),
                    'icon' => 'lucide:percent',
                    'active' => request()->routeIs('admin.discounts.*')
                ]
            ],
            'Orders & Payments' => [
                [
                    'id' => 'orders',
                    'label' => 'Orders',
                    'route' => route('admin.orders.index'),
                    'icon' => 'lucide:shopping-cart',
                    'active' => request()->routeIs('admin.orders.*')
                ],
                [
                    'id' => 'payments',
                    'label' => 'Payments',
                    'route' => route('admin.payments.index'),
                    'icon' => 'lucide:credit-card',
                    'active' => request()->routeIs('admin.payments.*')
                ]
            ],
            'Settings' => [
                [
                    'id' => 'profile',
                    'label' => 'Profile',
                    'route' => route('admin.profile'),
                    'icon' => 'lucide:user',
                    'active' => request()->routeIs('admin.profile')
                ]
            ]
        ];
    }

    public function render(array $items): string
    {
        $html = '';
        foreach ($items as $item) {
            $html .= $this->renderMenuItem($item);
        }
        return $html;
    }

    private function renderMenuItem(array $item): string
    {
        $isActive = $item['active'] ? 'menu-item-active' : 'menu-item-inactive';
        $icon = $item['icon'] ?? 'lucide:circle';
        
        return sprintf(
            '<li class="menu-item-%s">
                <a href="%s" class="menu-item group js-ajax-link %s">
                    <iconify-icon icon="%s" class="menu-item-icon" width="18" height="18"></iconify-icon>
                    <span class="menu-item-text">%s</span>
                </a>
            </li>',
            $item['id'],
            $item['route'],
            $isActive,
            $icon,
            $item['label']
        );
    }

    public function shouldExpandSubmenu($item): bool
    {
        return false; // No submenus in current implementation
    }
}

