<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleManageController extends Controller
{
    /**
     * Hiển thị danh sách bài viết (có bộ lọc & tìm kiếm)
     */
    public function index(Request $request)
    {
        $query = Article::query();

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo danh mục
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Tìm kiếm theo tiêu đề hoặc nội dung
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('summary', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
            });
        }

        // Sắp xếp bài viết mới nhất lên đầu (theo published_at, nếu null thì theo created_at)
        $articles = $query->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Lấy danh sách danh mục để hiển thị filter
        $categories = Article::select('category')->distinct()->pluck('category');

        return view('admin.article.index', compact('articles', 'categories'));
    }

    /**
     * Hiển thị form thêm bài viết mới
     */
    public function create()
    {
        $categories = Article::select('category')->distinct()->pluck('category');
        return view('admin.article.create', compact('categories'));
    }

    /**
     * Lưu bài viết mới vào database
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:articles,slug',
            'summary' => 'nullable|string|max:500',
            'content' => 'required|string',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'category' => 'required|string|max:100',
            'status' => 'required|in:DRAFT,PUBLISHED,HIDDEN',
            'published_at' => 'nullable|date',
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề bài viết.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'slug.unique' => 'Slug này đã tồn tại, vui lòng chọn slug khác.',
            'content.required' => 'Vui lòng nhập nội dung bài viết.',
            'thumbnail.image' => 'File tải lên phải là hình ảnh.',
            'thumbnail.max' => 'Kích thước ảnh tối đa là 4MB.',
            'category.required' => 'Vui lòng chọn danh mục.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ]);

        // Xử lý slug: nếu không nhập thì tự động tạo từ title
        $slug = $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->title);
        // Đảm bảo slug unique
        $originalSlug = $slug;
        $counter = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Xử lý upload thumbnail (lưu trực tiếp vào public/uploads/articles/ - không cần storage:link)
        $thumbnailUrl = null;
        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/articles'), $fileName);
            $thumbnailUrl = 'uploads/articles/' . $fileName;
        }

        // Xử lý published_at
        $publishedAt = $request->filled('published_at') ? $request->published_at : null;
        // Nếu status là PUBLISHED mà chưa có published_at, gán thời gian hiện tại
        if ($request->status === 'PUBLISHED' && !$publishedAt) {
            $publishedAt = now();
        }

        Article::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'summary' => $validated['summary'] ?? null,
            'content' => $validated['content'],
            'thumbnail_url' => $thumbnailUrl,
            'category' => $validated['category'],
            'status' => $validated['status'],
            'published_at' => $publishedAt,
        ]);

        return redirect()->route('admin.articles.index')->with('success', 'Thêm bài viết thành công.');
    }

    /**
     * Hiển thị form chỉnh sửa bài viết
     */
    public function edit($id)
    {
        $article = Article::findOrFail($id);
        $categories = Article::select('category')->distinct()->pluck('category');
        return view('admin.article.edit', compact('article', 'categories'));
    }

    /**
     * Cập nhật bài viết trong database
     */
    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:articles,slug,' . $id,
            'summary' => 'nullable|string|max:500',
            'content' => 'required|string',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'category' => 'required|string|max:100',
            'status' => 'required|in:DRAFT,PUBLISHED,HIDDEN',
            'published_at' => 'nullable|date',
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề bài viết.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'slug.unique' => 'Slug này đã tồn tại, vui lòng chọn slug khác.',
            'content.required' => 'Vui lòng nhập nội dung bài viết.',
            'thumbnail.image' => 'File tải lên phải là hình ảnh.',
            'thumbnail.max' => 'Kích thước ảnh tối đa là 4MB.',
            'category.required' => 'Vui lòng chọn danh mục.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ]);

        // Xử lý slug
        $slug = $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->title);
        $originalSlug = $slug;
        $counter = 1;
        while (Article::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Xử lý upload thumbnail (lưu trực tiếp vào public/uploads/articles/)
        $thumbnailUrl = $article->thumbnail_url;
        if ($request->hasFile('thumbnail')) {
            // Xóa ảnh cũ nếu có
            if ($article->thumbnail_url && file_exists(public_path($article->thumbnail_url))) {
                unlink(public_path($article->thumbnail_url));
            }
            $file = $request->file('thumbnail');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/articles'), $fileName);
            $thumbnailUrl = 'uploads/articles/' . $fileName;
        }

        // Xử lý published_at
        $publishedAt = $request->filled('published_at') ? $request->published_at : $article->published_at;
        // Nếu chuyển sang PUBLISHED mà chưa có published_at, gán thời gian hiện tại
        if ($request->status === 'PUBLISHED' && !$publishedAt) {
            $publishedAt = now();
        }

        $article->update([
            'title' => $validated['title'],
            'slug' => $slug,
            'summary' => $validated['summary'] ?? null,
            'content' => $validated['content'],
            'thumbnail_url' => $thumbnailUrl,
            'category' => $validated['category'],
            'status' => $validated['status'],
            'published_at' => $publishedAt,
        ]);

        return redirect()->route('admin.articles.index')->with('success', 'Cập nhật bài viết thành công.');
    }

    /**
     * Xóa bài viết (kèm ảnh thumbnail)
     */
    public function destroy($id)
    {
        $article = Article::findOrFail($id);

        // Xóa ảnh thumbnail nếu có
        if ($article->thumbnail_url && file_exists(public_path($article->thumbnail_url))) {
            unlink(public_path($article->thumbnail_url));
        }

        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Xóa bài viết thành công.');
    }
}
