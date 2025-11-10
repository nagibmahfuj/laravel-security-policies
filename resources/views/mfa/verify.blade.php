@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6">
    <form method="POST" action="{{ route('security.mfa.verify.post') }}" class="w-full max-w-md space-y-4">
        @csrf
        <h1 class="text-xl font-semibold">Verify</h1>
        <input type="text" name="code" class="border rounded w-full p-2" placeholder="Enter code">
        <div class="flex items-center justify-between">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Verify</button>
            <button formmethod="POST" formaction="{{ route('security.mfa.resend') }}" class="px-4 py-2 border rounded">Resend</button>
        </div>
    </form>
</div>
@endsection
