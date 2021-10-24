<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Vector2;

const PI = 3.14159265358979;

function xrange(int $min, int $max): iterable
{
    $i = $min;

    while ($i < $max) {
        yield $i++;
    }
}

function normalize360(int|float $angle): float
{
    $angle = fmod($angle, 360);
    if ($angle < 0) {
        $angle += 360;
    }

    return $angle;
}

function angleToVertex(Vector2 $from, Vector2 $to): float
{
    $dx = $to->x - $from->x;
    $dy = $to->y - $from->y;

    return normalize360(atan2($dy, $dx) * 180 / PI);
}

function angleToScreenX(float $angle): int
{
    $halfScreenAngle = 90;
    $fullScreenAngle = 180;
    $halfScreenWidth = Game::SCREEN_WIDTH / 2;

    // Left side
    if ($angle > $halfScreenAngle) {
        $angle -= $halfScreenAngle;
        return (int) ($halfScreenWidth - round(
                $halfScreenWidth * tan($angle * PI / $fullScreenAngle)
            ));
    }

    // Right side
    $angle = $halfScreenAngle - $angle;
    return (int) ($halfScreenWidth + round(
        $halfScreenWidth * tan($angle * PI / $fullScreenAngle)
    ));
}

function randomColor(): Color
{
    return new Color(rand(0, 255), rand(0, 255), rand(0, 255), 255);
}
