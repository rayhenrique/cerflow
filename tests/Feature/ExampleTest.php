<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_raiz_exibe_landing_page_e_login_responde_200(): void
    {
        $this->get('/')->assertOk()->assertSee('CER Digital');
        $this->get('/login')->assertOk();
    }
}
