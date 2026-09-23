<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerHomeTest extends TestCase
{
    public function test_customer_home_renders_the_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('customer.home');
        $response->assertSee('WAREHOUSE.BOOK');
        $response->assertSee('Gudang Tersedia');
        $response->assertSee('Gudang A5');
    }
}
