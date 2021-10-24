<?php

declare(strict_types=1);

namespace Nawarian\Dumm\WAD;

final class SubSector
{
    public function __construct(
        public int $segCount,
        public int $segId,
        public Segment $segment,
    ) {}
}
