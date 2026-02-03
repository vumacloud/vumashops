@extends('emails.layout')

@section('title', 'Your Order Has Shipped')

@section('content')
    <h2>Your Order is On Its Way!</h2>

    <div class="success">
        <strong>Order #{{ $shipping['order_number'] ?? 'N/A' }} has shipped!</strong>
    </div>

    <p>Hi {{ $shipping['customer_name'] ?? 'Customer' }},</p>

    <p>Great news! Your order has been shipped and is on its way to you.</p>

    <h3>Shipping Details</h3>
    <table>
        <tr>
            <th>Order Number</th>
            <td>#{{ $shipping['order_number'] ?? 'N/A' }}</td>
        </tr>
        @if(!empty($shipping['carrier']))
        <tr>
            <th>Carrier</th>
            <td>{{ $shipping['carrier'] }}</td>
        </tr>
        @endif
        @if(!empty($shipping['tracking_number']))
        <tr>
            <th>Tracking Number</th>
            <td>{{ $shipping['tracking_number'] }}</td>
        </tr>
        @endif
        @if(!empty($shipping['estimated_delivery']))
        <tr>
            <th>Estimated Delivery</th>
            <td>{{ $shipping['estimated_delivery'] }}</td>
        </tr>
        @endif
    </table>

    @if(!empty($shipping['tracking_url']))
    <p style="text-align: center;">
        <a href="{{ $shipping['tracking_url'] }}" class="btn">Track Your Package</a>
    </p>
    @endif

    <div class="highlight">
        <strong>Tip:</strong> Save your tracking number to monitor your delivery status.
    </div>

    <p>If you have any questions about your delivery, please contact us.</p>
@endsection

@section('footer')
    <p>Thank you for shopping with us!</p>
@endsection
