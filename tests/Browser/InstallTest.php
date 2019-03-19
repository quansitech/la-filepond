<?php

namespace Qs\La\Tests\Browser;

use Qs\La\Tests\BrowserTestCase;

class InstallTest extends BrowserTestCase
{
    public function testInstall()
    {
        $this->browse(function ($browser) {
            $browser->visit('/admin/auth/login')
                ->assertSeeIn('.login-logo', 'Laravel-admin');
        });
    }

    /**
     * @depends testInstall
     */
    public function testLogin()
    {
        $this->browse(function ($browser) {
            $browser->visit('/admin/auth/login')
                ->type('username', 'admin')
                ->type('password', 'admin')
                ->press('Login')
                ->whenAvailable('.user-menu', function ($menu) {
                    $menu->assertSee('Administrator');
                }, 10);
        });
    }
}
