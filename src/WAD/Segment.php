<?php

declare(strict_types=1);

namespace Nawarian\Dumm\WAD;

use Nawarian\Raylib\Types\Vector2;

final class Segment
{
    public function __construct(
        public Vector2 $startVertex,
        public Vector2 $endVertex,
        public float $angle,
        public Linedef $linedef,
        public int $direction,
        public int $offset
    ) {}
}
