<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Article::query()->delete();

        // Danh sách chính xác 11 file ảnh trong thư mục storage/app/public/articles
        $images = [
            'articles/TT-1.webp',
            'articles/TT-2.webp',
            'articles/TT-3.jpg',
            'articles/TT-4.jpg',
            'articles/TT-5.jpg',
            'articles/TT-6.jpg',
            'articles/TT-7.webp',
            'articles/TT-8.jpg',
            'articles/TT-9.jpg',
            'articles/TT-10.jpg',
            'articles/TT12.jpg',
        ];

        // Tiêu đề gợi ý phù hợp với nội dung ảnh
        $titles = [
            'Kung Fu Panda 4 chính thức trở lại màn ảnh rộng',
            'Lầu Chú Hỏa - Lời nguyện con ma nhà họ Hứa',
            'Hồn Ma Xác Mẹ - Phim kinh dị nặng đô chính thức tới rạp',
            'Hoàng Tử Quỷ - Kẻ tôn thờ quỷ xương cuồng hạ sinh',
            'Hổ Cánh Cụt & Biệt Đội Rừng Xanh quậy banh thế giới',
            'Mắt Biếc - Tình đầu một thời cứ ngỡ một đời',
            'Interstellar - Tuyệt tác khoa học viễn tưởng',
            'Family Combo 2 - Tiết kiệm 30K cực hấp dẫn tại Galaxy Cinema',
            'Khám phá ưu đãi vé phim hè cực hot',
            'Biệt Đội Tí Hon - Khởi chiếu bùng nổ dịp Giáng Sinh',
            'Tin tức điện ảnh cập nhật mới nhất',
        ];

        foreach ($images as $index => $imagePath) {
            $title = $titles[$index] ?? 'Tin tức điện ảnh ' . ($index + 1);

            Article::create([
                'title'        => $title,
                'slug'         => Str::slug($title) . '-' . ($index + 1),
                'summary'      => 'Tóm tắt nội dung hấp dẫn dành cho bài viết: ' . $title,
                'content'      => 'Chi tiết nội dung bài viết về ' . $title . '. Khám phá ngay những thông tin nổi bật nhất!',
                'thumbnail_url' => $imagePath,
                'category'     => 'Entertainment',
                'status'       => 'PUBLISHED',
                'published_at' => now()->subDays(11 - $index),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}