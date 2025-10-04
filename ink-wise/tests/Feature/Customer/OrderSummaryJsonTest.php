<?php

namespace Tests\Feature\Customer;

use Tests\TestCase;

class OrderSummaryJsonTest extends TestCase
{
    public function test_summary_json_endpoint_returns_not_found_when_no_active_order(): void
    {
        $response = $this->getJson('/order/summary.json');

        $response
            ->assertStatus(404)
            ->assertJson([
                'message' => 'No active order found for the current session.',
            ]);
    }
}
