<?php
namespace App\Http\Controllers\Admin;

use App\Mail\AccountError;
use App\Mail\AccountOpenedMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AccountManageController {
    public function listaccount (Request $request)
    {
        $account = DB::table('users')
            ->leftJoin('user_roles', 'user_roles.user_id', '=', 'users.id')
            ->leftJoin('roles', 'roles.id', '=', 'user_roles.role_id')
            ->select(
                'users.*',
                'roles.name as role_name'
            );

        if ($request->status) {
            $account->where('users.status', $request->status);
        }

        if ($request->role) {
            $account->where('roles.name', $request->role);
        }

        $account = $account->paginate(10);
        return view('admin.account.account',compact('account'));
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

        // Không cho khóa Admin
        if (strtoupper($user->role_name ?? '') === 'ADMIN') {
            return back()->with('error', 'Không thể khóa tài khoản admin');
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
                'status' => 'SUSPENDED',
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




}

?>
