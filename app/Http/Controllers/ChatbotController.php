<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\MovieService;
use App\Services\Chatbot\ShowtimeService;
use App\Services\Chatbot\FAQService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __construct(
        private MovieService $movieService,
        private ShowtimeService $showtimeService,
        private FAQService $faqService,
    ) {}

    /**
     * Xử lý tất cả tương tác chatbot
     * Frontend gửi: { action: string, value?: string|int }
     */
    public function __invoke(Request $request)
    {
        $action = $request->input('action', 'menu');
        $value = $request->input('value', '');

        try {
            $response = match ($action) {
                // Menu chính
                'menu' => $this->mainMenu(),

                // Chức năng 1: Xem phim đang chiếu
                'now_showing' => $this->movieService->getNowShowing(),

                // Chức năng 2: Tìm phim
                'search' => $this->searchMenu(),
                'search_by_name' => $this->promptSearchByName(),
                'search_by_name_submit' => $this->movieService->searchByName($value),
                'search_by_genre_select' => $this->movieService->getGenreButtons(),
                'search_by_genre' => $this->movieService->searchByGenre((int) $value),

                // Chức năng 3: Xem lịch chiếu
                'showtime_select_movie' => $this->movieService->getMovieSelectButtons('showtime_for_movie'),
                'showtime_for_movie' => $this->showtimeService->getDatesForMovie((int) $value),
                'showtime_result' => $this->handleShowtimeResult($value),

                // Chức năng 4: Thông tin chi tiết phim
                'movie_detail_select' => $this->movieService->getMovieSelectButtons('movie_detail'),
                'movie_detail' => $this->movieService->getMovieDetail((int) $value),

                // Chức năng 5: FAQ
                'faq' => $this->faqService->getFAQButtons(),
                'faq_answer' => $this->faqService->getAnswer($value),

                // Mặc định
                default => $this->mainMenu(),
            };

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Chatbot Error: ' . $e->getMessage(), [
                'action' => $action,
                'value' => $value,
                'trace' => $e->getTraceAsString(),
            ]);

            $response = [
                'type' => 'text',
                'message' => 'Xin lỗi, đã có lỗi xảy ra. Vui lòng thử lại! 😢',
                'buttons' => [['label' => '🔙 Quay lại menu', 'action' => 'menu']],
            ];

            if (config('app.debug')) {
                $response['debug_error'] = $e->getMessage();
            }

            return response()->json($response);
        }
    }

    /**
     * Menu chính khi mở chatbot
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
     * Menu tìm kiếm phim
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
     * Yêu cầu user nhập tên phim
     */
    private function promptSearchByName(): array
    {
        return [
            'type' => 'prompt_input',
            'message' => '📝 Vui lòng nhập tên phim bạn muốn tìm:',
            'input_action' => 'search_by_name_submit',
            'buttons' => [
                ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
            ],
        ];
    }

    /**
     * Xử lý kết quả suất chiếu (value format: movieId|date)
     */
    private function handleShowtimeResult(string $value): array
    {
        $parts = explode('|', $value);
        if (count($parts) !== 2) {
            return [
                'type' => 'text',
                'message' => 'Dữ liệu không hợp lệ.',
                'buttons' => [['label' => '🔙 Quay lại menu', 'action' => 'menu']],
            ];
        }

        return $this->showtimeService->getShowtimes((int) $parts[0], $parts[1]);
    }
}
