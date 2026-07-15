<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\MovieService;
use App\Services\Chatbot\ShowtimeService;
use App\Services\Chatbot\FAQService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Hàm khởi tạo (__construct)
     * Laravel sẽ tự động nhúng (inject) 3 cái Service này vào Controller khi Controller được gọi.
     * Nhờ vậy, ta có thể dùng $this->movieService để truy cập vào các hàm bên trong file MovieService.
     */
    public function __construct(
        private MovieService $movieService,
        private ShowtimeService $showtimeService,
        private FAQService $faqService,
    ) {}

    /**
     * Hàm __invoke() là một hàm đặc biệt trong PHP.
     * Khi file routes/web.php trỏ tới ChatbotController mà không chỉ định tên hàm cụ thể,
     * Laravel sẽ tự động chạy vào hàm __invoke() này đầu tiên.
     * 
     * Tham số $request chứa toàn bộ dữ liệu từ Javascript gửi lên.
     */
    public function __invoke(Request $request)
    {
        // 1. Lấy ra chữ 'action' mà Javascript gửi lên. Ví dụ: 'now_showing', 'search'...
        // Nếu JS không gửi gì cả, mặc định sẽ lấy chữ 'menu'
        $action = $request->input('action', 'menu');
        
        // 2. Lấy ra giá trị 'value' đi kèm (nếu có). Thường dùng chứa ID phim hoặc từ khóa gõ vào.
        $value = $request->input('value', '');

        try {
            // 3. Lệnh match() hoạt động giống như một người phân loại bưu kiện.
            // Nó xem chữ $action là gì, rồi gọi tới cái hàm tương ứng để xử lý.
            $response = match ($action) {
                // Nếu action là 'menu' -> Trả về danh sách nút bấm chính
                'menu' => $this->mainMenu(),

                // Nếu action là 'now_showing' -> Lệnh cho MovieService đi lấy phim đang chiếu
                'now_showing' => $this->movieService->getNowShowing(),

                // Các nhánh dành cho tính năng Tìm phim
                'search' => $this->searchMenu(), // Hiện menu hỏi muốn tìm theo tên hay thể loại
                'search_by_name' => $this->promptSearchByName(), // Lệnh JS bật khung gõ chữ
                'search_by_name_submit' => $this->movieService->searchByName($value), // Cầm chữ vừa gõ đi tìm phim
                'search_by_genre_select' => $this->movieService->getGenreButtons(), // Hiện danh sách nút thể loại
                'search_by_genre' => $this->movieService->searchByGenre((int) $value), // Tìm phim theo ID thể loại

                // Các nhánh dành cho Lịch chiếu
                'showtime_select_movie' => $this->movieService->getMovieSelectButtons('showtime_for_movie'), // Chọn phim nào?
                'showtime_for_movie' => $this->showtimeService->getDatesForMovie((int) $value), // Có phim rồi, chọn ngày nào?
                'showtime_result' => $this->handleShowtimeResult($value), // Có phim và ngày rồi, lấy lịch chiếu cuối cùng!

                // Xem chi tiết phim
                'movie_detail_select' => $this->movieService->getMovieSelectButtons('movie_detail'),
                'movie_detail' => $this->movieService->getMovieDetail((int) $value),

                // Xem Câu hỏi thường gặp
                'faq' => $this->faqService->getFAQButtons(), // Hiện danh sách câu hỏi
                'faq_answer' => $this->faqService->getAnswer($value), // In ra câu trả lời

                // Nếu không khớp với bất kỳ chữ nào ở trên, thì mặc định gọi lại menu chính
                default => $this->mainMenu(),
            };

            // 4. Biến $response lúc này đang là 1 cái mảng (Array).
            // Lệnh json() sẽ dịch cái mảng đó ra thành chuỗi JSON và gửi trả về qua dây cáp mạng cho Javascript
            return response()->json($response);

        } catch (\Exception $e) {
            // Nếu có lỗi làm sập web (như mất kết nối DB), nó sẽ nhảy vào đây
            // Ghi log lỗi lại để lập trình viên sau này xem
            \Log::error('Chatbot Error: ' . $e->getMessage(), [
                'action' => $action,
                'value' => $value,
                'trace' => $e->getTraceAsString(),
            ]);

            // Trả về câu thông báo lỗi thân thiện cho người dùng, kèm nút quay lại
            $response = [
                'type' => 'text',
                'message' => 'Xin lỗi, đã có lỗi xảy ra. Vui lòng thử lại! 😢',
                'buttons' => [['label' => '🔙 Quay lại menu', 'action' => 'menu']],
            ];

            // Nếu đang bật chế độ debug, in luôn dòng code lỗi ra cho lập trình viên dễ sửa
            if (config('app.debug')) {
                $response['debug_error'] = $e->getMessage();
            }

            return response()->json($response);
        }
    }

    /**
     * Hàm tạo Menu chính
     * Trả về mảng chứa lời chào và 5 cái nút bấm
     */
    private function mainMenu(): array
    {
        return [
            'type' => 'text',
            'message' => "Xin chào! 👋 Tôi là trợ lý ảo của **MovieZone**.\nTôi có thể hỗ trợ bạn những gì?",
            'buttons' => [
                ['label' => '🎬 Xem phim đang chiếu', 'action' => 'now_showing'],
                ['label' => '🔍 Tìm phim', 'action' => 'search'],
                ['label' => '🕒 Xem lịch chiếu', 'action' => 'showtime_select_movie'],
                ['label' => '📖 Thông tin phim', 'action' => 'movie_detail_select'],
                ['label' => '❓ Câu hỏi thường gặp', 'action' => 'faq'],
            ],
        ];
    }

    /**
     * Hàm tạo Menu Tìm kiếm
     * Hỏi người dùng muốn tìm phim bằng cách nào
     */
    private function searchMenu(): array
    {
        return [
            'type' => 'text',
            'message' => '🔍 Bạn muốn tìm phim theo cách nào?',
            'buttons' => [
                ['label' => '📝 Tìm theo tên phim', 'action' => 'search_by_name'],
                ['label' => '🎭 Tìm theo thể loại', 'action' => 'search_by_genre_select'],
                ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
            ],
        ];
    }

    /**
     * Hàm Kích hoạt chế độ nhập Text
     * Nó trả về type='prompt_input'. JS đọc được cái type này sẽ giấu các nút bấm đi và bật thanh gõ chữ lên.
     */
    private function promptSearchByName(): array
    {
        return [
            'type' => 'prompt_input',
            'message' => '📝 Vui lòng nhập tên phim bạn muốn tìm:',
            'input_action' => 'search_by_name_submit', // Dặn JS là gõ xong thì nhét action này để gửi lên lại
            'buttons' => [
                ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
            ],
        ];
    }

    /**
     * Hàm Xử lý kết quả Lịch chiếu
     * Do lúc chọn ngày, JS gửi lên $value có dạng gồm cả 2 thông tin gộp lại: "IDPhim|Ngày" (VD: "3|2026-07-15")
     * Nên ta phải cắt cái chuỗi đó làm đôi để truyền vào hàm lấy lịch chiếu cuối cùng.
     */
    private function handleShowtimeResult(string $value): array
    {
        // Lệnh explode('|') sẽ chặt đứt chuỗi ngay tại chữ '|' và trả ra 1 mảng gồm 2 mảnh
        $parts = explode('|', $value); 
        
        // Tránh lỗi nếu nhỡ chuỗi bị sai định dạng
        if (count($parts) !== 2) {
            return [
                'type' => 'text',
                'message' => 'Dữ liệu không hợp lệ.',
                'buttons' => [['label' => '🔙 Quay lại menu', 'action' => 'menu']],
            ];
        }

        // Truyền Mảnh 1 (Ép kiểu thành Số nguyên ID phim) và Mảnh 2 (Chuỗi ngày tháng) vào Service
        return $this->showtimeService->getShowtimes((int) $parts[0], $parts[1]);
    }
}
