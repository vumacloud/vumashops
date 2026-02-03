@extends('emails.layout')

@section('title', 'Reset Your Password')

@section('content')
    <h2>Password Reset Request</h2>

    @if($name)
    <p>Hi {{ $name }},</p>
    @endif

    <p>We received a request to reset your password. Click the button below to choose a new password:</p>

    <p style="text-align: center;">
        <a href="{{ $resetLink }}" class="btn">Reset Password</a>
    </p>

    <div class="highlight">
        <strong>This link will expire in 60 minutes.</strong>
    </div>

    <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>

    <p style="font-size: 12px; color: #666;">
        If the button above doesn't work, copy and paste this link into your browser:<br>
        <a href="{{ $resetLink }}">{{ $resetLink }}</a>
    </p>
@endsection

@section('footer')
    <p>This is an automated security email. Please do not reply.</p>
@endsection
