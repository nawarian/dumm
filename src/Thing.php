<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

class Thing
{
    public int $x;
    public int $y;
    public int $angle;
    public int $type;
    public int $flags;

    public function __construct(
        int $x,
        int $y,
        int $angle,
        int $type,
        int $flags
    ) {
        $this->x = $x;
        $this->y = $y;
        $this->angle = $angle;
        $this->type = $type;
        $this->flags = $flags;
    }
}

