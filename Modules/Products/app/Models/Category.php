<?php

namespace Modules\Products\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id', 'image', 'description'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getImageUrlAttribute(): string
    {
        $image = trim((string)($this->image ?? ''), " \t\n\r\0\x0B\"'{}");
        if ($image === '') {
            return asset('img/category-placeholder.png');
        }
        if ($image[0] === '@') {
            $image = substr($image, 1);
        }
        // Decode percent-encoded URL if needed
        if (preg_match('#^(https?%3A|http%3A)//#i', $image)) {
            $image = urldecode($image);
        }
        $image = str_replace(' ', '%20', $image);

        // Absolute URLs
        if (preg_match('#^https?://#i', $image) || preg_match('#^//#', $image)) {
            try {
                $parts = parse_url($image);
                if (!empty($parts['host']) && stripos($parts['host'], 'via.placeholder.com') !== false) {
                    $path = $parts['path'] ?? '';
                    $query = $parts['query'] ?? '';
                    $text = '';
                    parse_str($query, $q);
                    if (!empty($q['text'])) { $text = $q['text']; }
                    if (preg_match('#/(\\d+x\\d+)\\.png/([0-9a-fA-F]{3,6})#', $path, $m)) {
                        $size = $m[1];
                        $bg = $m[2];
                        return 'https://placehold.co/' . $size . '/' . $bg . '/ffffff?text=' . urlencode($text ?: '');
                    }
                    if (preg_match('#/(\\d+x\\d+)#', $path, $m)) {
                        $size = $m[1];
                        return 'https://placehold.co/' . $size . '?text=' . urlencode($text ?: '');
                    }
                    return 'https://placehold.co/400x300?text=';
                }
            } catch (\Throwable $e) {}
            return $image;
        }

        // Local stored paths
        if (\Illuminate\Support\Str::startsWith($image, ['storage/'])) {
            return asset($image);
        }
        if (\Illuminate\Support\Str::startsWith($image, ['public/'])) {
            return asset(str_replace('public/', 'storage/', $image));
        }
        return asset('storage/' . ltrim($image, '/'));
    }
}


