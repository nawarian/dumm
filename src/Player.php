<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use function Nawarian\Dumm\{
    normalize360,
    angleToVertex,
};

class Player extends Thing
{
    private int $fov = 90;
    private float $rotationSpeed = 10;

    // I hate receiving by ref, I'll soon refactor
    public function clipVertexesInFOV(array $v1, array $v2, float &$v1Angle, float &$v2Angle): bool
    {
        $pos = [$this->x, $this->y];
        $v1Angle = (float) angleToVertex($pos, $v1);
        $v2Angle = (float) angleToVertex($pos, $v2);

        $angleSpan = $v1Angle - $v2Angle;

        if ($angleSpan >= 180) {
            return false;
        }

        $v1Angle = normalize360($v1Angle - $this->angle);
        $v2Angle = normalize360($v2Angle - $this->angle);
        $halfFOV = $this->fov / 2;

        $v1Moved = $v1Angle + $halfFOV;
        if ($v1Moved > $this->fov) {
            $v1MovedAngle = $v1Moved - $this->fov;

            if ($v1MovedAngle >= $angleSpan) {
                return false;
            }

            $v1Angle = $halfFOV;
        }

        $v2Moved = $halfFOV - $v2Angle;

        if ($v2Moved > $this->fov) {
            $v2Angle = -$halfFOV;
        }

        $v1Angle += 90;
        $v2Angle += 90;

        return true;
    }

    public function rotateLeft(): void
    {
        $this->angle = normalize360($this->angle + (0.1875 * $this->rotationSpeed));
    }

    public function rotateRight(): void
    {
        $this->angle = normalize360($this->angle - (0.1875 * $this->rotationSpeed));
    }
}

