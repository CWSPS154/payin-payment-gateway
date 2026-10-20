<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $merchant = Merchant::firstOrCreate(
            ['name' => config('demo.merchant.store_name')],
            [
                'offer_name' => config('demo.offer.name'),
                'offer_amount' => config('demo.offer.amount'),
            ],
        );

        if ($merchant->offer_name === null || $merchant->offer_amount === null) {
            $merchant->update([
                'offer_name' => $merchant->offer_name ?? config('demo.offer.name'),
                'offer_amount' => $merchant->offer_amount ?? config('demo.offer.amount'),
            ]);
        }

        $merchant->wallet()->firstOrCreate([], ['balance' => '20000.00']);

        User::updateOrCreate(
            ['email' => config('demo.admin.email')],
            [
                'name' => config('demo.admin.name'),
                'password' => config('demo.admin.password'),
                'role' => User::ROLE_ADMIN,
                'merchant_id' => null,
            ],
        );

        User::updateOrCreate(
            ['email' => config('demo.merchant.email')],
            [
                'name' => config('demo.merchant.owner_name'),
                'password' => config('demo.merchant.password'),
                'role' => User::ROLE_MERCHANT,
                'merchant_id' => $merchant->id,
            ],
        );
    }
}
