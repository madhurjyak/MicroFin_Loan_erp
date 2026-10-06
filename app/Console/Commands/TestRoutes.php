<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class TestRoutes extends Command
{
    protected $signature = 'app:test-routes';
    protected $description = 'Test the remaining application routes';

    public function handle()
    {
        $user = User::where('email', 'admin@sfb.in')->first();
        auth()->login($user);

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

        $kernel = app()->make(\Illuminate\Contracts\Http\Kernel::class);

        foreach ($routes as $route) {
            $request = \Illuminate\Http\Request::create($route, 'GET');
            $response = $kernel->handle($request);
            $status = $response->getStatusCode();
            
            if ($status == 200 || $status == 302) {
                $this->info("[SUCCESS] {$route} -> {$status}");
            } else {
                $this->error("[FAILED]  {$route} -> {$status}");
            }
        }
    }
}
