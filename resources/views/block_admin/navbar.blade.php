<nav class="navbar admin-navbar navbar-expand bg-white border-bottom py-2">
    <div class="container-fluid">
        <div class="ms-auto d-flex align-items-center gap-2">
            <button class="btn btn-light rounded-circle p-2 position-relative" style="width: 40px; height: 40px;">
                <i class="bi bi-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            </button>

            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 py-1.5 px-3 rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-5 text-secondary"></i>
                    <span class="fw-semibold">
                        @auth {{ Auth::user()->name }} @else Khách @endauth
                    </span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" style="min-width: 200px;">
                    @auth
                        <li class="dropdown-header text-dark border-bottom pb-2 mb-1">
                            <small class="text-muted d-block">Tài khoản</small>
                            <span class="fw-bold text-truncate d-block text-light">{{ Auth::user()->name }}</span>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <form action="{{ \App\Helpers\TabAuthHelper::route('logout') }}" method="GET" class="d-inline">
                                <button type="submit" class="dropdown-item text-danger py-2 d-flex align-items-center gap-2 w-100 border-0 bg-transparent">
                                    <i class="bi bi-box-arrow-right fs-5"></i>
                                    <span>Đăng xuất</span>
                                </button>
                            </form>
                        </li>
                    @else
                        <li>
                            <a href="{{ \App\Helpers\TabAuthHelper::route('login') }}" class="dropdown-item py-2 d-flex align-items-center gap-2 text-primary fw-semibold">
                                <i class="bi bi-box-arrow-in-right fs-5"></i>
                                <span>Đăng nhập</span>
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </div>
</nav>
