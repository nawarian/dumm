<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Nawarian\Dumm\{Game, WAD};

[, $wad] = $argv;

// Load WAD
$wad = WAD::fromFile($wad);

// Initialize Game Loop
(new Game($wad))->start();

