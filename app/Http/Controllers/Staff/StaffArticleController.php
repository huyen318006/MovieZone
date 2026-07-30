<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StaffArticleController extends Controller
{
    /**
     * Hiển thị danh sách bài viết (dành cho Staff)
     */
    public function index(Request $request)
    {
        $query = Article::query();

        // Staff có thể xem tất cả bài viết (đã có quyền edit)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('summary', 'like', "%{$keyword}%");
            });
        }

        $articles = $query->orderBy('created_at', 'desc')->paginate(10);
        $categories = Article::select('category')->distinct()->pluck('category');

        return view('staff.articles.index', compact('articles', 'categories'));
    }

    /**
     * Xem chi tiết bài viết
     */
    public function show($id)
    {
        $article = Article::findOrFail($id);
        return view('staff.articles.show', compact('article'));
    }

    /**
     * Hiển thị form chỉnh sửa bài viết
     */
    public function edit($id)
    {
        $article = Article::findOrFail($id);
        return view('staff.articles.edit', compact('article'));
    }

    /**
     * Cập nhật bài viết
     */
    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:articles,slug,' . $id,
            'category'    => 'required|string|max:100',
            'status'      => 'required|in:DRAFT,PUBLISHED,HIDDEN',
            'published_at'=> 'nullable|date',
            'summary'     => 'nullable|string|max:500',
            'content'     => 'required|string',
            'thumbnail'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $article->title    = $validated['title'];
        $article->category = $validated['category'];
        $article->status   = $validated['status'];
        $article->summary  = $validated['summary'] ?? null;
        $article->content  = $validated['content'];

        // Xử lý slug
        if (!empty($validated['slug'])) {
            $article->slug = $validated['slug'];
        } else {
            $article->slug = Str::slug($validated['title']);
        }

        // Xử lý published_at
        if (!empty($validated['published_at'])) {
            $article->published_at = $validated['published_at'];
        } elseif ($validated['status'] === 'PUBLISHED' && !$article->published_at) {
            $article->published_at = now();
        }

        // Xử lý thumbnail
        if ($request->hasFile('thumbnail')) {
            // Xóa ảnh cũ nếu có
            if ($article->thumbnail_url && Storage::disk('public')->exists($article->thumbnail_url)) {
                Storage::disk('public')->delete($article->thumbnail_url);
            }
            $path = $request->file('thumbnail')->store('articles', 'public');
            $article->thumbnail_url = $path;
        }

        $article->save();

        return redirect()
            ->route('staff.articles.show', $article->id)
            ->with('success', 'Bài viết đã được cập nhật thành công.');
    }
}

