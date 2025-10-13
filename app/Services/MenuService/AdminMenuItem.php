<?php

namespace App\Services\MenuService;

class AdminMenuItem
{
    public $id;
    public $label;
    public $route;
    public $icon;
    public $active;
    public $children;
    public $htmlData;
    public $itemStyles;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->label = $data['label'] ?? '';
        $this->route = $data['route'] ?? '#';
        $this->icon = $data['icon'] ?? 'lucide:circle';
        $this->active = $data['active'] ?? false;
        $this->children = $data['children'] ?? [];
        $this->htmlData = $data['htmlData'] ?? null;
        $this->itemStyles = $data['itemStyles'] ?? '';
    }
}
