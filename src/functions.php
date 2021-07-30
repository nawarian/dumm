<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Raylib\Types\Color;

function normalize360(int|float $angle): float
{
    $angle = fmod($angle, 360);
    if ($angle < 0) {
        $angle += 360;
    }

    return $angle;
}

function angleToVertex(array $from, array $to): float
{
    [$fromX, $fromY] = $from;
    [$toX, $toY] = $to;

    $dx = $toX - $fromX;
    $dy = $toY - $fromY;

    return normalize360(atan2($dy, $dx) * 180 / pi());
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
                $halfScreenWidth * tan($angle * pi() / $fullScreenAngle)
            ));
    }

    // Right side
    $angle = $halfScreenAngle - $angle;
    return (int) ($halfScreenWidth + round(
        $halfScreenWidth * tan($angle * pi() / $fullScreenAngle)
    ));
}

function randomColor(): Color
{
    return new Color(rand(0, 255), rand(0, 255), rand(0, 255), 255);
}
