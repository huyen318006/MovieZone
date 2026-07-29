<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Coin;
use App\Models\DailyCheckin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CoinController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function coinIndex($id)
    {

        //trả về coin của user có id = $id
        $coin = Coin::where('user_id', $id)->first();

        // Đã điểm danh hôm nay chưa
        $checkedToday = DailyCheckin::where('user_id', $id)
            ->whereDate('checkin_date', Carbon::today())
            ->exists();

            //tạo biến lưu chuỗi
            $streak = 0;
            //nếu chưa điểm danh thì bắt đầu từ hôm nay chưa nếu chưa thì bắt đầu đếm từ hôm qua
            $data = $checkedToday ? Carbon::today() : Carbon::yesterday();

            //kiểm tra chuỗi điểm danh
            while(DailyCheckin::where('user_id', $id)->whereDate('checkin_date', $data)->exists()) {
                //bắt đàu đếm chuỗi điểm danh
                $streak++;
                //giảm ngày đi 1 ngày để kiểm tra các ngày trước đó đã điểm danh chưa nếu có thì lặp lại while để + chuỗi
                $data->subDay();
            }

            $rewardTable = [
                1 => 100,
                2 => 150,
                3 => 200,
                4 => 200,
                5 => 200,
                6 => 200,
                7 => 200,
            ];

            // Tính bước cho hôm nay:
            $todayStep = $checkedToday ? $streak : ($streak + 1);
            $todayStep = max(1, min(7, (int) $todayStep));

            $todayReward = $rewardTable[$todayStep] ?? $rewardTable[1];

            return view('coin.index', compact('coin', 'checkedToday', 'streak', 'rewardTable', 'todayStep', 'todayReward'));
    }

    public function checkin(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->id != (int) $id) {
            abort(403);
        }

        $validated = $request->validate([
            'reward_coin' => 'required|integer',
            'todayStep' => 'required|integer|min:1|max:7',
            'checkin_date' => 'required|date',
        ]);

        $today = Carbon::parse($validated['checkin_date'])->startOfDay();
        $alreadyChecked = DailyCheckin::where('user_id', $id)
            ->whereDate('checkin_date', $today)
            ->exists();

        if ($alreadyChecked) {
            return redirect()->back()
                ->with('error', 'Bạn đã điểm danh hôm nay.');
        }

        $rewardTable = [
            1 => 100,
            2 => 150,
            3 => 200,
            4 => 200,
            5 => 200,
            6 => 200,
            7 => 200,
        ];

        $todayReward = (int) $validated['reward_coin'];
        if (!in_array($todayReward, $rewardTable, true)) {
            $todayStep = max(1, min(7, (int) $validated['todayStep']));
            $todayReward = $rewardTable[$todayStep] ?? $rewardTable[1];
        }

        DailyCheckin::create([
            'user_id' => $id,
            'checkin_date' => $today,
            'reward_coin' => $todayReward,
        ]);

        $coin = Coin::firstOrCreate(['user_id' => $id], ['balance' => 0]);
        $coin->increment('balance', $todayReward);

        //ghi lại audit
        DB::table('audit_logs')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'checkin_daily',
            'entity_name' => 'users',
            'entity_id'   => $id,
            'old_value'   => null,
            'new_value'   => json_encode(['reward_coin' => $todayReward]),
            'created_at'  => now(),
        ]);


        return redirect()->back()
            ->with('success', "Bạn đã điểm danh thành công và nhận {$todayReward} Coin!");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
