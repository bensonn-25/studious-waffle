<?php
$url = 'https://raw.githubusercontent.com/t0k4rt/phpqrcode/master/phpqrcode.php';
$dest = __DIR__ . '/includes/phpqrcode.php';

if (!file_exists($dest)) {
    $content = @file_get_contents($url);
    if ($content !== false) {
        file_put_contents($dest, $content);
        echo "phpqrcode downloaded successfully.";
    } else {
        echo "Failed to download phpqrcode.";
    }
} else {
    echo "phpqrcode already exists.";
}
?>
