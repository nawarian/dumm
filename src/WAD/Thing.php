<?php

declare(strict_types=1);

namespace Nawarian\Dumm\WAD;

use Nawarian\Raylib\Types\Vector2;

class Thing
{
    public function __construct(
        public Vector2 $position,
        public float $angle,
        public int $type,
        public int $flags,
    ) {}
}
