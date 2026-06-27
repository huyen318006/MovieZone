<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // nếu dùng roles many-to-many
        $isAdmin = $user->roles()
            ->where('name', 'admin')
            ->exists();

        if (!$isAdmin) {
            abort(403, 'Bạn không có quyền truy cập.');
        }

        return $next($request);
    }
    }

