<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_can_be_created_for_a_merchant(): void
    {
        $merchantId = $this->createMerchant('ABC Store', '20000.00');

        $response = $this->postJson('/api/payments', [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD10001',
            'amount' => '10000.00',
            'gateway' => 'TEST_GATEWAY',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.order_id', 'ORD10001');

        $this->assertDatabaseHas('payments', [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD10001',
            'status' => 'PENDING',
        ]);
    }

    public function test_duplicate_order_id_is_rejected_for_the_same_merchant(): void
    {
        $merchantId = $this->createMerchant('ABC Store', '20000.00');
        $payload = [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD10001',
            'amount' => '10000.00',
            'gateway' => 'TEST_GATEWAY',
        ];

        $this->postJson('/api/payments', $payload)->assertCreated();
        $this->postJson('/api/payments', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_amount_must_be_positive_and_have_at_most_two_decimal_places(): void
    {
        $merchantId = $this->createMerchant('ABC Store', '20000.00');

        foreach (['0', '-1.00', '1.001'] as $amount) {
            $this->postJson('/api/payments', [
                'merchant_id' => $merchantId,
                'order_id' => 'ORD-INVALID',
                'amount' => $amount,
                'gateway' => 'TEST_GATEWAY',
            ])->assertUnprocessable();
        }

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_order_id_can_be_reused_for_a_different_merchant(): void
    {
        $firstMerchant = $this->createMerchant('ABC Store', '0.00');
        $secondMerchant = $this->createMerchant('XYZ Shop', '0.00');

        $this->createPayment($firstMerchant, 'ORD-SHARED', '10.00');
        $this->createPayment($secondMerchant, 'ORD-SHARED', '10.00');

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_repeated_success_webhooks_credit_the_wallet_only_once(): void
    {
        $merchantId = $this->createMerchant('ABC Store', '20000.00');
        $this->postJson('/api/payments', [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD10001',
            'amount' => '10000.00',
            'gateway' => 'TEST_GATEWAY',
        ])->assertCreated();

        $webhook = [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD10001',
            'status' => 'SUCCESS',
            'reference' => 'GW123456',
        ];

        foreach (range(1, 4) as $_) {
            $this->postJson('/api/payment/webhook', $webhook)
                ->assertOk()
                ->assertJsonPath('data.status', 'SUCCESS');
        }

        $this->assertDatabaseHas('wallets', ['merchant_id' => $merchantId, 'balance' => '30000.00']);
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertDatabaseHas('wallet_transactions', [
            'amount' => '10000.00',
            'balance_before' => '20000.00',
            'balance_after' => '30000.00',
            'reference' => 'GW123456',
        ]);
    }

    public function test_failed_webhook_does_not_credit_wallet_and_rejects_a_later_success(): void
    {
        $merchantId = $this->createMerchant('ABC Store', '20000.00');
        $this->postJson('/api/payments', [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD10002',
            'amount' => '10000.00',
            'gateway' => 'TEST_GATEWAY',
        ])->assertCreated();

        $this->postJson('/api/payment/webhook', [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD10002',
            'status' => 'FAILED',
            'reference' => 'GW-FAILED',
        ])->assertOk()->assertJsonPath('data.status', 'FAILED');

        $this->postJson('/api/payment/webhook', [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD10002',
            'status' => 'SUCCESS',
            'reference' => 'GW-SUCCESS',
        ])->assertConflict();

        $this->assertDatabaseHas('wallets', ['merchant_id' => $merchantId, 'balance' => '20000.00']);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_webhook_requires_merchant_id_when_order_id_is_ambiguous(): void
    {
        $firstMerchant = $this->createMerchant('ABC Store', '0.01');
        $secondMerchant = $this->createMerchant('XYZ Shop', '0.01');
        $this->createPayment($firstMerchant, 'SHARED-ORDER', '0.29');
        $this->createPayment($secondMerchant, 'SHARED-ORDER', '0.29');

        $this->postJson('/api/payment/webhook', [
            'order_id' => 'SHARED-ORDER',
            'status' => 'SUCCESS',
        ])->assertUnprocessable();

        $this->postJson('/api/payment/webhook', [
            'merchant_id' => $firstMerchant,
            'order_id' => 'SHARED-ORDER',
            'status' => 'SUCCESS',
        ])->assertOk();

        $this->assertDatabaseHas('wallets', ['merchant_id' => $firstMerchant, 'balance' => '0.30']);
        $this->assertDatabaseHas('wallets', ['merchant_id' => $secondMerchant, 'balance' => '0.01']);
    }

    public function test_unknown_webhook_order_returns_not_found(): void
    {
        $this->postJson('/api/payment/webhook', [
            'order_id' => 'MISSING',
            'status' => 'SUCCESS',
        ])->assertNotFound();
    }

    public function test_settlement_rolls_back_when_the_wallet_is_missing(): void
    {
        $merchantId = $this->createMerchant('ABC Store', '20000.00');
        $this->createPayment($merchantId, 'ORD-NO-WALLET', '10000.00');
        Wallet::where('merchant_id', $merchantId)->delete();

        $this->postJson('/api/payment/webhook', [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD-NO-WALLET',
            'status' => 'SUCCESS',
        ])->assertNotFound();

        $this->assertDatabaseHas('payments', ['order_id' => 'ORD-NO-WALLET', 'status' => 'PENDING']);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_settled_payment_and_merchant_cannot_be_deleted_with_their_ledger(): void
    {
        $merchantId = $this->createMerchant('ABC Store', '20000.00');
        $this->createPayment($merchantId, 'ORD-RETAIN', '10000.00');
        $this->postJson('/api/payment/webhook', [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD-RETAIN',
            'status' => 'SUCCESS',
        ])->assertOk();

        $payment = Payment::where('order_id', 'ORD-RETAIN')->firstOrFail();
        $paymentDeleteWasRestricted = false;
        try {
            $payment->delete();
        } catch (QueryException) {
            $paymentDeleteWasRestricted = true;
        }

        $merchantDeleteWasRestricted = false;
        try {
            Merchant::findOrFail($merchantId)->delete();
        } catch (QueryException) {
            $merchantDeleteWasRestricted = true;
        }

        $this->assertTrue($paymentDeleteWasRestricted);
        $this->assertTrue($merchantDeleteWasRestricted);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'SUCCESS']);
        $this->assertDatabaseHas('wallet_transactions', ['payment_id' => $payment->id]);
    }

    public function test_admin_payment_list_can_be_filtered_by_merchant_status_and_order_id(): void
    {
        $firstMerchant = $this->createMerchant('ABC Store', '20000.00');
        $secondMerchant = $this->createMerchant('XYZ Shop', '5000.00');

        $this->createPayment($firstMerchant, 'ORD-ABC', '100.00');
        $this->createPayment($secondMerchant, 'ORD-XYZ', '200.00');

        $this->signInAsAdmin();

        $this->get('/admin/payments?merchant_id='.$secondMerchant.'&status=PENDING&order_id=XYZ')
            ->assertOk()
            ->assertSee('XYZ Shop')
            ->assertSee('ORD-XYZ')
            ->assertDontSee('ORD-ABC');
    }

    public function test_admin_payment_details_show_wallet_transaction_and_balance(): void
    {
        $merchantId = $this->createMerchant('ABC Store', '20000.00');
        $response = $this->postJson('/api/payments', [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD-DETAIL',
            'amount' => '10000.00',
            'gateway' => 'TEST_GATEWAY',
        ])->assertCreated();
        $paymentId = $response->json('data.id');

        $this->postJson('/api/payment/webhook', [
            'merchant_id' => $merchantId,
            'order_id' => 'ORD-DETAIL',
            'status' => 'SUCCESS',
            'reference' => 'GW-DETAIL',
        ])->assertOk();

        $this->signInAsAdmin();

        $this->get('/admin/payments/'.$paymentId)
            ->assertOk()
            ->assertSee('ORD-DETAIL')
            ->assertSee('GW-DETAIL')
            ->assertSee('30000.00');
    }

    public function test_admin_rejects_invalid_filter_values(): void
    {
        $this->signInAsAdmin();

        $this->getJson('/admin/payments?status=UNKNOWN')->assertUnprocessable();
    }

    private function signInAsAdmin(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'admin-test@example.com',
            'password' => 'test-password',
            'role' => User::ROLE_ADMIN,
        ]));
    }

    private function createPayment(int $merchantId, string $orderId, string $amount): void
    {
        $this->postJson('/api/payments', [
            'merchant_id' => $merchantId,
            'order_id' => $orderId,
            'amount' => $amount,
            'gateway' => 'TEST_GATEWAY',
        ])->assertCreated();
    }

    private function createMerchant(string $name, string $balance): int
    {
        $merchant = Merchant::create(['name' => $name]);
        $merchant->wallet()->create(['balance' => $balance]);

        return $merchant->id;
    }
}
