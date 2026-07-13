<?php 
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiService{


    /**
     * Gọi Gemini API để sinh câu trả lời dạng TEXT (không ép JSON).
     * Dùng cho generateResponse().
     */
    public function generateText($prompt){
        return $this->callGemini($prompt, false);
    }

    /**
     * Gọi Gemini API để sinh câu trả lời dạng JSON.
     * Dùng cho detectIntent().
     */
    public function generateJson($prompt){
        return $this->callGemini($prompt, true);
    }

    /**
     * Hàm gọi API Gemini chung.
     * @param string $prompt - Câu hỏi/prompt gửi đi
     * @param bool $jsonMode - true: ép trả về JSON, false: trả về text tự nhiên
     */
    private function callGemini($prompt, $jsonMode = false){
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model');
        $baseUrl = config('services.gemini.base_url');
        $timeout = (int) config('services.gemini.timeout', 15);

        if (!$apiKey) {
            throw new RuntimeException('Gemini API key is not configured. Please set it in config/services.php');
        }
        if (!$model) {
            throw new RuntimeException('Gemini model is not configured. Please set it in config/services.php');
        }
        if (!$baseUrl) {
            throw new RuntimeException('Gemini base URL is not configured. Please set it in config/services.php');
        }

        if (!$prompt) {
            return "Vui lòng nhập câu hỏi của bạn";
        }

        // Cấu hình generation
        $generationConfig = [
            'temperature' => 0.7,
            'maxOutputTokens' => 2048,
        ];

        // Chỉ ép JSON khi cần (cho detectIntent)
        if ($jsonMode) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        // Gửi request đến API Gemini (POST)
        $response = Http::acceptJson()
            ->timeout($timeout)
            ->withQueryParameters([
                'key' => $apiKey,
            ])
            ->post("{$baseUrl}/models/{$model}:generateContent", [
                'contents' => [ // contents phải là MẢNG các message
                    [
                        'role' => 'user', // Gemini chỉ chấp nhận 'user' hoặc 'model'
                        'parts' => [
                            ['text' => $prompt] // parts cũng phải là mảng các object
                        ]
                    ]
                ],
                'generationConfig' => $generationConfig,
            ]);

        // Log response để debug
        Log::debug('Gemini API Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        // Kiểm tra nếu gửi đi lỗi thì dừng lại luôn 
        $response->throw();

        // Lấy kết quả câu trả lời từ candidates khi chat phản hồi 
        $candidates = $response->json('candidates', []);

        if (empty($candidates)) {
            return "Không có câu trả lời từ AI";
        }

        // Lấy phương án trả lời từ candidates
        foreach($candidates as $candidate){
            $parts = data_get($candidate, 'content.parts', []);
            
            // Kiểm tra nếu KHÔNG phải mảng hoặc rỗng thì bỏ qua
            if(!is_array($parts) || empty($parts)){
                continue;
            }

            // Tạo biến lưu dữ liệu trả lời 
            $texts = [];
            // Ghép các nội dung trả về 
            foreach($parts as $part){
                $text = trim(data_get($part, 'text', ''));
                // Kiểm tra nếu không rỗng thì gộp vào 
                if($text !== ''){
                    $texts[] = $text;
                }
            }

            // Nếu nội dung trả về thì ghép thêm xuống dòng cho dễ đọc 
            if(!empty($texts)){
                return implode("\n\n", $texts); 
            }
        }

        // Nếu sau vòng lặp không tìm thấy nội dung trả về 
        return "Không có nội dung trả về từ AI";
    }

    /**
     * Phân tích ý định (intent) và thực thể (entities) từ tin nhắn người dùng.
     * Trả về mảng ['intent' => '...', 'entities' => [...]]
     */
    public function detectIntent($message)
    {
        $prompt = "Bạn là hệ thống phân tích ngôn ngữ tự nhiên cho chatbot bán vé xem phim.
Nhiệm vụ của bạn là phân loại ý định của người dùng và trích xuất các thực thể (entities).
Các ý định có thể có:
1. GREETING: Lời chào hỏi bình thường.
2. MOVIE_SEARCH: Tìm kiếm phim chung chung (VD: có phim gì, phim nào đang hot).
3. MOVIE_DETAIL: Hỏi chi tiết về một bộ phim (VD: phim Avatar dài bao lâu, ai đạo diễn phim Bố Già).
4. GENRE_SEARCH: Tìm phim theo thể loại (VD: cho xem phim kinh dị, có phim hài không).
5. SHOWTIME_SEARCH: Tìm suất chiếu (VD: tìm suất chiếu Avatar, ngày mai có phim gì).
6. FAQ: Câu hỏi thường gặp (giờ mở cửa, loại ghế, ưu đãi...).

Hãy trả về CHỈ MỘT chuỗi JSON hợp lệ, không có markdown, có cấu trúc như sau:
{
    \"intent\": \"<TÊN_INTENT>\",
    \"entities\": {
        \"movie_name\": \"<tên phim nếu có>\",
        \"genre\": \"<thể loại nếu có>\",
        \"date\": \"<ngày tháng nếu có, vd: hôm nay, ngày mai>\"
    }
}

Câu của người dùng: \"{$message}\"";

        $response = $this->generateJson($prompt);
        
        // Làm sạch response để đảm bảo là JSON hợp lệ
        $response = trim($response);
        $response = str_replace(['```json', '```'], '', $response);
        
        $data = json_decode(trim($response), true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        Log::warning('Gemini detectIntent returned invalid JSON', ['response' => $response]);

        return [
            'intent' => 'FAQ',
            'entities' => []
        ];
    }

    /**
     * Sinh câu trả lời tự nhiên dựa trên context đã build.
     */
    public function generateResponse($message, $context)
    {
        $prompt = "Bạn là trợ lý ảo thân thiện và chuyên nghiệp của rạp chiếu phim MovieZone.
Nhiệm vụ của bạn là trả lời câu hỏi của khách hàng dựa BẮT BUỘC vào ngữ cảnh được cung cấp dưới đây. 
Nếu thông tin không có trong ngữ cảnh, hãy xin lỗi và nói rằng bạn chưa có thông tin đó, tuyệt đối KHÔNG tự bịa ra phim, suất chiếu hay thông tin khác.
Trình bày câu trả lời rõ ràng, dễ đọc, có thể dùng icon (emoji) cho sinh động.

--- NGỮ CẢNH TỪ HỆ THỐNG ---
{$context}

--- CÂU HỎI CỦA KHÁCH HÀNG ---
\"{$message}\"";

        return $this->generateText($prompt);
    }
    
}