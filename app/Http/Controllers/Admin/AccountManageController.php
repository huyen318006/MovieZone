<?php
namespace App\Http\Controllers\Admin;

use App\Mail\AccountError;
use App\Mail\AccountOpenedMail;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AccountManageController {
    public function listaccount(Request $request)
    {
     $tab        = $request->input('tab', 'admin');
    $email      = $request->email;
    $lockedRole = $request->input('locked_role');

    // Đếm badge (giữ nguyên)
    $countAdmin    = DB::table('users')->leftJoin('user_roles', 'user_roles.user_id', '=', 'users.id')->leftJoin('roles', 'roles.id', '=', 'user_roles.role_id')->where('roles.name', 'ADMIN')->where('users.status', 'ACTIVE')->count();
    $countStaff    = DB::table('users')->leftJoin('user_roles', 'user_roles.user_id', '=', 'users.id')->leftJoin('roles', 'roles.id', '=', 'user_roles.role_id')->where('roles.name', 'STAFF')->where('users.status', 'ACTIVE')->count();
    $countCustomer = DB::table('users')->leftJoin('user_roles', 'user_roles.user_id', '=', 'users.id')->leftJoin('roles', 'roles.id', '=', 'user_roles.role_id')->where('roles.name', 'CUSTOMER')->where('users.status', 'ACTIVE')->count();
    $countLocked   = DB::table('users')->where('status', 'LOCK')->count();

    // ==================== QUERY CHÍNH ====================
    $query = DB::table('users')
        ->leftJoin('user_roles', 'user_roles.user_id', '=', 'users.id')
        ->leftJoin('roles', 'roles.id', '=', 'user_roles.role_id')
        ->select('users.*', 'roles.name as role_name')
        ->orderBy('users.created_at', 'desc');   // thêm order cho dễ nhìn

    if ($tab === 'all') {
        // TAB TẤT CẢ: Lấy hết, không filter role và status
        // (không thêm where nào cả)
    }
    elseif ($tab === 'locked') {
        $query->where('users.status', 'LOCK');
        if ($lockedRole) {
            $query->where('roles.name', strtoupper($lockedRole));
        }
    }
    else {
        // Tab Admin, Staff, Customer
        $query->where('roles.name', strtoupper($tab))
              ->where('users.status', 'ACTIVE');
    }

    // Filter email - áp dụng cho tất cả tab
    if ($email) {
        $query->where('users.email', 'like', '%' . $email . '%');
    }

    $account = $query->paginate(10)->appends($request->query());

    return view('admin.account.account', compact(
        'account', 'tab',
        'countAdmin', 'countStaff', 'countCustomer', 'countLocked'
    ));
    }
    //detail account

    public function detailaccount($id)
    {
        $user = DB::table('users')
        ->leftJoin('user_roles','user_roles.user_id','=','users.id')
        ->leftJoin('roles','roles.id','=','user_roles.role_id')
        ->select('users.*',
        'roles.name as role_name'
        )
        ->where('users.id',$id)
        ->first();
        return view('admin.account.detail_account', compact('user'));

    }


    //lock tk người dùng
    public function lock(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        // Lấy thông tin user + role
        $user = DB::table('users')
            ->leftJoin('user_roles', 'users.id', '=', 'user_roles.user_id')
            ->leftJoin('roles', 'user_roles.role_id', '=', 'roles.id')
            ->select('users.*', 'roles.name as role_name')
            ->where('users.id', $id)
            ->first();

        if (!$user) {
            return back()->with('error', 'Không tìm thấy tài khoản');
        }

        // Kiểm tra tránh tự khóa chính mình
        if ($user->id == auth()->id()) {
            return back()->with('error', 'Bạn không thể tự khóa tài khoản của mình');
        }

        if (strtoupper($user->role_name ?? '') === 'ADMIN') {
            return back()->with('error', 'Không thể khóa tài khoản admin khác');
        }

        $oldStatus = $user->status;

        // Cập nhật status
        DB::table('users')
            ->where('id', $id)
            ->update([
                'status' => 'LOCK',
                'updated_at' => now()
            ]);

        // Ghi audit log
        DB::table('audit_logs')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'lock_user',
            'entity_name' => 'users',
            'entity_id'   => (string) $user->id,
            'old_value'   => json_encode(['status' => $oldStatus]),
            'new_value'   => json_encode([
                'status' => 'LOCK',
                'reason' => $request->reason
            ]),
            'created_at'  => now(),
        ]);

        // Gửi email
        Mail::to($user->email)->send(new AccountError($user, $request->reason));

        return back()->with('success', 'Đã khóa tài khoản thành công');


    }
    public function open($id)
    {
        $user = User::findOrFail($id);

        //không cho tự mở khóa chính mình (tuỳ bạn có cần hay không)
        if ($user->id == auth()->id()) {
            return back()->with('error', 'Bạn không thể tự thao tác với tài khoản của mình');
        }

        // lưu trạng thái cũ
        $oldStatus = $user->status;

        // 🔓 mở khóa tài khoản
        $user->status = 'ACTIVE';
        $user->save();

        // 🧾 audit log
        DB::table('audit_logs')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'unlock_user',
            'entity_name' => 'users',
            'entity_id'   => (string) $user->id,
            'old_value'   => json_encode([
                'status' => $oldStatus
            ]),
            'new_value'   => json_encode([
                'status' => 'ACTIVE'
            ]),
            'created_at'  => now(),
        ]);

        // 📧 gửi mail thông báo mở khóa
        Mail::to($user->email)
            ->send(new AccountOpenedMail($user));

        return back()->with('success', 'Mở khóa tài khoản thành công');
    }

    public function promote(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        // Lấy role của người cần nâng
        $userRole = UserRole::where('user_id', $request->user_id)->first();

        if (!$userRole) {
            return back()->with('error', 'Không tìm thấy vai trò của người dùng.');
        }

        // Cho phép CUSTOMER lên STAFF, nhưng không cho phép ADMIN/STAFF khác
        if ($userRole->role_id != 3) {
            return back()->with('error', 'Chỉ tài khoản CUSTOMER mới được nâng lên STAFF.');
        }
        // Lưu role cũ
        $oldRole = $userRole->role_id;


        // Đổi role sang STAFF
        $userRole->where('user_id',$request->user_id)->update([
            'role_id' => 2
        ]);

        //lưu lại audi_log
        DB::table('audit_logs')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'promote_user',
            'entity_name' => 'user_roles',
            'entity_id'   => $request->user_id,
            'old_value'   =>  json_encode([
                'role_id' => $oldRole
            ]),
            'new_value'   => json_encode([
                'role_id' => json_encode(
                    [
                        'role_id'=>2
                    ]
                )
            ]),
            'created_at'  => now(),
        ]);
        return back()->with(
            'success',
            'Nâng quyền nhân viên thành công.'
        );

    }


        //giáng chức
        public function demote(Request $request)
        {
        $userRole = UserRole::where('user_id', $request->user_id)->first();

        if (!$userRole) {
            return back()->with('error', 'Không tìm thấy role user');
        }

        // ❌ Không cho hạ admin
        if ($userRole->role_id == 1) {
            return back()->with('error', 'Không thể hạ quyền admin');
        }

        // ❌ Không tự hạ chính mình
        if ($userRole->user_id == auth()->id()) {
            return back()->with('error', 'Không thể thay đổi quyền chính bạn');
        }

        // ❌ Chỉ staff mới được hạ về customer
        if ($userRole->role_id != 2) {
            return back()->with('error', 'Chỉ có thể hạ quyền STAFF về CUSTOMER');
        }

        $oldRole = $userRole->role_id;

        $userRole->where('user_id', $request->user_id)->update([
            'role_id' => 3
        ]);

        DB::table('audit_logs')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'demote_user',
            'entity_name' => 'user_roles',
            'entity_id'   => $request->user_id,
            'old_value'   => json_encode(['role_id' => $oldRole]),
            'new_value'   => json_encode(['role_id' => 3]),
            'created_at'  => now(),
        ]);

        return back()->with('success', 'Hạ quyền thành công');
        }


        //hạ quyền admin
        public function demoteAdmin(Request $request)
{
    $userRole = UserRole::where(
        'user_id',
        $request->user_id
    )->first();

    // Không tự hạ chính mình
    if ($request->user_id == auth()->id()) {
        return back()->with(
            'error',
            'Không thể thay đổi quyền chính mình.'
        );
    }

    if ($userRole->role_id != 1) {
        return back()->with(
            'error',
            'Chỉ có thể hạ quyền tài khoản ADMIN khác.'
        );
    }

    // Đếm admin
    $adminCount = UserRole::where(
        'role_id',
        '1'
    )->count();

    // Không hạ admin cuối cùng
    if ($adminCount <= 1) {
        return back()->with(
            'error',
            'Hệ thống phải còn ít nhất 1 Admin.'
        );
    }

    //lấy dữ liệu cũ để log
    $oldRole = $userRole->role_id;
    // Hạ xuống Staff
    $userRole->where('user_id', $request->user_id)->update([
        'role_id' => 2
    ]);

    //ghi lại audit
    DB::table('audit_logs')->insert([
        'user_id'     => auth()->id(),
        'action'      => 'demote_admin',
        'entity_name' => 'user_roles',
        'entity_id'   => $request->user_id,
        'old_value'   => json_encode(['role_id' => $oldRole]),
        'new_value'   => json_encode(['role_id' => 2]),
        'created_at'  => now(),
    ]);

    return back()->with(
        'success',
        'Hạ quyền Admin thành công.'
    );
}

