<?php

namespace App\Http\Controllers;

use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    public function apply(
        Request $request,
        VoucherService $voucherService
    ) {

        $request->validate([
            'code' => ['required', 'string']
        ]);

        $booking = session('booking_tam');

        if (!$booking) {
            return back()->with(
                'error',
                'Không tìm thấy thông tin booking'
            );
        }

        $result = $voucherService->applyVoucher(
            strtoupper(trim($request->code)),
            $booking['subtotal'],
            Auth::id()
        );

        if (!$result['success']) {

            return back()->with(
                'error',
                $result['message']
            );
        }

        $booking['voucher_id']
            = $result['voucher']->id;

        $booking['voucher_code']
            = $result['voucher']->code;

        $booking['discount_amount']
            = $result['discount'];

        $booking['total']
            = $result['total'];

        session([
            'booking_tam' => $booking
        ]);

        return back()->with(
            'success',
            'Áp voucher thành công'
        );
    }

    public function remove()
    {
        $booking = session('booking_tam');

        if (!$booking) {
            return back();
        }

        $booking['voucher_id'] = null;
        $booking['voucher_code'] = null;
        $booking['discount_amount'] = 0;
        $booking['total'] = $booking['subtotal'];

        session([
            'booking_tam' => $booking
        ]);

        return back()->with(
            'success',
            'Đã hủy voucher'
        );
    }
}