<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_and_merchant_users_are_seeded_with_hashed_passwords(): void
    {
        Artisan::call('db:seed');

        $admin = DB::table('users')->where('email', config('demo.admin.email'))->first();
        $merchantUser = DB::table('users')->where('email', config('demo.merchant.email'))->first();

        $this->assertNotNull($admin);
        $this->assertSame('ADMIN', $admin->role);
        $this->assertTrue(Hash::check(config('demo.admin.password'), $admin->password));
        $this->assertNotNull($merchantUser);
        $this->assertSame('MERCHANT', $merchantUser->role);
        $this->assertNotNull($merchantUser->merchant_id);
        $this->assertTrue(Hash::check(config('demo.merchant.password'), $merchantUser->password));
    }

    public function test_seeded_merchant_has_public_offer_and_expected_wallet(): void
    {
        Artisan::call('db:seed');

        $merchant = Merchant::where('name', 'ABC Store')->firstOrFail();

        $this->assertSame('Demo Item', $merchant->offer_name);
        $this->assertSame('1000.00', $merchant->offer_amount);
        $this->assertSame('20000.00', $merchant->wallet->balance);
    }

    public function test_rerunning_database_seeder_does_not_reset_existing_wallet_balance(): void
    {
        Artisan::call('db:seed');
        $merchant = Merchant::where('name', 'ABC Store')->firstOrFail();
        $merchant->wallet->update(['balance' => '345.67']);

        Artisan::call('db:seed');

        $this->assertSame('345.67', $merchant->fresh()->wallet->balance);
    }

    public function test_home_page_offers_admin_merchant_and_customer_paths(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Admin Login')
            ->assertSee('Merchant Login')
            ->assertSee('Register as Merchant')
            ->assertSee('Pay as Customer');
    }

    public function test_home_page_shows_only_the_signed_in_role_dashboard_and_keeps_customer_payments(): void
    {
        Artisan::call('db:seed');
        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $merchant = User::where('role', User::ROLE_MERCHANT)->firstOrFail();

        $this->actingAs($admin)->get('/')
            ->assertOk()
            ->assertSee('Admin Dashboard')
            ->assertDontSee('Admin Login')
            ->assertDontSee('Merchant Login')
            ->assertSee('Pay as Customer');

        auth()->logout();
        $this->actingAs($merchant)->get('/')
            ->assertOk()
            ->assertSee('Merchant Dashboard')
            ->assertDontSee('Admin Login')
            ->assertDontSee('Merchant Login')
            ->assertSee('Pay as Customer');
    }

    public function test_authenticated_user_cannot_open_other_role_login_or_merchant_registration(): void
    {
        Artisan::call('db:seed');
        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $merchant = User::where('role', User::ROLE_MERCHANT)->firstOrFail();

        $this->actingAs($admin)->get('/admin/login')->assertRedirect('/admin/payments');
        $this->get('/merchant/login')->assertForbidden();
        $this->get('/merchant/register')->assertForbidden();

        auth()->logout();
        $this->actingAs($merchant)->get('/merchant/login')->assertRedirect('/merchant/dashboard');
        $this->get('/admin/login')->assertForbidden();
    }

    public function test_admin_and_merchant_can_log_in_with_seeded_credentials(): void
    {
        Artisan::call('db:seed');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee(config('demo.admin.email'))
            ->assertSee('value="'.config('demo.admin.password').'"', false);
        $this->post('/admin/login', [
            'email' => config('demo.admin.email'),
            'password' => config('demo.admin.password'),
        ])->assertRedirect('/admin/payments');
        $this->assertAuthenticatedAs(User::where('email', config('demo.admin.email'))->firstOrFail());

        $this->post('/logout')->assertRedirect('/');
        $this->get('/merchant/login')
            ->assertOk()
            ->assertSee(config('demo.merchant.email'))
            ->assertSee('value="'.config('demo.merchant.password').'"', false);
        $this->post('/merchant/login', [
            'email' => config('demo.merchant.email'),
            'password' => config('demo.merchant.password'),
        ])->assertRedirect('/merchant/dashboard');
        $this->assertAuthenticatedAs(User::where('email', config('demo.merchant.email'))->firstOrFail());
    }

    public function test_login_rejects_credentials_for_the_wrong_role(): void
    {
        Artisan::call('db:seed');

        $this->post('/admin/login', [
            'email' => config('demo.merchant.email'),
            'password' => config('demo.merchant.password'),
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_the_matching_login_for_protected_routes(): void
    {
        $this->get('/admin/payments')->assertRedirect('/admin/login');
        $this->get('/merchant/dashboard')->assertRedirect('/merchant/login');
    }

    public function test_merchant_registration_creates_linked_user_merchant_and_wallet(): void
    {
        $this->get('/merchant/register')->assertOk();

        $this->post('/merchant/register', [
            'owner_name' => 'New Owner',
            'store_name' => 'New Store',
            'email' => 'owner@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect('/merchant/dashboard');

        $merchant = Merchant::where('name', 'New Store')->firstOrFail();
        $user = User::where('email', 'owner@example.com')->firstOrFail();

        $this->assertSame('MERCHANT', $user->role);
        $this->assertSame($merchant->id, $user->merchant_id);
        $this->assertSame('0.00', $merchant->wallet->balance);
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_dashboard_shows_all_payments_after_admin_login(): void
    {
        Artisan::call('db:seed');
        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $firstMerchant = Merchant::where('name', 'ABC Store')->firstOrFail();
        $secondMerchant = $this->makeMerchant('Second Store', 'Second Offer', '75.00', '0.00');
        $this->makePayment($firstMerchant, 'ORD-ADMIN-ONE', '10.00');
        $this->makePayment($secondMerchant, 'ORD-ADMIN-TWO', '75.00');

        $this->actingAs($admin)->get('/admin/payments')
            ->assertOk()
            ->assertSee('dashboard-shell', false)
            ->assertSee('ORD-ADMIN-ONE')
            ->assertSee('ORD-ADMIN-TWO');
    }

    public function test_merchant_dashboard_shows_only_its_wallet_payments_and_offer(): void
    {
        Artisan::call('db:seed');
        $merchantUser = User::where('role', User::ROLE_MERCHANT)->firstOrFail();
        $ownMerchant = $merchantUser->merchant;
        $otherMerchant = $this->makeMerchant('Other Store', 'Other Item', '50.00', '25.00');
        $this->makePayment($ownMerchant, 'ORD-OWN', '1000.00');
        $this->makePayment($otherMerchant, 'ORD-OTHER', '50.00');

        $this->actingAs($merchantUser)->get('/merchant/dashboard')
            ->assertOk()
            ->assertSee('dashboard-shell', false)
            ->assertSee('Demo Item')
            ->assertSee('ORD-OWN')
            ->assertSee('20,000.00')
            ->assertDontSee('Other Item')
            ->assertDontSee('ORD-OTHER');
    }

    public function test_merchant_cannot_read_or_update_another_merchants_offer(): void
    {
        Artisan::call('db:seed');
        $merchantUser = User::where('role', User::ROLE_MERCHANT)->firstOrFail();
        $otherMerchant = $this->makeMerchant('Other Store', 'Private Item', '50.00', '25.00');

        $this->actingAs($merchantUser)->get('/merchant/dashboard')
            ->assertOk()
            ->assertDontSee('Private Item');

        $this->post('/merchant/dashboard/offer', [
            'merchant_id' => $otherMerchant->id,
            'offer_name' => 'Changed Item',
            'offer_amount' => '75.00',
        ])->assertRedirect('/merchant/dashboard');

        $this->assertSame('Changed Item', $merchantUser->fresh()->merchant->offer_name);
        $this->assertSame('75.00', $merchantUser->fresh()->merchant->offer_amount);
        $this->assertSame('Private Item', $otherMerchant->fresh()->offer_name);
    }

    public function test_admin_and_merchant_cannot_cross_access_role_dashboards(): void
    {
        Artisan::call('db:seed');
        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $merchantUser = User::where('role', User::ROLE_MERCHANT)->firstOrFail();

        $this->actingAs($merchantUser)->get('/admin/payments')->assertForbidden();
        $this->actingAs($admin)->get('/merchant/dashboard')->assertForbidden();
    }

    public function test_merchant_offer_requires_a_positive_amount_with_two_decimal_places(): void
    {
        Artisan::call('db:seed');
        $merchantUser = User::where('role', User::ROLE_MERCHANT)->firstOrFail();

        foreach (['0', '-1.00', '1.001'] as $amount) {
            $this->actingAs($merchantUser)->post('/merchant/dashboard/offer', [
                'offer_name' => 'Invalid Offer',
                'offer_amount' => $amount,
            ])->assertSessionHasErrors('offer_amount');
        }
    }

    private function makeMerchant(string $name, string $offerName, string $offerAmount, string $balance): Merchant
    {
        $merchant = Merchant::create([
            'name' => $name,
            'offer_name' => $offerName,
            'offer_amount' => $offerAmount,
        ]);
        $merchant->wallet()->create(['balance' => $balance]);

        return $merchant;
    }

    private function makePayment(Merchant $merchant, string $orderId, string $amount): void
    {
        $merchant->payments()->create([
            'order_id' => $orderId,
            'amount' => $amount,
            'gateway' => 'TEST_GATEWAY',
            'status' => Payment::STATUS_PENDING,
        ]);
    }
}
