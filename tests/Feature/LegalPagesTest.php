<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_privacy_policy_is_public(): void
    {
        $this->get('/chinh-sach-bao-mat')->assertOk()->assertSee('Chính sách bảo mật')->assertSee('Google');
    }

    public function test_terms_of_service_is_public(): void
    {
        $this->get('/dieu-khoan-su-dung')->assertOk()->assertSee('Điều khoản sử dụng');
    }

    public function test_login_page_links_to_legal_pages(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.terms'), false);
    }
}
