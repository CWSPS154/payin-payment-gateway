<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_home_page_shows_the_role_entry_points(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Admin Login')
            ->assertSee('Merchant Login')
            ->assertSee('Pay as Customer');
    }
}
