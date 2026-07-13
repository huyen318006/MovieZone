<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiChatbotController extends Controller
{
    //

    public function __construct(private GeminiService $geminiService){
        
    }

    //xử lý tin nhắn  chat từ khách hàng  và trả về câu trả lời
    public function __invoke(Request $request){
        $message = $request->input('message');
        
        if (!$message) {
            return response()->json(['reply' => 'Vui lòng nhập tin nhắn của bạn.']);
        }

        try {
            // 1. Phân tích ý định và thực thể bằng Gemini
            $analysis = $this->geminiService->detectIntent($message);
            $intent = $analysis['intent'] ?? 'FAQ';
            $entities = $analysis['entities'] ?? [];

            $context = '';

            // 2. Truy vấn dữ liệu dựa trên ý định
            switch ($intent) {
                case 'MOVIE_SEARCH':
                    // Tìm các phim đang chiếu
                    $movies = \App\Models\Movie::where('status', 'SHOWING')
                        ->select('title', 'duration_minutes', 'age_rating', 'release_date', 'description')
                        ->take(10)
                        ->get();
                    $context = "Danh sách phim đang chiếu tại rạp:\n" . $movies->toJson(JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'GENRE_SEARCH':
                    $genreName = $entities['genre'] ?? '';
                    if ($genreName) {
                        $movies = \App\Models\Movie::whereHas('genres', function($q) use ($genreName) {
                            $q->where('name', 'like', "%{$genreName}%");
                        })->where('status', 'SHOWING')
                        ->select('title', 'duration_minutes', 'age_rating')
                        ->take(5)
                        ->get();
                        
                        $context = "Danh sách phim thuộc thể loại '{$genreName}' đang chiếu:\n";
                        $context .= $movies->isEmpty() ? "Hiện không có phim nào thuộc thể loại này." : $movies->toJson(JSON_UNESCAPED_UNICODE);
                    } else {
                        $context = "Bạn vui lòng cho biết cụ thể thể loại phim (hành động, kinh dị, hài...) bạn muốn xem nhé.";
                    }
                    break;

                case 'MOVIE_DETAIL':
                    $movieName = $entities['movie_name'] ?? '';
                    if ($movieName) {
                        $movie = \App\Models\Movie::with('genres')
                            ->where('title', 'like', "%{$movieName}%")
                            ->first();
                        
                        if ($movie) {
                            $context = "Thông tin chi tiết phim '{$movieName}':\n" . $movie->toJson(JSON_UNESCAPED_UNICODE);
                        } else {
                            $context = "Hiện tại rạp không có thông tin về phim '{$movieName}'. Bạn vui lòng kiểm tra lại tên phim nhé.";
                        }
                    } else {
                        $context = "Bạn vui lòng cho biết tên bộ phim bạn muốn tìm hiểu.";
                    }
                    break;

                case 'SHOWTIME_SEARCH':
                    $movieName = $entities['movie_name'] ?? '';
                    $dateEntity = $entities['date'] ?? 'hôm nay';
                    
                    // Xác định ngày truy vấn
                    $searchDate = now();
                    if (str_contains(strtolower($dateEntity), 'mai')) {
                        $searchDate = now()->addDay();
                    }
                    
                    if ($movieName) {
                        $movie = \App\Models\Movie::where('title', 'like', "%{$movieName}%")->first();
                        if ($movie) {
                            $showtimes = \App\Models\Showtime::with('room')
                                ->where('movie_id', $movie->id)
                                ->whereDate('start_time', $searchDate)
                                ->where('start_time', '>', now())
                                ->orderBy('start_time')
                                ->take(5)
                                ->get();
                                
                            $context = "Các suất chiếu của phim '{$movie->title}' vào ngày {$searchDate->format('d/m/Y')}:\n";
                            if ($showtimes->isEmpty()) {
                                $context .= "Hiện không có suất chiếu nào phù hợp.";
                            } else {
                                foreach($showtimes as $st) {
                                    $context .= "- LÚc " . $st->start_time->format('H:i') . " tại phòng " . $st->room->name . ".\n";
                                }
                            }
                        } else {
                            $context = "Không tìm thấy phim '{$movieName}' để xem suất chiếu.";
                        }
                    } else {
                        // Nếu không nói tên phim, lấy suất chiếu chung trong ngày
                        $showtimes = \App\Models\Showtime::with(['movie', 'room'])
                            ->whereDate('start_time', $searchDate)
                            ->where('start_time', '>', now())
                            ->orderBy('start_time')
                            ->take(10)
                            ->get();
                            
                        $context = "Các suất chiếu sắp tới vào ngày {$searchDate->format('d/m/Y')}:\n";
                        if ($showtimes->isEmpty()) {
                            $context .= "Hiện không có suất chiếu nào.";
                        } else {
                            foreach($showtimes as $st) {
                                $context .= "- Phim '{$st->movie->title}' lúc " . $st->start_time->format('H:i') . " tại phòng " . $st->room->name . ".\n";
                            }
                        }
                    }
                    break;

                case 'GREETING':
                    $context = "Hãy chào khách hàng một cách thân thiện, giới thiệu bạn là trợ lý ảo của MovieZone và hỏi xem bạn có thể giúp gì (tìm phim, lịch chiếu, giá vé...).";
                    break;

                case 'FAQ':
                default:
                    $context = "Thông tin chung rạp MovieZone:
- Rạp mở cửa từ 8:00 sáng đến 23:00 tối mỗi ngày.
- Rạp có các loại ghế: Ghế thường, Ghế VIP, và Ghế Couple (ghế đôi).
- Các phim đều có phụ đề tiếng Việt đối với phim nước ngoài.
- Rạp tuân thủ quy định độ tuổi (C13, C16, C18) theo quy định của Cục Điện Ảnh. Khách hàng cần mang the CCCD/CMND khi xem phim giới hạn độ tuổi.
- Thường xuyên có các chương trình khuyến mãi cho học sinh, sinh viên và thành viên rạp.";
                    break;
            }

            // 3. Yêu cầu Gemini tạo câu trả lời dựa trên context đã build
            $reply = $this->geminiService->generateResponse($message, $context);
            
            return response()->json([
                'reply' => $reply,
                // Uncomment line below to debug intent & context during development
                // 'debug' => ['intent' => $intent, 'entities' => $entities, 'context' => $context]
            ]);

        } catch (\Exception $e) {
            Log::error('Chatbot Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'message' => $message,
            ]);
            
            $response = [
                'reply' => 'Xin lỗi, hệ thống AI của rạp đang được bảo trì hoặc quá tải. Vui lòng thử lại sau giây lát.'
            ];

            // Trong môi trường development, trả thêm thông tin debug
            if (config('app.debug')) {
                $response['debug_error'] = $e->getMessage();
            }

            return response()->json($response);
        }
    } 
}
