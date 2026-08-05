<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieZone</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('styles')
</head>
<body>

    @include('include.header')

    @if(session('success'))
        <div class="flash-message-container">
            <div id="success-alert" class="alert alert-success">
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close" aria-label="Close">&times;</button>
            </div>
        </div>
    @endif

    @yield('content')

    @include('include.footer')

    @include('include.chatbot')

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
    @stack('scripts')
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

    const alertBox = document.getElementById('success-alert');
    if (alertBox) {
        const closeButton = alertBox.querySelector('.btn-close');
        const hideAlert = () => {
            alertBox.style.opacity = '0';
            const container = alertBox.parentElement;
            if (container && container.classList.contains('flash-message-container')) {
                container.style.maxHeight = '0px';
                container.style.paddingTop = '0px';
                container.style.paddingBottom = '0px';
                container.style.marginTop = '0px';
                container.style.marginBottom = '0px';
                container.style.overflow = 'hidden';
            }
            setTimeout(() => {
                if (container && container.classList.contains('flash-message-container')) {
                    container.remove();
                } else {
                    alertBox.remove();
                }
            }, 300);
        };
        if (closeButton) {
            closeButton.addEventListener('click', hideAlert);
        }
        setTimeout(hideAlert, 3000);
    }
});
</script>
</html>
