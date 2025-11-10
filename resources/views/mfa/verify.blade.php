@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6">
    <form method="POST" action="{{ route('security.mfa.verify.post') }}" class="w-full max-w-md space-y-4">
        @csrf
        <h1 class="text-xl font-semibold">Verify</h1>
        @if(session('status'))
            <div class="p-2 bg-green-100 border border-green-300 text-green-800 rounded">{{ session('status') }}</div>
        @endif
        @error('code')
            <div class="p-2 bg-red-100 border border-red-300 text-red-800 rounded">{{ $message }}</div>
        @enderror
        <input type="text" name="code" class="border rounded w-full p-2" placeholder="Enter code">
        <label class="flex items-center space-x-2">
            <input type="checkbox" name="remember_device" value="1" class="border rounded">
            <span>Remember this device</span>
        </label>
        <div class="flex items-center justify-between">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Verify</button>
            <button formmethod="POST" formaction="{{ route('security.mfa.resend') }}" class="px-4 py-2 border rounded">Resend</button>
        </div>
    </form>
</div>
@endsection
