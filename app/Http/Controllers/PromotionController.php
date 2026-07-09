<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    /**
     * UC-CUS-15: Danh sách khuyến mãi dành cho khách hàng.
     */
    public function index(Request $request)
    {
        $promotions = Promotion::query()
            ->where('status', 'ACTIVE')
            ->orderBy('start_date')
            ->paginate(9)
            ->appends($request->query());

        return view('promotion.index', compact('promotions'));
    }

    /**
     * UC-CUS-15: Chi tiết chương trình khuyến mãi.
     */
    public function show(Promotion $promotion)
    {
        if ($promotion->status !== 'ACTIVE') {
            abort(404, 'Chương trình khuyến mãi không tồn tại hoặc đã kết thúc.');
        }

        return view('promotion.show', compact('promotion'));
    }
}