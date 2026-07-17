<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$showtimes = \App\Models\Showtime::where('start_time', '>=', \Carbon\Carbon::now())->get();
echo "Total showtimes: " . $showtimes->count() . "\n";
foreach($showtimes as $s) {
    echo "ID: {$s->id}, Movie ID: {$s->movie_id}, Has Movie Relation: " . ($s->movie ? 'YES' : 'NO') . "\n";
}
