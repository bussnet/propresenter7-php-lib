<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ProPresenter\Parser\PresentationBundle;
use ProPresenter\Parser\ProBundleWriter;
use ProPresenter\Parser\ProFileGenerator;

$image = imagecreatetruecolor(200, 150);
$blue = imagecolorallocate($image, 30, 60, 180);
imagefill($image, 0, 0, $blue);
$white = imagecolorallocate($image, 255, 255, 255);
imagestring($image, 5, 10, 10, 'ProPresenter', $white);
$tmpPng = tempnam(sys_get_temp_dir(), 'testbild-') . '.png';
imagepng($image, $tmpPng);
$imageBytes = file_get_contents($tmpPng);
unlink($tmpPng);

$refDir = dirname(__DIR__) . '/doc/reference_samples';

$song = ProFileGenerator::generate(
    'TestBild',
    [
        [
            'name' => 'Verse 1',
            'color' => [0.0, 0.0, 0.0, 1.0],
            'slides' => [
                [
                    'media' => 'test-background.png',
                    'format' => 'png',
                    'label' => 'test-background.png',
                    'bundleRelative' => true,
                ],
            ],
        ],
    ],
    [['name' => 'normal', 'groupNames' => ['Verse 1']]],
);

$bundle = new PresentationBundle($song, 'TestBild.pro', ['test-background.png' => $imageBytes]);
ProBundleWriter::write($bundle, $refDir . '/TestBild.probundle');
echo "TestBild.probundle written\n";
