<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MFA Verification</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', Arial, sans-serif; background: #f8fafc; }
        .container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 100%; max-width: 420px; background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06); padding: 20px; }
        .title { font-size: 20px; font-weight: 600; margin: 0 0 12px; }
        .alert { padding: 8px 10px; border-radius: 8px; font-size: 14px; margin-bottom: 10px; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .input { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 10px 12px; font-size: 16px; }
        .row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .checkbox { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; color: #111827; }
        .btn { display: inline-block; padding: 10px 14px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; border: 1px solid transparent; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-outline { background: #fff; color: #111827; border-color: #d1d5db; }
        .space-y > * + * { margin-top: 12px; }
    </style>
    @csrf
    @php $status = session('status'); @endphp
    @php $codeError = $errors->first('code'); @endphp
</head>
<body>
<div class="container">
    <form method="POST" action="{{ route('security.mfa.verify.post') }}" class="card space-y">
        @csrf
        <h1 class="title">Multi‑factor verification</h1>
        @if($status)
            <div class="alert alert-success">{{ $status }}</div>
        @endif
        @if($codeError)
            <div class="alert alert-error">{{ $codeError }}</div>
        @endif
        <input type="text" name="code" class="input" placeholder="Enter the verification code">
        <label class="checkbox">
            <input type="checkbox" name="remember_device" value="1">
            <span>Remember this device</span>
        </label>
        <div class="row">
            <button type="submit" class="btn btn-primary">Verify</button>
            <button formmethod="POST" formaction="{{ route('security.mfa.resend') }}" class="btn btn-outline">Resend</button>
        </div>
    </form>
</div>
</body>
</html>
