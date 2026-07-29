@extends('layout.app')

@section('title', 'Membership')

@section('content')
<div class="container py-5">
    <div class="card bg-dark text-white p-4">
        <h2>Membership của {{ $user->name }}</h2>
        <p>Hạng hiện tại: {{ $currentLevel ? $currentLevel->name : 'BRONZE' }}</p>
        <p>Số dư Coin: {{ number_format($coins) }}</p>
    </div>
</div>
@endsection