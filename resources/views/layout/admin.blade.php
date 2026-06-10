<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Admin')</title>

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body>

<div class="admin-shell">

    @include('block_admin.sidebar')

    <div class="admin-main">

        @include('block_admin.navbar')

        <main class="dashboard-content">

            <div class="container-fluid px-3 px-lg-4 py-4">

                @yield('content')

            </div>

        </main>

        @include('block_admin.footer')

    </div>

</div>

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>

<script>
(() => {
  const html = document.documentElement;
  const toggleBtn = document.getElementById('theme-toggle');
  // debug: ensure toggle is clickable
  // console.log('theme-toggle found:', !!toggleBtn);


  function applyTheme(theme) {
    html.setAttribute('data-theme', theme);
    html.setAttribute('data-bs-theme', theme);

    localStorage.setItem('theme', theme);
  }

  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) {
    applyTheme(savedTheme);
  } else {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(prefersDark ? 'dark' : 'light');
  }

  if (toggleBtn) {

    toggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();

      const current = (html.getAttribute('data-bs-theme') || 'light').toLowerCase();
      applyTheme(current === 'dark' ? 'light' : 'dark');

    });
  }
})();
</script>


@stack('scripts')

</body>

</html>