//update account management
//view update account management
public function profileAccount($id)
{
    $user = User::findOrFail($id);
    return view('admin.account.updateaccount', compact('user'));
}
//
public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:15',
    ]);
    //kiểm tra xem ảnh có được gửi
    if($request->hasFile('avatar'))
    {
        //kiểm tra xem tệp có tồn tại trong storage không
        if(!empty($user->avatar))
        {
            //delete file cũ
            Storage::disk('public')->delete($user->avatar);
        }
        //còn lại thì upload ảnh mới
        $avatanew = $request->file('avatar')->store('avata_account', 'public');
    }else {
        //avatar cũ nếu không upload ảnh mới
        $avatanew = $user->avatar;
    }

    $user->update([
        'name' => $request->name,
        'phone' => $request->phone,
        'avatar' => $avatanew,
    ]);

    //ghi lại audit
    DB::table('audit_logs')->insert([
        'user_id'     => auth()->id(),
        'action'      => 'update_user',
        'entity_name' => 'users',
        'entity_id'   => $id,
        'old_value'   => json_encode(['name' => $user->name, 'phone' => $user->phone]),
        'new_value'   => json_encode(['name' => $request->name, 'phone' => $request->phone]),
        'created_at'  => now(),
    ]);

    return back()->with('success', 'Cập nhật thông tin thành công');
}

