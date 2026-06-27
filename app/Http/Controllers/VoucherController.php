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

        $selectedCombos = $request->input('selected_combos');

        if (!empty($selectedCombos)) {
            $decodedCombos = is_string($selectedCombos)
                ? json_decode($selectedCombos, true)
                : $selectedCombos;

            if (is_array($decodedCombos)) {
                $comboTotal = 0;

                foreach ($decodedCombos as $combo) {
                    $quantity = (int) ($combo['quantity'] ?? 0);

                    if ($quantity > 0) {
                        $comboTotal += (float) ($combo['total_price'] ?? 0);
                    }
                }

                $booking['combos'] = array_values($decodedCombos);
                $booking['total_combo_amount'] = $comboTotal;
                $booking['subtotal'] = ($booking['total_seat_amount'] ?? 0) + $comboTotal;
                $booking['total'] = max(0, $booking['subtotal'] - ($booking['discount_amount'] ?? 0));

                session()->put('booking_tam', $booking);
            }
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

        $booking = session()->get('booking_tam');
        $booking = array_merge($booking, [
            'voucher_id' => $result['voucher']->id,
            'voucher_code' => $result['voucher']->code,
            'discount_amount' => $result['discount'],
            'total' => $result['total'],
        ]);
        session(['booking_tam' => $booking]);

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