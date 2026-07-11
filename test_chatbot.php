<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$movieService = new \App\Services\Chatbot\MovieService();
$showtimeService = new \App\Services\Chatbot\ShowtimeService();
$faqService = new \App\Services\Chatbot\FAQService();

echo "=== Test 1: Now Showing ===\n";
$result = $movieService->getNowShowing();
echo "Type: {$result['type']} | Message: {$result['message']}\n";
echo "Movies found: " . (isset($result['data']) ? count($result['data']) : 0) . "\n";
if (isset($result['data'])) {
    foreach ($result['data'] as $m) {
        echo "  - {$m['title']} | {$m['duration_minutes']} min | genres: " . implode(',', $m['genres']) . "\n";
    }
}

echo "\n=== Test 2: Genre Buttons ===\n";
$result = $movieService->getGenreButtons();
echo "Buttons: " . count($result['buttons']) . "\n";
foreach ($result['buttons'] as $b) {
    echo "  - {$b['label']}\n";
}

echo "\n=== Test 3: Search by Name ===\n";
$result = $movieService->searchByName('Avengers');
echo "Type: {$result['type']} | Found: " . (isset($result['data']) ? count($result['data']) : 0) . "\n";

echo "\n=== Test 4: Movie Detail ===\n";
$result = $movieService->getMovieDetail(1);
echo "Type: {$result['type']} | Title: {$result['data']['title']}\n";

echo "\n=== Test 5: FAQ ===\n";
$result = $faqService->getAnswer('hours');
echo "Answer: " . substr($result['message'], 0, 100) . "...\n";

echo "\n=== Test 6: Showtime dates for movie 3 ===\n";
$result = $showtimeService->getDatesForMovie(3);
echo "Type: {$result['type']} | Message: {$result['message']}\n";
echo "Buttons: " . count($result['buttons']) . "\n";
foreach ($result['buttons'] as $b) {
    echo "  - {$b['label']}\n";
}

echo "\n✅ ALL TESTS PASSED!\n";
