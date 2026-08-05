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
        // Xóa dữ liệu tin tức cũ
        Article::query()->delete();

        // Dữ liệu tin tức tương ứng với từng ảnh bạn vừa gửi
        $articles = [
            [
                'title' => 'SpongeBob: Lời Nguyền Hải Tặc dự kiến khởi chiếu dịp cuối năm',
                'summary' => 'SpongeBob trở lại với chuyến hành trình hải tặc đầy hài hước và kịch tính.',
                'content' => 'Bộ phim hoạt hình SpongeBob: Lời Nguyền Hải Tặc hứa hẹn mang đến những giây phút giải trí bùng nổ cho gia đình và trẻ em trong dịp lễ hội cuối năm.',
                'thumbnail_url' => 'articles/TT-2.webp',
                'category' => 'Tin Điện Ảnh',
            ],
            [
                'title' => 'Kung Fu Panda 4 chính thức tái xuất màn ảnh rộng',
                'summary' => 'Chú gấu Po trở lại trong hành trình mới đối đầu với Thần Thoại Biến Hình.',
                'content' => 'Thương hiệu hoạt hình đình đám DreamWorks đưa gấu Po trở lại với những trận chiến võ thuật đỉnh cao cùng sự xuất hiện của các nhân vật mới vô cùng thú vị.',
                'thumbnail_url' => 'articles/TT-3.jpg',
                'category' => 'Tin Điện Ảnh',
            ],
            [
                'title' => 'Phim kinh dị Lầu Chú Hỏa - Lời nguyện con ma nhà họ Hứa',
                'summary' => 'Tác phẩm kinh dị Việt Nam khai thác giai thoại truyền kỳ đô thị gây tò mò.',
                'content' => 'Bộ phim kinh dị do đạo diễn Hùng Trần thực hiện xoay quanh lời đồn ma mị tại căn biệt thự cổ nổi tiếng, hứa hẹn mang lại cảm giác giật gân bộc phát cho khán giả.',
                'thumbnail_url' => 'articles/TT-4.jpg',
                'category' => 'Phim Việt',
            ],
            [
                'title' => 'Hồn Ma Xác Mẹ - Phim kinh dị nặng độ chính thức tới rạp',
                'summary' => 'Tác phẩm kinh dị tâm linh đẫm máu gieo rắc nỗi sợ hãi tột cùng.',
                'content' => 'Hồn Ma Xác Mẹ (Perewangan) mang đến không khí u uất và những phân cảnh tâm linh nặng đô, dành cho những tín đồ yêu thích thể loại kinh dị cảm giác mạnh.',
                'thumbnail_url' => 'articles/TT-5.jpg',
                'category' => 'Tin Điện Ảnh',
            ],
            [
                'title' => 'Dự án phim Hoàng Tử Quỷ công bố poster bí ẩn',
                'summary' => 'Phim điện ảnh mới từ bộ đôi Trần Hữu Tấn & Hoàng Quân từ tiểu thuyết Phan Cường.',
                'content' => 'Dựa trên nguyên tác của tác giả Phan Cường, dự án Hoàng Tử Quỷ chính thức hé lộ tạo hình nhân vật độc đáo và đầy ma mị trên ngai vàng quỷ xương.',
                'thumbnail_url' => 'articles/TT-6.jpg',
                'category' => 'Phim Việt',
            ],
            [
                'title' => 'Hổ Cánh Cụt & Biệt Đội Rừng Xanh: Quậy Banh Thế Giới',
                'summary' => 'Biệt đội động vật vui nhộn trở lại trong cuộc phiêu lưu khinh khí cầu kỳ thú.',
                'content' => 'Hổ Cánh Cụt Maurice cùng các bạn lên đường giải cứu rừng xanh với những pha hành động siêu quậy và tiếng cười sảng khoái cho các bé.',
                'thumbnail_url' => 'articles/TT-7.webp',
                'category' => 'Hoạt Hình',
            ],
            [
                'title' => 'Mắt Biếc - Tình đầu, một thời cứ ngờ một đời',
                'summary' => 'Siêu phẩm chuyển thể từ tiểu thuyết Nguyễn Nhật Ánh của đạo diễn Victor Vũ.',
                'content' => 'Mắt Biếc tái hiện lại câu chuyện tình cảm nhẹ nhàng nhưng da diết giữa Ngạn và Hà Lan, cùng những thước phim rung động lòng người tại làng Đo Đo.',
                'thumbnail_url' => 'articles/TT-8.jpg',
                'category' => 'Góc Review',
            ],
            [
                'title' => 'Interstellar - Siêu phẩm viễn tưởng không gian của Christopher Nolan',
                'summary' => 'Hành trình vượt không thời gian đi tìm sự sống mới cho nhân loại.',
                'content' => 'Interstellar khẳng định vị thế kiệt tác điện ảnh viễn tưởng với cốt truyện hack não về lỗ đen, thuyết tương đối và tình cảm gia đình thiêng liêng.',
                'thumbnail_url' => 'articles/TT-9.jpg',
                'category' => 'Góc Review',
            ],
            [
                'title' => 'Chương trình khuyến mãi: Family Combo 2 tiết kiệm ngay 30K',
                'summary' => 'Thưởng thức bắp nước thả ga cùng ưu đãi đặc biệt tại Galaxy Cinema.',
                'content' => 'Nhận ngay gói combo bao gồm 2 Bắp + 4 Nước + 1 Food/Snacks với giá ưu đãi cực hấp dẫn dành cho nhóm bạn và gia đình khi xem phim.',
                'thumbnail_url' => 'articles/TT-10.jpg',
                'category' => 'Khuyến Mãi',
            ],
            [
                'title' => 'Biệt Đội Tí Hon: Báo nhất vùng, quậy tới cùng!',
                'summary' => 'Bộ phim hoạt hình rực rỡ sắc màu dành cho cả gia đình khởi chiếu dịp Giáng Sinh.',
                'content' => 'Cùng theo chân những chú lùn tí hon thông minh trong cuộc phiêu lưu bảo vệ khu vườn khỏi những thế lực phá hoại hài hước.',
                'thumbnail_url' => 'articles/TT12.jpg',
                'category' => 'Hoạt Hình',
            ],
        ];

        // Tạo bản ghi dữ liệu
        foreach ($articles as $index => $item) {
            Article::create([
                'title' => $item['title'],
                'slug' => Str::slug($item['title']),
                'summary' => $item['summary'],
                'content' => $item['content'],
                'thumbnail_url' => $item['thumbnail_url'],
                'category' => $item['category'],
                'status' => 'PUBLISHED',
                'published_at' => now()->subDays(count($articles) - $index),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}