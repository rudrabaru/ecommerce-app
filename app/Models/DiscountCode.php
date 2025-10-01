<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Products\Models\Category;

class DiscountCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'minimum_order_amount',
        'usage_limit',
        'usage_count',
        'valid_from',
        'valid_until',
        'is_active',
        'category_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'discount_code_category');
    }

    // Convenience for single-category UI
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Scopes
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeValidNow($q)
    {
        $now = now();
        return $q->where(function ($q) use ($now) {
            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
        });
    }

    public function isWithinDateRange(): bool
    {
        $now = now();
        if ($this->valid_from && $now->lt($this->valid_from)) return false;
        if ($this->valid_until && $now->gt($this->valid_until)) return false;
        return true;
    }

    public function hasRemainingUses(): bool
    {
        if (is_null($this->usage_limit)) return true;
        return $this->usage_count < $this->usage_limit;
    }

    public function appliesToCategoryIds(array $categoryIds): bool
    {
        $allowed = $this->categories()->pluck('categories.id')->all();
        if (empty($allowed) && $this->category_id) {
            $allowed = [$this->category_id];
        }
        if (empty($allowed)) return true; // if none selected, applies to all
        return count(array_intersect($allowed, $categoryIds)) > 0;
    }
}


