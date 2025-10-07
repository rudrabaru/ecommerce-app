<?php

namespace Modules\Products\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'price', 'stock', 'category_id', 'provider_id', 'slug', 'image', 'is_approved'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $base = Str::slug($product->title);
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $product->slug = $slug;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'provider_id');
    }

    public function getImageUrlAttribute(): string
    {
        $image = trim((string)($this->image ?? ''), " \t\n\r\0\x0B\"'{}");
        if ($image === '') {
            return asset('img/product-placeholder.png');
        }
        if ($image[0] === '@') { $image = substr($image, 1); }
        if (preg_match('#^(https?%3A|http%3A)//#i', $image)) { $image = urldecode($image); }
        $image = str_replace(' ', '%20', $image);

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
                    return 'https://placehold.co/600x600?text=';
                }
            } catch (\Throwable $e) {}
            return $image;
        }

        if (\Illuminate\Support\Str::startsWith($image, ['storage/'])) { return asset($image); }
        if (\Illuminate\Support\Str::startsWith($image, ['public/'])) { return asset(str_replace('public/', 'storage/', $image)); }
        return asset('storage/' . ltrim($image, '/'));
    }
}


