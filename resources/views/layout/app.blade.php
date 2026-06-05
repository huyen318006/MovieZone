<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieZone</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>
<body>

    @include('include.header')

    @yield('content')

    @include('include.footer')
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            easing: 'ease-out-cubic',
            once: true,
            offset: 100
        });
    </script>
</body>
<script>

document.addEventListener('DOMContentLoaded', () => {
    // 1. Phải import hoặc định nghĩa các module trước (nếu bạn dùng CDN hoặc ES Modules)
    // Nếu dùng bản Bundle đầy đủ, bạn cần khai báo nó trong mảng modules:
    
    new Swiper('.movieSwiper', {
        // Thêm dòng này để kích hoạt tính năng Autoplay
        modules: [Autoplay], 
        
        loop: true,
        centeredSlides: true,
        slidesPerView: 2,
        spaceBetween: 20,
        grabCursor: true,
        
        autoplay: {
            delay: 1000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },
        speed: 800,
    });
});

</script>
</html>