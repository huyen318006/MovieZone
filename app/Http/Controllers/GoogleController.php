<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $finduser = User::where('google_id', $user->id)->first();
        if ($finduser) {
            Auth::login($finduser);
            return redirect('/')->with('success', 'Đăng nhập thành công!');

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
            Auth::login($newUser);
            return redirect('/')->with('success', 'Đăng ký và đăng nhập thành công!');
        }
    }
}
