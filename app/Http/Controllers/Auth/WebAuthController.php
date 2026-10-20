<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    public function showAdminLogin(): View|RedirectResponse
    {
        return $this->showLogin(User::ROLE_ADMIN);
    }

    public function showMerchantLogin(): View|RedirectResponse
    {
        return $this->showLogin(User::ROLE_MERCHANT);
    }

    public function showLogin(string $role): View|RedirectResponse
    {
        if (Auth::check()) {
            abort_unless(Auth::user()->role === $role, 403);

            return redirect($role === User::ROLE_ADMIN ? '/admin/payments' : '/merchant/dashboard');
        }

        $demo = config('demo.'.strtolower($role));

        return view('auth.login', [
            'role' => $role,
            'demo' => $demo,
            'loginRoute' => $role === User::ROLE_ADMIN ? 'admin.login.submit' : 'merchant.login.submit',
        ]);
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        return $this->login($request, User::ROLE_ADMIN);
    }

    public function merchantLogin(Request $request): RedirectResponse
    {
        return $this->login($request, User::ROLE_MERCHANT);
    }

    public function login(Request $request, string $role): RedirectResponse
    {
        if (Auth::check()) {
            abort_unless(Auth::user()->role === $role, 403);

            return redirect($role === User::ROLE_ADMIN ? '/admin/payments' : '/merchant/dashboard');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials + ['role' => $role], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match this account type.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect($role === User::ROLE_ADMIN ? '/admin/payments' : '/merchant/dashboard');
    }

    public function showMerchantRegistration(): View
    {
        abort_if(Auth::check(), 403);

        return view('auth.register-merchant');
    }

    public function registerMerchant(Request $request): RedirectResponse
    {
        abort_if(Auth::check(), 403);

        $validated = $request->validate([
            'owner_name' => ['required', 'string', 'max:255'],
            'store_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $merchant = Merchant::create(['name' => $validated['store_name']]);
            $merchant->wallet()->create(['balance' => '0.00']);

            return User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => User::ROLE_MERCHANT,
                'merchant_id' => $merchant->id,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/merchant/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
