<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Raylib\Types\Vector2;

class Player extends Thing
{
    public int $fov = 90;
    public int $z = 41;
    private float $rotationSpeed = 10;

    // I hate receiving by ref, I'll soon refactor
    public function clipVertexesInFOV(Vector2 $v1, Vector2 $v2, float &$v1Angle, float &$v2Angle): bool
    {
        $v1Angle = angleToVertex($this->position, $v1);
        $v2Angle = angleToVertex($this->position, $v2);

        $angleSpan = $v1Angle - $v2Angle;

        if ($angleSpan >= 180) {
            return false;
        }

        $v1Angle = normalize360($v1Angle - $this->angle);
        $v2Angle = normalize360($v2Angle - $this->angle);
        $halfFOV = $this->fov / 2;

        $v1Moved = normalize360($v1Angle + $halfFOV);
        if ($v1Moved > $this->fov) {
            $v1MovedAngle = normalize360($v1Moved - $this->fov);

            if ($v1MovedAngle >= $angleSpan) {
                $v1Angle = round($v1Angle, 2);
                $v2Angle = round($v2Angle, 2);
                return false;
            }

            $v1Angle = $halfFOV;
        }

        $v2Moved = normalize360($halfFOV - $v2Angle);

        if ($v2Moved > $this->fov) {
            $v2Angle = normalize360(-$halfFOV);
        }

        $v1Angle = round(normalize360($v1Angle + 90), 2);
        $v2Angle = round(normalize360($v2Angle + 90), 2);

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

    public function distanceToPoint(Vector2 $vertex): float
    {
        return sqrt(
            pow($this->position->x - $vertex->x, 2)
            + pow($this->position->y - $vertex->y, 2)
        );
    }
}