public function updatepassword(Request $request, $id)
{
    $user = User::findOrFail($id);
    //validate password
    $request->validate([
        'password' => 'required|string|min:6',
    ]);
    //kiểm tra mật khẩu có giống mật khẩu cũ không
    if($request->password == $user->password)
    {
        return back()->with('error', 'Mật khẩu mới không được giống mật khẩu cũ');
    }
    //check pass cũ
    if(!password_verify($request->old_password, $user->password))
    {
        return back()->with('error', 'Mật khẩu cũ không đúng');
    }


    $user->update([
        'password' => bcrypt($request->password),
    ]);

    //ghi lại audit
    DB::table('audit_logs')->insert([
        'user_id'     => auth()->id(),
        'action'      => 'update_password_admin',
        'entity_name' => 'users',
        'entity_id'   => $id,
        'old_value'   => json_encode(['password' => $user->password]),
        'new_value'   => json_encode(['password' => bcrypt($request->password)]),
        'created_at'  => now(),
    ]);

    return back()->with('success', 'Cập nhật mật khẩu thành công');
}

//thêm tài khoản account
public function createAccount()
{
    $roles = Role::all();
    return view('admin.account.create_account', compact('roles'));
}

// lưu tài khoản
public function storeAccount(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6|confirmed',
        'role_id' => 'required|exists:roles,id',
    ],
    [
        'name.required' => 'Tên không được để trống',
        'email.required' => 'Email không được để trống',
        'email.unique' => 'Email đã tồn tại',
        'password.required' => 'Mật khẩu không được để trống',
        'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
        'password.confirmed' => 'Mật khẩu không khớp',
        'role_id.required' => 'Vai trò không được để trống',
        'role_id.exists' => 'Vai trò không hợp lệ',
    ]
);

    //kiểm tra ảnh có tồn tại không
    if($request->hasFile('avatar'))
    {
        $avatar = $request->file('avatar')->store('avatars', 'public');
    }
    else
    {
        $avatar = null;
    }
    // Kiểm tra giới hạn tối đa 3 tài khoản do admin hiện tại tạo
    $createdAccountCount = DB::table('audit_logs')
        ->where('user_id', auth()->id())
        ->where('action', 'create_user')
        ->count();

    if ($createdAccountCount >= 3) {
        return back()
            ->withInput()
            ->with('error', 'Bạn chỉ được tạo tối đa 3 tài khoản.');
    }

    if ((int)$request->role_id === 1) {
        $adminCount = DB::table('user_roles')->where('role_id', 1)->count();
        if ($adminCount >= 3) {
            return back()
                ->withInput()
                ->with('error', 'Hệ thống đã đạt tối đa 3 tài khoản Admin. Vui lòng chọn vai trò khác.');
        }
    }


    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'avatar' => $avatar,
        'password' => bcrypt($request->password),
    ]);

    // Lưu quyền vào bảng user_roles
    UserRole::create([
        'user_id' => $user->id,
        'role_id' => $request->role_id,
        'assigned_at' => now(),
    ]);

    //ghi lại audit
    DB::table('audit_logs')->insert([
        'user_id'     => auth()->id(),
        'action'      => 'create_user',
        'entity_name' => 'users',
        'entity_id'   => $user->id,
        'old_value'   => null,
        'new_value'   => json_encode([
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role_id' => $request->role_id,
        ]),
        'created_at'  => now(),
    ]);

    return redirect()->route('admin.list_account')->with('success', 'Tạo tài khoản thành công');
}




}

?>
