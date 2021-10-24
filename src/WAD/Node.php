<?php

declare(strict_types=1);

namespace Nawarian\Dumm\WAD;

use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;

final class Node
{
    public function __construct(
        public Vector2 $partition,
        public Vector2 $partitionChange,
        public Rectangle $rightRectangle,
        public Rectangle $leftRectangle,
        public ?Node $leftChild,
        public ?Node $rightChild,
        public int $leftChildId,
        public int $rightChildId,
    ) {}
}
