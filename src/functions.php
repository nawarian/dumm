<?php

namespace Nawarian\Dumm;

/**
 * @var int|float $angle
 * @return int|float
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
