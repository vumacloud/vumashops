@extends('emails.layout')

@section('title', 'Payment Received')

@section('content')
    <h2>Payment Received</h2>

    <div class="success">
        <strong>Payment Successful!</strong>
    </div>

    <p>Hi {{ $payment['customer_name'] ?? 'Customer' }},</p>

    <p>We've received your payment. Thank you!</p>

    <h3>Payment Details</h3>
    <table>
        <tr>
            <th>Order Number</th>
            <td>#{{ $payment['order_number'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Amount Paid</th>
            <td>{{ $payment['currency'] ?? 'KES' }} {{ number_format($payment['amount'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <th>Payment Method</th>
            <td>{{ $payment['payment_method'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Date</th>
            <td>{{ $payment['date'] ?? now()->format('M d, Y H:i') }}</td>
        </tr>
        @if(!empty($payment['transaction_id']))
        <tr>
            <th>Transaction ID</th>
            <td>{{ $payment['transaction_id'] }}</td>
        </tr>
        @endif
    </table>

    <p>Your order is now being processed and you'll receive a shipping notification once it's on its way.</p>

    <p>If you have any questions about your payment, please contact us.</p>
@endsection

@section('footer')
    <p>This is your payment receipt. Please keep it for your records.</p>
@endsection
