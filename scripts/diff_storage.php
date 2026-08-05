<?php
// Compare public/storage (served dir) vs storage/app/public (Laravel upload dir)
$pubRoot = 'c:/laragon/www/MovieZone/MovieZone/public/storage';
$appRoot = 'c:/laragon/www/MovieZone/MovieZone/storage/app/public';

function walk($root) {
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
        $out[] = $rel;
    }
    sort($out);
    return $out;
}

$pub = walk($pubRoot);
$app = walk($appRoot);

echo "=== FILES IN public/storage BUT NOT IN storage/app/public (would be LOST on delete) ===\n";
$onlyPub = array_diff($pub, $app);
foreach ($onlyPub as $f) echo "  $f\n";
if (!$onlyPub) echo "  (none)\n";

echo "\n=== FILES IN storage/app/public BUT NOT IN public/storage (currently INVISIBLE on web) ===\n";
$onlyApp = array_diff($app, $pub);
foreach ($onlyApp as $f) echo "  $f\n";
if (!$onlyApp) echo "  (none)\n";

echo "\n=== Counts ===\n";
echo "public/storage files: " . count($pub) . "\n";
echo "storage/app/public files: " . count($app) . "\n";

