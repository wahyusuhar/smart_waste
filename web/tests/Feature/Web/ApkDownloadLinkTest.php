<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class ApkDownloadLinkTest extends TestCase
{
    private const APK_URL = 'https://github.com/wahyusuhar/smart_waste/releases/latest/download/smart-waste.apk';

    public function test_link_is_shown_on_login_and_register_when_url_is_set(): void
    {
        config(['services.mobile.apk_url' => self::APK_URL]);

        foreach (['/login', '/register'] as $page) {
            $this->get($page)
                ->assertOk()
                ->assertSee('Unduh aplikasi (APK)')
                ->assertSee(self::APK_URL, escape: false);
        }
    }

    public function test_link_is_hidden_when_url_is_empty(): void
    {
        config(['services.mobile.apk_url' => null]);

        foreach (['/login', '/register'] as $page) {
            $this->get($page)
                ->assertOk()
                ->assertDontSee('Unduh aplikasi (APK)');
        }
    }
}
