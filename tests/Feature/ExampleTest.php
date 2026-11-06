<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_raiz_redireciona_para_grade_e_login_responde_200(): void
    {
        $this->get('/')->assertRedirect('/grade');
        $this->get('/login')->assertOk();
    }
}
