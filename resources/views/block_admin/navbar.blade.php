<nav class="navbar admin-navbar navbar-expand bg-white">

    <div class="container-fluid">

        <form class="d-flex w-50">

            <input
                type="text"
                class="form-control"
                placeholder="Tìm kiếm phim, vé, khách hàng...">

        </form>

        <div class="ms-auto d-flex align-items-center gap-3">

            <button class="btn btn-light">
                <i class="bi bi-bell"></i>
            </button>

            <div class="dropdown">

                <button
                    class="btn btn-light dropdown-toggle"
                    data-bs-toggle="dropdown">

                            {{-- {{ Auth::user()->name }} --}}
                            Admin

                </button>

                <ul class="dropdown-menu dropdown-menu-end">

                    <li>
                        <a class="dropdown-item" href="#">
                            Hồ sơ
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="#">
                            Đăng xuất
                        </a>
                    </li>

                </ul>

            </div>

        </div>

    </div>

</nav>
