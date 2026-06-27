<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * S1-09: Middleware kiểm tra quyền Staff.
 *
 * Kiểm tra user đã đăng nhập có permission cụ thể
 * thông qua chuỗi: user_roles → role_permissions → permissions.
 *
 * Sử dụng:
 *   Route::middleware('staff.permission:booking.lookup')
 */
class CheckStaffPermission
{
    /**
     * Handle an incoming request.
     *
     * @param string $permission Permission code cần kiểm tra (VD: 'booking.lookup')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // E3: Chưa đăng nhập
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => [
                        'code'    => 'UNAUTHORIZED',
                        'message' => 'Vui lòng đăng nhập để tiếp tục.',
                    ],
                ], 401);
            }
            return redirect()->route('login');
        }

        // Kiểm tra permission qua chuỗi: user_roles → role_permissions → permissions
        $hasPermission = DB::table('user_roles')
            ->join('role_permissions', 'user_roles.role_id', '=', 'role_permissions.role_id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('user_roles.user_id', $user->id)
            ->where('permissions.name', $permission)
            ->exists();

        // E3: Không có quyền
        if (!$hasPermission) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => [
                        'code'    => 'FORBIDDEN',
                        'message' => 'Bạn không có quyền thực hiện thao tác này.',
                    ],
                ], 403);
            }
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}
