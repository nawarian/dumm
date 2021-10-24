<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

final class SolidSegmentRange
{
    public function __construct(public int $xStart, public int $xEnd)
    {
    }
}
