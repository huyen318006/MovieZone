<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MembershipLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipLevelController extends Controller
{
    /**
     * Danh sách và Quản lý Mốc Hạng Thành Viên
     */
    public function index()
    {
        $levels = MembershipLevel::orderBy('min_points', 'asc')->get();

        return view('admin.memberships.levels', compact('levels'));
    }

    /**
     * Cập nhật quy tắc mốc điểm và % ưu đãi Hạng thành viên
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'min_points'       => 'required|numeric|min:0',
            'discount_percent' => 'required|numeric|min:0|max:100',
        ], [
            'min_points.required'       => 'Vui lòng nhập mốc chi tiêu.',
            'min_points.min'            => 'Mốc chi tiêu không được bé hơn 0.',
            'discount_percent.required' => 'Vui lòng nhập % giảm giá vé.',
            'discount_percent.max'      => '% giảm giá tối đa là 100%.',
        ]);

        try {
            $level = MembershipLevel::findOrFail($id);
            $oldMin = $level->min_points;
            $oldDiscount = $level->discount_percent;

            $level->update([
                'min_points'       => (float) $request->min_points,
                'discount_percent' => (float) $request->discount_percent,
                'updated_at'       => now(),
            ]);

            // Ghi Audit Log
            $adminUser = \App\Helpers\TabAuthHelper::currentUser() ?? Auth::user();
            $adminUserId = $adminUser ? $adminUser->id : Auth::id();

            if (class_exists(AuditLog::class)) {
                AuditLog::create([
                    'user_id'     => $adminUserId,
                    'action'      => 'UPDATE_MEMBERSHIP_LEVEL_RULE',
                    'entity_name' => 'MembershipLevel',
                    'entity_id'   => $level->id,
                    'old_value'   => "Mốc chi tiêu: " . number_format($oldMin) . "đ, Giảm giá: {$oldDiscount}%",
                    'new_value'   => "Mốc chi tiêu: " . number_format($request->min_points) . "đ, Giảm giá: {$request->discount_percent}%",
                    'created_at'  => now(),
                ]);
            }

            return redirect()->back()->with('success', "Cập nhật mốc hạng {$level->name} thành công!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
