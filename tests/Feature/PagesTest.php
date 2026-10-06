<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class PagesTest extends TestCase
{
    public function test_all_pages_load()
    {
        $user = User::where('email', 'admin@sfb.in')->first();
        if (!$user) {
            $this->markTestSkipped('Admin user not found.');
        }

        $routes = [
            '/lms/cds',
            '/los/apply',
            '/los/my-applications',
            '/los/pipeline',
            '/recovery/console',
            '/recovery/legal',
            '/sms/savings',
            '/admin/users',
            '/admin/config'
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertStatus(200);
            echo "Successfully loaded: {$route}\n";
        }
    }
}
