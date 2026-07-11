<?php

namespace App\Services\Chatbot;

class FAQService
{
    /**
     * Danh sách FAQ buttons
     */
    public function getFAQButtons()
    {
        $faqs = $this->getFAQs();

        $buttons = [];
        foreach ($faqs as $key => $faq) {
            $buttons[] = [
                'label' => $faq['icon'] . ' ' . $faq['question'],
                'action' => 'faq_answer',
                'value' => $key,
            ];
        }

        $buttons[] = ['label' => '🔙 Quay lại menu', 'action' => 'menu'];

        return [
            'type' => 'text',
            'message' => '❓ Chọn câu hỏi bạn muốn biết:',
            'buttons' => $buttons,
        ];
    }

    /**
     * Trả lời FAQ theo key
     */
    public function getAnswer(string $key)
    {
        $faqs = $this->getFAQs();

        if (!isset($faqs[$key])) {
            return [
                'type' => 'text',
                'message' => 'Không tìm thấy câu trả lời cho câu hỏi này.',
                'buttons' => [['label' => '🔙 Quay lại menu', 'action' => 'menu']],
            ];
        }

        return [
            'type' => 'text',
            'message' => $faqs[$key]['icon'] . ' **' . $faqs[$key]['question'] . "**\n\n" . $faqs[$key]['answer'],
            'buttons' => [
                ['label' => '❓ Câu hỏi khác', 'action' => 'faq'],
                ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
            ],
        ];
    }

    /**
     * Danh sách câu hỏi thường gặp
     */
    private function getFAQs(): array
    {
        return [
            'hours' => [
                'icon' => '🕐',
                'question' => 'Rạp mở cửa lúc mấy giờ?',
                'answer' => 'Rạp MovieZone mở cửa từ **8:00 sáng** đến **23:00 tối** mỗi ngày, kể cả cuối tuần và ngày lễ.',
            ],
            'seats' => [
                'icon' => '💺',
                'question' => 'Có những loại ghế nào?',
                'answer' => "Rạp MovieZone có **3 loại ghế**:\n• **Ghế thường (Standard)** - Ghế tiêu chuẩn, thoải mái\n• **Ghế VIP** - Ghế da cao cấp, rộng rãi hơn, vị trí đẹp\n• **Ghế Couple (Ghế đôi)** - Ghế dành cho cặp đôi, không có tay vịn ngăn cách",
            ],
            'subtitle' => [
                'icon' => '📝',
                'question' => 'Phim có phụ đề không?',
                'answer' => 'Tất cả phim nước ngoài tại MovieZone đều có **phụ đề tiếng Việt**. Một số phim có thêm tùy chọn lồng tiếng Việt.',
            ],
            'promotion' => [
                'icon' => '🎁',
                'question' => 'Có ưu đãi gì không?',
                'answer' => "MovieZone thường xuyên có các **chương trình ưu đãi**:\n• Giảm giá cho **Học sinh, Sinh viên** (xuất trình thẻ)\n• Ưu đãi cho **Thành viên VIP** của rạp\n• Happy Day - Giảm giá vào các ngày đặc biệt trong tuần\n• Combo bắp nước giá tốt khi mua kèm vé",
            ],
            'age' => [
                'icon' => '🔞',
                'question' => 'Quy định độ tuổi xem phim?',
                'answer' => "Rạp tuân thủ quy định **phân loại phim** của Cục Điện Ảnh:\n• **P** - Phim dành cho mọi đối tượng\n• **T13 (C13)** - Phim cấm khán giả dưới 13 tuổi\n• **T16 (C16)** - Phim cấm khán giả dưới 16 tuổi\n• **T18 (C18)** - Phim cấm khán giả dưới 18 tuổi\n\n⚠️ Khách hàng vui lòng mang theo **CCCD/CMND** khi xem phim giới hạn độ tuổi.",
            ],
            'cancel' => [
                'icon' => '↩️',
                'question' => 'Có thể hủy/đổi vé không?',
                'answer' => "Chính sách hủy/đổi vé:\n• Vé đã mua **không được hoàn trả** tiền mặt\n• Có thể **đổi suất chiếu** trước giờ chiếu ít nhất **2 tiếng**\n• Liên hệ quầy vé hoặc hotline để được hỗ trợ",
            ],
        ];
    }
}
