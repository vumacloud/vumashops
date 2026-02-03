@extends('emails.layout')

@section('title', 'Welcome!')
@section('header', $user['store_name'] ?? 'VumaShops')

@section('content')
    <h2>Welcome, {{ $user['name'] }}!</h2>

    <p>Thank you for creating an account with us. We're excited to have you!</p>

    <div class="success">
        Your account has been created successfully.
    </div>

    <p>With your account, you can:</p>
    <ul>
        <li>Track your orders</li>
        <li>Save your delivery addresses</li>
        <li>Get exclusive offers and updates</li>
        <li>Shop faster with saved payment methods</li>
    </ul>

    @if(!empty($user['store_url']))
    <p style="text-align: center;">
        <a href="{{ $user['store_url'] }}" class="btn">Start Shopping</a>
    </p>
    @endif

    <p>If you have any questions, we're here to help!</p>
@endsection

@section('footer')
    <p>Welcome to {{ $user['store_name'] ?? 'our store' }}!</p>
@endsection
