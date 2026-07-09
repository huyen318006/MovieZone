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
        $filter = $request->query('filter', 'available');
        $search = trim((string) $request->query('search', ''));

        $query = Promotion::query()
            ->where('status', 'ACTIVE')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            });

        match ($filter) {
            'ongoing' => $query->where('start_date', '<=', now())->where('end_date', '>=', now()),
            'upcoming' => $query->where('start_date', '>', now()),
            'ended' => $query->where('end_date', '<', now()),
            default => $query->where('end_date', '>=', now()),
        };

        $promotions = $query
            ->orderBy('start_date')
            ->paginate(9)
            ->appends($request->query());

        return view('promotion.index', compact('promotions', 'filter', 'search'));
    }

    /**
     * UC-CUS-15: Chi tiết chương trình khuyến mãi.
     */
    public function show(Promotion $promotion)
    {
        if ($promotion->status !== 'ACTIVE' || $promotion->end_date->isPast()) {
            abort(404, 'Chương trình khuyến mãi không tồn tại hoặc đã kết thúc.');
        }

        return view('promotion.show', compact('promotion'));
    }
}