#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

// http://www.codediesel.com/php/generating-upc-check-digit/
function generateUpcCheckdigit($upc_code)
{
    $odd_total = 0;
    $even_total = 0;

    for ($i = 1; $i <= strlen($upc_code); $i++) {
        //echo "Checking i=$i ({$upc_code[$i - 1]})...\n";
        if ($i % 2 == 0) {
            //echo "even\n";
            /* Sum even digits */
            $even_total += $upc_code[$i - 1];
        } else {
            //echo "odd\n";
            /* Sum odd digits */
            $odd_total += $upc_code[$i - 1];
        }
    }

    $sum = (3 * $even_total) + $odd_total;
    //echo "Sum: $sum\n";
    /* Get the remainder MOD 10*/
    $check_digit = $sum % 10;

    /* If the result is not zero, subtract the result from ten. */
    return ($check_digit > 0) ? 10 - $check_digit : $check_digit;
}

$coupons = [
    [
        'signature' => '0109',
        'description' => '10% off',
        'expires' => '12/27/2017',
    ],
    [
        'signature' => 9217,
        'description' => '$10 off $50',
        'expires' => '11/30/2017',
    ],
    [
        'signature' => 9345,
        'description' => '$15 off $75',
        'expires' => '12/27/2017',
    ],
    [
        'signature' => '9381',
        'description' => '$20 off $100',
        'expires' => '12/27/2017',
    ],
    [
        'signature' => 9387,
        'description' => '$40 off $200',
        'expires' => '12/27/2017',
    ],
    [
        'signature' => 9393,
        'description' => '$60 off $400',
        'expires' => '12/27/2017',
    ],
];

$date = date('c');
foreach ($coupons as $coupon) {
    echo "{$coupon['description']}:" . PHP_EOL;

    // create dir for barcode images
    $dir = __DIR__ . "/generated/{$date}/{$coupon['description']}";
    mkdir($dir, 0755, true);

    for ($i = 0; $i < 10; $i++) {
        $upc_code = '47000' . rand(0, 4) . rand(1000, 9999) . $coupon['signature'];
        $barcode = $upc_code . generateUpcCheckdigit($upc_code);
        echo $barcode . PHP_EOL;

        // generate barcode image
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcodeString = $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 1.5, 55);

        $src = imagecreatefromstring($barcodeString);
        if (!$src) {
            echo 'Failed to load image!' . PHP_EOL;
            exit(1);
        }

        $dest = imagecreatefrompng(__DIR__ . '/resources/coupon.png');
        imagecopy($dest, $src, 680, 410, 0, 0, 800, 400);
        $textcolor = imagecolorallocate($dest, 0, 0, 0);

        // write barcode
        imagestring($dest, 5, 720, 465, $barcode, $textcolor);

        // write date
        imagestring($dest, 4, 970, 127, $coupon['expires'], $textcolor);

        $filename = $dir . '/barcode-' . $barcode . '.png';
        imagegif($dest, $filename);

        imagedestroy($src);
        imagedestroy($dest);
    }
    echo PHP_EOL;
}
