<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

class StaffArticleController extends Controller
{
    /**
     * Hiển thị danh sách bài viết (dành cho Staff)
     */
    public function index(Request $request)
    {
        $query = Article::query();

        // Mặc định staff chỉ thấy bài PUBLISHED, nhưng có thể lọc để xem tất cả nếu cần
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'PUBLISHED');
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

        $articles = $query->orderBy('published_at', 'desc')->paginate(10);
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
}

