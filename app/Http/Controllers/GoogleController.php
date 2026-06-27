<?php

namespace App\Http\Controllers;

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

            $userRole = UserRole::where('user_id', $finduser->id)->first();

            if ($userRole && $userRole->role_id == 1) {
                return redirect('/admin')->with('success', 'Chào mừng admin trở lại hệ thống!');
            } elseif ($userRole && $userRole->role_id == 2) {
                return redirect('/staff')->with('success', 'Đăng nhập thành công với tư cách nhân viên!');
            } else {
                return redirect('/')->with('success', 'Đăng nhập thành công!');
            }
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
        UserRole::create([
            'user_id'     => $newUser->id,
            'role_id'     => 3,
            'assigned_at' => now(),
        ]);

        Auth::login($newUser);
        request()->session()->regenerate();

        return redirect('/')
            ->with('success', 'Đăng ký và đăng nhập thành công qua Google!');
    }
}
