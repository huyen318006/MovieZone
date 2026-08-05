<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $table = 'articles';

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'thumbnail_url',
        'category',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: chỉ lấy bài viết đã xuất bản
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'PUBLISHED');
    }

    /**
     * Scope: chỉ lấy bài viết nháp
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'DRAFT');
    }

    /**
     * Scope: chỉ lấy bài viết ẩn
     */
    public function scopeHidden($query)
    {
        return $query->where('status', 'HIDDEN');
    }

    /**
     * Accessor thông minh cho Thumbnail Bài viết
     */
    public function getThumbnailAttribute(): string
    {
        $path = trim($this->thumbnail_url ?? '');

        if (empty($path)) {
            return asset('assets/news/batman.jpg');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/') || str_starts_with($path, 'assets/') || str_starts_with($path, 'uploads/')) {
            return asset($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        if (file_exists(public_path('storage/' . $path))) {
            return asset('storage/' . $path);
        }

        if (file_exists(public_path('uploads/' . $path))) {
            return asset('uploads/' . $path);
        }

        if (file_exists(public_path('assets/' . $path))) {
            return asset('assets/' . $path);
        }

        return asset('storage/' . $path);
    }
}
