<?php

declare(strict_types=1);

namespace Nawarian\Dumm\WAD;

final class Sector
{
    public function __construct(
        public int $floorHeight,
        public int $ceilingHeight,
        public string $floorTexture,
        public string $ceilingTexture,
        public int $lightLevel,
        public int $type,
        public int $tag,
    ) {}
}
