<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash as FacadesHash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    //

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        // Kiểm tra cả status ngay trong attempt
        if (Auth::attempt([
            'email'    => $request->email,
            'password' => $request->password,
            'status'   => 'ACTIVE'           // ← Thêm dòng này
        ])) {

            $request->session()->regenerate();

            $user = Auth::user();
            $userRole = UserRole::where('user_id', $user->id)->first();

            // Redirect theo role
            if ($userRole && $userRole->role_id == 1) {
                return redirect('/admin')
                    ->with('success', 'Chào mừng admin trở lại hệ thống!');
            }

            if ($userRole && $userRole->role_id == 2) {
                return redirect('/staff')
                    ->with('success', 'Đăng nhập thành công với tư cách nhân viên!');
            }

            return redirect('/')
                ->with('success', 'Đăng nhập thành công!');
        }

        // Nếu login thất bại (sai mật khẩu hoặc tài khoản bị khóa)
        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác hoặc tài khoản đã bị khóa.',
        ]);
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('success', 'Đăng xuất thành công!');
    }
    function register(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email'
            ],

            'phone' => [
                'required',
                'regex:/^[0-9]{10,11}$/',
                'unique:users,phone'
            ],

            'password' => [
                'required',
                'min:8',
                'confirmed'
            ],
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',

            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email đã tồn tại.',

            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'phone.unique' => 'Số điện thoại đã tồn tại.',

            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải từ 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

     $userNew =    User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => FacadesHash::make($validated['password']),
        ]);
        //tạo quyền
        UserRole::create([
            'user_id' => $userNew->id,
            'role_id' => 3,
            'assigned_at' => now(),
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Đăng ký tài khoản thành công.');
    }
    /* -------------Phần forgot password------------------ */
          public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
        ]);
        //kiểm tra email có tồn tại không
        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'Email không tồn tại trong hệ thống.',
            ])->onlyInput('email');
         }
         /* gửi link reset password về email */
          $status = Password::sendResetLink($request->only('email'));

          //nếu thành công thì trả về thông báo
          if($status === Password::RESET_LINK_SENT){
            return back()->with('success','Đã gửi liên kết đặt lại mật khẩu đến email của bạn');
          }

          // Nếu gửi thất bại: KHÔNG hiển thị lỗi cụ thể ra UI (tránh lộ thông tin & UX tốt hơn)
          // Thông báo trung tính để người dùng hiểu rằng có thể mail đã/không đến do hệ thống.
          return back()->with('success','Nếu email của bạn tồn tại trong hệ thống, bạn sẽ nhận được liên kết đặt lại mật khẩu trong vài phút. Vui lòng kiểm tra hộp thư đến và spam.');

}

public function reset_password(Request $request,$token)
{
    $email= $request->email;
    return view('auth.forgot_password',compact('email','token'));

}

public function update_password(Request $request)
{
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        //bắt đâu reset password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => FacadesHash::make($password)
                ])->save();
            }
        );
        // nếu thành công thì trả về login và thông báo
        if($status === Password::PASSWORD_RESET){
            return redirect()->route('login')->with('success','Đặt lại mật khẩu thành công, bạn có thể đăng nhập ngay bây giờ!');
        }else{
            return back()->withErrors(['email' => 'Không thể đặt lại mật khẩu']);

      }
   }
}


