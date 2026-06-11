@extends('layout.app')

@section('content')

<section class="confirm-page">
    <div class="confirm-container">

        <div class="confirm-header">
            <h2>XÁC NHẬN ĐẶT VÉ</h2>
            <p>Kiểm tra lại thông tin trước khi thanh toán</p>
        </div>

        <div class="confirm-content">

            {{-- LEFT --}}
            <div class="confirm-movie">

                <img src="{{ asset('assets/hero/avatar.jpg') }}"
                     alt="Movie"
                     class="movie-poster">

                <div class="movie-info">

                    <h3>{{ $showtime->movie->title }}</h3>

                    <div class="info-item">
                        <i class="fa-solid fa-building"></i>
                        {{ $showtime->cinema->name }}
                    </div>

                    <div class="info-item">
                        <i class="fa-solid fa-door-open"></i>
                        {{ $showtime->room->name }}
                    </div>

                    <div class="info-item">
                        <i class="fa-solid fa-clock"></i>
                        {{ \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') }}
                    </div>

                </div>

            </div>

            {{-- RIGHT --}}
            <div class="confirm-ticket">

                <div class="ticket-section">
                    <h4>Ghế đã chọn</h4>

                    <div class="seat-list">
                        @foreach($seats as $seat)
                            <span class="seat-badge">
                                {{ $seat->seat->seat_code ?? $seat->seat_code }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="ticket-section">
                    <h4>Tổng thanh toán</h4>

                    <div class="total-price">
                        {{ number_format($totalPrice,0,',','.') }}đ
                    </div>
                </div>

            </div>

        </div>

        <form action="{{ route('booking.checkout') }}" method="POST">
            @csrf

            <div class="payment-method-box">

                <h4>
                    <i class="fa-solid fa-credit-card"></i>
                    Chọn phương thức thanh toán
                </h4>

                <label class="payment-option">
                    <input type="radio"
                           name="payment_method"
                           value="ONLINE"
                           checked>

                    <span>
                        💳 Thanh toán Online
                    </span>
                </label>

                <label class="payment-option">
                    <input type="radio"
                           name="payment_method"
                           value="CASH">

                    <span>
                        💵 Thanh toán tại quầy
                    </span>
                </label>

            </div>

            <div class="confirm-actions">

                <a href="{{ route('booking.seat',['showtime_id'=>$showtime->id]) }}"
                   class="btn-back">

                    <i class="fa-solid fa-arrow-left"></i>
                    Quay lại chọn ghế
                </a>

                <button type="submit"
                        class="btn-confirm">

                    Xác nhận & Thanh toán
                    <i class="fa-solid fa-check"></i>

                </button>

            </div>

        </form>

    </div>
</section>

<style>

.confirm-page{
    padding:60px 20px;
    background:#0f172a;
    min-height:100vh;
}

.confirm-container{
    max-width:1100px;
    margin:auto;
    background:#fff;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 20px 40px rgba(0,0,0,.2);
}

.confirm-header{
    background:linear-gradient(135deg,#e50914,#b20710);
    color:#fff;
    text-align:center;
    padding:30px;
}

.confirm-header h2{
    margin:0;
    font-size:32px;
    font-weight:700;
}

.confirm-header p{
    margin-top:8px;
    opacity:.9;
}

.confirm-content{
    display:grid;
    grid-template-columns:1.2fr .8fr;
    gap:30px;
    padding:30px;
}

.confirm-movie{
    display:flex;
    gap:20px;
}

.movie-poster{
    width:220px;
    height:320px;
    object-fit:cover;
    border-radius:15px;
}

.movie-info{
    flex:1;
}

.movie-info h3{
    margin-bottom:20px;
    font-size:28px;
}

.info-item{
    background:#f8fafc;
    padding:15px;
    border-radius:10px;
    margin-bottom:10px;
    font-weight:600;
}

.confirm-ticket{
    background:#f8fafc;
    border-radius:15px;
    padding:25px;
}

.ticket-section{
    margin-bottom:30px;
}

.ticket-section h4{
    margin-bottom:15px;
}

.seat-list{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}

.seat-badge{
    background:#e50914;
    color:white;
    padding:8px 15px;
    border-radius:8px;
    font-weight:bold;
}

.total-price{
    color:#e50914;
    font-size:38px;
    font-weight:700;
}

.payment-method-box{
    margin:0 30px 30px;
    padding:25px;
    border:2px solid #eee;
    border-radius:15px;
}

.payment-option{
    display:block;
    padding:15px;
    margin-top:10px;
    background:#f8fafc;
    border-radius:10px;
    cursor:pointer;
}

.payment-option:hover{
    background:#eef2ff;
}

.confirm-actions{
    display:flex;
    justify-content:space-between;
    padding:0 30px 30px;
}

.btn-back{
    background:#64748b;
    color:white;
    padding:14px 25px;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
}

.btn-confirm{
    background:#e50914;
    color:white;
    border:none;
    padding:14px 30px;
    border-radius:10px;
    font-size:16px;
    font-weight:700;
    cursor:pointer;
}

.btn-confirm:hover{
    background:#c50710;
}

@media(max-width:768px){

    .confirm-content{
        grid-template-columns:1fr;
    }

    .confirm-movie{
        flex-direction:column;
        align-items:center;
    }

    .movie-poster{
        width:100%;
        max-width:300px;
    }

    .confirm-actions{
        flex-direction:column;
        gap:15px;
    }

    .btn-back,
    .btn-confirm{
        width:100%;
        text-align:center;
    }
}
.confirm-container{
    background:#ffffff;
    color:#111827;
}
</style>

@endsection