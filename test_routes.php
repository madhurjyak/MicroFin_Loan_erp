<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Models\User::where('email', 'admin@sfb.in')->first();
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

echo "Testing remaining modules...\n\n";

foreach($routes as $route) {
    $request = Illuminate\Http\Request::create($route, 'GET');
    $response = $kernel->handle($request);
    
    $status = $response->getStatusCode();
    
    if ($status == 200) {
        echo "[SUCCESS] {$route} -> 200 OK\n";
    } else {
        echo "[FAILED]  {$route} -> {$status}\n";
    }
}
