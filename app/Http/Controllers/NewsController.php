<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Display a listing of the published articles.
     */
    public function index(Request $request)
    {
        $articles = Article::query()
            ->where('status', 'PUBLISHED')
            ->orderBy('published_at', 'desc')
            ->paginate(6);

        return view('news.index', compact('articles'));
    }

    /**
     * Display the specified article.
     */
    public function show(string $slug)
    {
        $article = Article::query()
            ->where('slug', $slug)
            ->where('status', 'PUBLISHED')
            ->firstOrFail();

        // Get other articles for the sidebar (excluding the current one)
        $otherArticles = Article::query()
            ->where('id', '!=', $article->id)
            ->where('status', 'PUBLISHED')
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();

        return view('news.detail', compact('article', 'otherArticles'));
    }
}
