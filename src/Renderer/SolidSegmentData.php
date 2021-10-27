<?php

declare(strict_types=1);

namespace Nawarian\Dumm\Renderer;

use Nawarian\Dumm\WAD\Segment;

final class SolidSegmentData
{
    public function __construct(public Segment $segment, public int $v1XScreen, public int $v2XScreen)
    {}
}
