<?php

declare(strict_types=1);

namespace Nawarian\Dumm\WAD;

use Nawarian\Raylib\Types\Vector2;

final class Linedef
{
    public function __construct(
        public Vector2 $startVertex,
        public Vector2 $endVertex,
        public int $flags,
        public int $lineType,
        public int $sectorTag,
        public ?Sidedef $rightSidedef,
        public ?Sidedef $leftSidedef,
    ) {}
}
