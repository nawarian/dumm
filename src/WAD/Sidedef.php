<?php

declare(strict_types=1);

namespace Nawarian\Dumm\WAD;

use Nawarian\Raylib\Types\Vector2;

final class Sidedef
{
    public function __construct(
        public Vector2 $offset,
        public string $upperTexture,
        public string $lowerTexture,
        public string $midTexture,
        public Sector $sector,
    ) {}
}
