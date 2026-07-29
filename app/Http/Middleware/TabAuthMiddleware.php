<?php

namespace App\Http\Middleware;

use App\Helpers\TabAuthHelper;
use App\Models\TabToken;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware xác thực dựa trên Tab Token.
 *
 * Thay vì dùng session cookie (dùng chung giữa các tab),
 * middleware này dùng token trong URL (?tab_token=xxx)
 * để xác định user hiện tại.
 *
 * Mỗi tab trình duyệt có URL khác nhau → token khác nhau
 * → đăng nhập độc lập.
 */
class TabAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Bước 1: Lấy tab_token từ query string
        $token = $request->query('tab_token');

        // Nếu không có token → chuyển hướng về login
        if (!$token) {
            $redirectUrl = $request->fullUrl();
            $loginUrl = route('login', ['redirect' => $redirectUrl]);

            return redirect($loginUrl);
        }

        // Bước 2: Tìm token trong DB
        $tabToken = TabToken::where('token', $token)->first();

        // Không tìm thấy token → token không hợp lệ
        if (!$tabToken) {
            $loginUrl = route('login', [
                'redirect' => $request->fullUrl(),
                'error'    => 'token_invalid',
            ]);

            return redirect($loginUrl);
        }

        // Bước 3: Kiểm tra token còn hạn không
        if (!$tabToken->isValid()) {
            // Token hết hạn → xóa token và redirect login
            $tabToken->delete();
            $loginUrl = route('login', [
                'redirect' => $request->fullUrl(),
                'error'    => 'token_expired',
            ]);

            return redirect($loginUrl);
        }

        // Bước 4: Lấy user từ token và set vào Auth
        $user = $tabToken->user;

        if (!$user || $user->status !== 'ACTIVE') {
            $tabToken->delete();
            $loginUrl = route('login', [
                'redirect' => $request->fullUrl(),
                'error'    => 'user_inactive',
            ]);

            return redirect($loginUrl);
        }

        // Set user vào Auth (không dùng session)
        Auth::setUser($user);

        // Cập nhật last_used_at
        $tabToken->markAsUsed();

        // Gắn tab_token vào request để các view có thể dùng
        $request->merge(['tab_token' => $token]);
        $request->attributes->set('tab_token', $token);

        $response = $next($request);

        return $this->preserveTabTokenOnRedirect($response, $request);
    }

    public function preserveTabTokenOnRedirect(Response $response, Request $request): Response
    {
        if (!$response instanceof RedirectResponse) {
            return $response;
        }

        $token = $request->query('tab_token') ?? $request->attributes->get('tab_token');

        if (!$token) {
            return $response;
        }

        $targetUrl = $response->getTargetUrl();
        if (str_contains($targetUrl, 'tab_token=')) {
            return $response;
        }

        $response->setTargetUrl(TabAuthHelper::addToken($targetUrl));

        return $response;
    }
}

