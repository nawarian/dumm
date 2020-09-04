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
    list($fromX, $fromY) = $from;
    list($toX, $toY) = $to;

    $dx = $toX - $fromX;
    $dy = $toY - $fromY;

    return atan2($dy, $dx) * 180 / pi();
}

