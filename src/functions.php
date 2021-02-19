<?php

namespace Nawarian\Dumm;

use raylib\Color;

/**
 * @return int|float
 * @var int|float $angle
 */
function normalize360($angle) 
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

function color(int $r, int $g, int $b, int $a): Color
{
    return new Color($r, $g, $b, $a);
}

function black(int $a): Color
{
    return color(0, 0, 0, $a);
}

function white(int $a): Color
{
    return color(255, 255, 255, $a);
}

function red(int $a): Color
{
    return color(255, 0, 0, $a);
}

function orange(int $a): Color
{
    return color(100, 100, 0, $a);
}

function green(int $a): Color
{
    return color(0, 255, 0, $a);
}

function randomColor(): Color
{
    return color(rand(0, 255), rand(0, 255), rand(0, 255), 255);
}
