<?php

namespace App\Http\Controllers;

use App\Models\TabToken;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    //
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        // 1. Lấy thông tin người dùng từ Google
        $googleUser = Socialite::driver('google')->stateless()->user();

        $finduser = User::where('email', $googleUser->email)->first();

        // === Kiểm tra nếu đã tồn tại ===
        if ($finduser) {

            // Kiểm tra tài khoản có bị khóa không
            if ($finduser->status === 'LOCK' || $finduser->status !== 'ACTIVE') {
                return redirect()->route('login')
                    ->withErrors(['error' => 'Tài khoản của bạn đã bị khóa. Hãy quay lại trang chủ điền form hỗ trợ để mở khóa tài khoản']);
            }
            

            // Đăng nhập
            Auth::login($finduser);
            $request = request(); // hoặc inject Request nếu cần
            $request->session()->regenerate();
            //dăng nhập thành công thì tạo token mới
            $token = bin2hex(random_bytes(32));
            //5. lưu token vào bảng tab_toke
            TabToken::create(
                [
                    'user_id' => $finduser->id,
                    'token'=> $token,
                    'last_used_at' => now(), //thời điểm tạo
                    'expires_at' => now()->addHours(24), // token có hiệu lực 24 giờ
                ]
            );
            // 6. lấy role để phân quyền
            $userRole = UserRole::where('user_id', $finduser->id)->first();

            // 7. Chuyển hướng dựa trên role
            $redirectRoute = match($userRole->role_id) {
                1 => 'admin.dashboard',
                2 => 'staff.dashboard',
                3 => 'home', // Khách hàng
                default => 'home', // Mặc định về trang chủ
            };

            // 8. logout  để tránh dính session cho các tab khác
            $request->session()->regenerate();
            Auth::logout();
            //9. Chuyển hướng trả về kèm token
            return redirect()->route($redirectRoute, ['tab_token' => $token])
                ->with('success', 'Đăng nhập thành công qua Google!');
        }

        // === Tạo user mới ===
        $newUser = User::updateOrCreate(
            ['email' => $googleUser->email],
            [
                'name'      => $googleUser->name,
                'email'     => $googleUser->email,
                'google_id' => $googleUser->id,
                'password'  => Hash::make('12345678'), // Nên random password hơn
                'status'    => 'ACTIVE',               // Mặc định active khi tạo mới
            ]
        );

        // Tạo phân quyền mặc định
       $userRole = UserRole::create([
            'user_id'     => $newUser->id,
            'role_id'     => 3,
            'assigned_at' => now(),
        ]);

        // Tự động khởi tạo ví Coin & Membership mặc định
        app(\App\Services\MembershipService::class)->ensureMembership($newUser);

        // Tạo token mới để đăng nhập tab_token
        $token = bin2hex(random_bytes(32));
        TabToken::create([
            'user_id'      => $newUser->id,
            'token'        => $token,
            'last_used_at' => now(),
            'expires_at'   => now()->addHours(24),
        ]);

        Auth::logout();

        $userRole = UserRole::where('user_id', $newUser->id)->first();
        $redirectRoute = match($userRole?->role_id) {
            1 => 'admin.dashboard',
            2 => 'staff.dashboard',
            3 => 'home',
            default => 'home',
        };

        return redirect()->route($redirectRoute, ['tab_token' => $token])
            ->with('success', 'Đăng ký và đăng nhập thành công qua Google!');

    }
}
