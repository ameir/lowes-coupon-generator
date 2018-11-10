#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

// http://www.codediesel.com/php/generating-upc-check-digit/
function generateUpcCheckdigit($upc_code)
{
    $odd_total = 0;
    $even_total = 0;

    for ($i = 1; $i <= strlen($upc_code); $i++) {
        if ($i % 2 == 0) {
            // Sum even digits
            $even_total += $upc_code[$i - 1];
        } else {
            // Sum odd digits
            $odd_total += $upc_code[$i - 1];
        }
    }

    $sum = (3 * $even_total) + $odd_total;
    // Get the remainder MOD 10
    $check_digit = $sum % 10;

    // If the result is not zero, subtract the result from ten.
    return ($check_digit > 0) ? 10 - $check_digit : $check_digit;
}

$coupons = yaml_parse_file(__DIR__ . '/config.yaml')['coupons'];

$date = date('c');
foreach ($coupons as $coupon) {
    echo "{$coupon['description']}:" . PHP_EOL;

    if (time() > strtotime($coupon['expires'])) {
        echo 'Coupon already expired.' . PHP_EOL . PHP_EOL;
        continue;
    }

    // create dir for barcode images
    $dir = __DIR__ . "/generated/{$date}/{$coupon['description']}";
    mkdir($dir, 0755, true);

    $prefix = $coupon['prefix'] ?? '50000';
    for ($i = 0; $i < 10; $i++) {
        $upc_code = $prefix . rand(0, 4) . rand(1000, 9999) . $coupon['signature'];
        $barcode = $upc_code . generateUpcCheckdigit($upc_code);
        echo $barcode . PHP_EOL;

        // generate barcode image
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcodeString = $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 2, 60);

        $src = imagecreatefromstring($barcodeString);
        if (!$src) {
            echo 'Failed to load image!' . PHP_EOL;
            exit(1);
        }

        $dest = imagecreatefrompng(__DIR__ . '/resources/coupon.png');
        imagecopy($dest, $src, 680, 410, 0, 0, 800, 400);
        $textcolor = imagecolorallocate($dest, 0, 0, 0);

        // write barcode
        imagestring($dest, 5, 750, 475, $barcode, $textcolor);

        // write date
        imagestring($dest, 4, 970, 127, $coupon['expires'], $textcolor);

        $filename = "{$dir}/{$coupon['description']}-{$barcode}.png";
        imagegif($dest, $filename);

        imagedestroy($src);
        imagedestroy($dest);
    }
    echo PHP_EOL;
}
