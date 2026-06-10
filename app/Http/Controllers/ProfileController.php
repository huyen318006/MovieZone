<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Hiển thị trang hồ sơ cá nhân
     */
    public function index()
    {
        return view('profile.index');
    }

    /**
     * Xử lý cập nhật thông tin hồ sơ và avatar
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        //Validate 
        $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'required|string|max:15|unique:users,phone,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Tối đa 2MB
        ], [
            'name.required'  => 'Vui lòng nhập họ và tên.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.unique'   => 'Số điện thoại này đã được sử dụng.',
            'avatar.image'   => 'Định dạng file phải là hình ảnh.',
            'avatar.mimes'   => 'Ảnh đại diện chỉ chấp nhận định dạng: jpeg, png, jpg, gif.',
            'avatar.max'     => 'Kích thước ảnh đại diện không được vượt quá 2MB.',
        ]);

        // Điền thông tin 
        $user->name = $request->input('name');
        $user->phone = $request->input('phone');

        //Upload ảnh nếu có file mới
        if ($request->hasFile('avatar')) {
            // Xóa file ảnh cũ trong thư mục storage nếu tồn tại
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Lưu file ảnh mới vào thư mục storage/app/public/avatars
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        // 4. Lưu lại toàn bộ thay đổi vào Database
        $user->save();

        return redirect()->route('profile')->with('success', 'Cập nhật hồ sơ cá nhân thành công!');
    }


    public function changePassword(Request $request)
    {
        // 1. Validate dữ liệu đầu vào
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:6', 'confirmed'], // 'confirmed' bắt buộc phải có trường nhập lại tên là new_password_confirmation
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.min' => 'Mật khẩu mới phải từ 6 ký tự trở lên.',
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không trùng khớp.',
        ]);

        $user = Auth::user();

        // 2. Kiểm tra mật khẩu hiện tại có đúng không
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
        }

        // 3. Cập nhật mật khẩu mới vào Database
        $user->password = Hash::make($request->new_password);
        /** @var \App\Models\User $user */
        $user->save();

        return back()->with('success', 'Đổi mật khẩu thành công!');
    }
}