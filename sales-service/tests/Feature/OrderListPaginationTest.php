<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderListPaginationTest extends TestCase
{
    private function createOrder(int $offset = 0): Order
    {
        return Order::create([
            'correlation_id' => (string) Str::uuid(),
            'customer_name' => "Customer {$offset}",
            'customer_email' => "c{$offset}@example.com",
            'total_amount' => 10.00,
            'items' => [['product_name' => 'Widget', 'quantity' => 1, 'unit_price' => 10.00]],
            'status' => Order::STATUS_SUBMITTED,
        ]);
    }

    public function test_index_returns_paginated_metadata(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->createOrder($i);
        }

        $response = $this->getJson('/api/orders?page=1');

        $response->assertOk();
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('per_page', 20);
        $response->assertJsonPath('total', 3);
        $response->assertJsonCount(3, 'data');
    }

    public function test_index_returns_second_page(): void
    {
        for ($i = 0; $i < 21; $i++) {
            $this->createOrder($i);
        }

        $response = $this->getJson('/api/orders?page=2');

        $response->assertOk();
        $response->assertJsonPath('current_page', 2);
        $response->assertJsonPath('last_page', 2);
        $response->assertJsonCount(1, 'data');
    }

    public function test_index_clamps_page_beyond_last(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createOrder($i);
        }

        $response = $this->getJson('/api/orders?page=99');

        $response->assertOk();
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('last_page', 1);
        $response->assertJsonCount(5, 'data');
    }

    public function test_index_rejects_invalid_page(): void
    {
        $response = $this->getJson('/api/orders?page=0');

        $response->assertStatus(422);
    }

    public function test_index_rejects_non_integer_page(): void
    {
        $response = $this->getJson('/api/orders?page=abc');

        $response->assertStatus(422);
    }
}
