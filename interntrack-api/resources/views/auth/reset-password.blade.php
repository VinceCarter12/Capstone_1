<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reset Password - InternTrack</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; background: #f5f6fa; color: #111;">
<div style="max-width: 520px; margin: 48px auto; background: #fff; border: 1px solid #e6e8ee; border-radius: 12px; padding: 24px;">
    <h1 style="margin: 0 0 12px; font-size: 20px;">Reset your password</h1>
    <p style="margin: 0 0 20px; color: #555;">Enter a new password for <strong>{{ $email }}</strong>.</p>

    @if ($errors->any())
        <div style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ url('/reset-password') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div style="margin-bottom: 14px;">
            <label style="display:block; font-weight: 600; margin-bottom: 6px;">New password</label>
            <input name="password" type="password" required autocomplete="new-password"
                   style="width: 100%; padding: 10px 12px; border: 1px solid #d5d7de; border-radius: 8px;">
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display:block; font-weight: 600; margin-bottom: 6px;">Confirm password</label>
            <input name="password_confirmation" type="password" required autocomplete="new-password"
                   style="width: 100%; padding: 10px 12px; border: 1px solid #d5d7de; border-radius: 8px;">
        </div>

        <button type="submit"
                style="width: 100%; padding: 12px 14px; border: 0; border-radius: 10px; background: #111827; color: #fff; font-weight: 700; cursor: pointer;">
            Reset Password
        </button>

        <p style="margin: 14px 0 0; color: #6b7280; font-size: 12px;">If you didn’t request this, you can ignore the email.</p>
    </form>
</div>
</body>
</html>
