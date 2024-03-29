<?php

declare(strict_types=1);

namespace Nawarian\Dumm\Renderer;

use Nawarian\Raylib\Types\Camera2D;

interface Renderer
{
    public function render(Camera2D $camera): void;
}
