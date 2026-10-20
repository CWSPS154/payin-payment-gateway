@extends('layouts.app')

@section('title', 'Register as Merchant')

@section('content')
    <section class="auth-card panel">
        <p class="eyebrow">MERCHANT ACCESS</p>
        <h1>Register your store</h1>
        <p class="muted">Your wallet is created with a zero balance. You can publish your first offer from the merchant dashboard.</p>

        @if ($errors->any())
            <div class="alert" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('merchant.register.submit') }}" class="form-stack">
            @csrf
            <label>Owner name<input name="owner_name" value="{{ old('owner_name') }}" required autocomplete="name"></label>
            <label>Store name<input name="store_name" value="{{ old('store_name') }}" required></label>
            <label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
            <label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
            <label>Confirm password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
            <button type="submit">Create merchant account</button>
        </form>
    </section>
@endsection
