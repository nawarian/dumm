<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/nawarian/raylib-ffi/src/generated-functions.php';

use Nawarian\Dumm\{Game, WAD};

[, $wad] = $argv;

// Load WAD
$wad = WAD::fromFile($wad);

// Initialize Game Loop
(new Game($wad))->start();
