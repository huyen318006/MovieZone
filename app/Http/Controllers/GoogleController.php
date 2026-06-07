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
        $user = Socialite::driver('google')->stateless()->user();
        // Process the user data as needed
        // For example, you can create or update a user in your database
        $finduser = User::where('email', $user->email)->first();
        if ($finduser) {
            //kiểm tra phân quyền
            $userRole = UserRole::where('user_id', $finduser->id)->first();
            Auth::login($finduser);

            if ($userRole && $userRole->role_id == 1) {
                return redirect('/admin')->with('success', 'Chào mừng admin trở lại hệ thống!');
            } elseif ($userRole && $userRole->role_id == 2) {
                return redirect('/staff')->with('success', 'Đăng nhập thành công với tư cách nhân viên!');
            } elseif ($userRole && $userRole->role_id == 3) {
                return redirect('/')->with('success', 'Đăng nhập thành công!');
            }



        } else {
            // Create a new user
            $newUser = User::updateOrCreate(
                ['email'=>$user->email],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->id,
                    'password' => Hash::make('12345678') // You can set a default password or generate a random one
                    // You can also store the avatar or other information if needed
                ]
            );
            //tạo phân quyền
            UserRole::create([
                'user_id' => $newUser->id,
                'role_id' => 3,
                'assigned_at' => now(),
            ]);
            Auth::login($newUser);
            return redirect('/')->with('success', 'Đăng ký và đăng nhập thành công!');
        }
    }
}
