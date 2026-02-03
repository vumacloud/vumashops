@extends('emails.layout')

@section('title', 'Order Confirmed')
@section('header', $order['store_name'] ?? 'VumaShops')

@section('content')
    <h2>Thank you for your order!</h2>

    <div class="success">
        <strong>Order #{{ $order['order_number'] }}</strong> has been confirmed.
    </div>

    <p>Hi {{ $order['customer_name'] }},</p>

    <p>We've received your order and are getting it ready. We'll notify you when it ships.</p>

    <h3>Order Details</h3>
    <table>
        <tr>
            <th>Order Number</th>
            <td>#{{ $order['order_number'] }}</td>
        </tr>
        <tr>
            <th>Order Date</th>
            <td>{{ $order['date'] ?? now()->format('M d, Y') }}</td>
        </tr>
        <tr>
            <th>Payment Method</th>
            <td>{{ $order['payment_method'] ?? 'N/A' }}</td>
        </tr>
    </table>

    @if(!empty($order['items']))
    <h3>Items Ordered</h3>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order['items'] as $item)
            <tr>
                <td>{{ $item['name'] ?? 'Product' }}</td>
                <td>{{ $item['quantity'] ?? 1 }}</td>
                <td>{{ $order['currency'] ?? 'KES' }} {{ number_format($item['price'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="highlight">
        <strong>Order Total: {{ $order['currency'] ?? 'KES' }} {{ number_format($order['total'] ?? 0, 2) }}</strong>
    </div>

    @if(!empty($order['store_url']))
    <p style="text-align: center;">
        <a href="{{ $order['store_url'] }}" class="btn">View Your Order</a>
    </p>
    @endif

    <p>If you have any questions, please don't hesitate to contact us.</p>
@endsection

@section('footer')
    <p>This email was sent to confirm your order at {{ $order['store_name'] ?? 'our store' }}.</p>
@endsection
