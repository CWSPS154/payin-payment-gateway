@extends('layouts.app')

@section('title', ucfirst(strtolower($role)).' Login')

@section('content')
    <section class="auth-card panel">
        <p class="eyebrow">{{ $role }} ACCESS</p>
        <h1>{{ ucfirst(strtolower($role)) }} login</h1>
        <p class="muted">Use the seeded demo account or enter your credentials.</p>

        @if ($errors->any())
            <div class="alert" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route($loginRoute) }}" class="form-stack">
            @csrf
            <label>Email<input type="email" name="email" value="{{ old('email', $demo['email']) }}" required autocomplete="username"></label>
            <label>Password<input type="password" name="password" value="{{ $demo['password'] }}" required autocomplete="current-password"></label>
            <label class="checkbox"><input type="checkbox" name="remember" value="1"> Remember me</label>
            <button type="submit">Login</button>
        </form>

        @if ($role === \App\Models\User::ROLE_MERCHANT)
            <p class="muted">New here? <a href="{{ route('merchant.register') }}">Register your store</a>.</p>
        @endif
    </section>
@endsection
