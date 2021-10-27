<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Dumm\WAD\Segment;

final class SolidWallClipper
{
    /** @var SolidSegmentData[] */
    public array $visibleWalls = [];

    /** @var SolidSegmentRange[] */
    private array $solidWallRanges = [];

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->solidWallRanges = [
            new SolidSegmentRange(PHP_INT_MIN, -1),
            new SolidSegmentRange((int) Game::SCREEN_WIDTH, PHP_INT_MAX),
        ];

        $this->visibleWalls = [];
    }

    public function registerVisibleWallPortion(Segment $segment, int $v1XScreen, int $v2XScreen): void
    {
        $currentWall = new SolidSegmentRange($v1XScreen, $v2XScreen);
        $i = 0;

        while (
            $this->solidWallRanges[$i] !== $this->solidWallRanges[count($this->solidWallRanges) - 1]
            && $this->solidWallRanges[$i]->xEnd < $currentWall->xStart - 1
        ) {
            $i++;
        }

        if ($currentWall->xStart < $this->solidWallRanges[$i]->xStart) {
            if ($currentWall->xEnd < $this->solidWallRanges[$i]->xStart - 1) {
                // Wall is entirely visible, insert it
                $this->insertSolidSegmentRange($i, $currentWall);
                $this->storeWallRange($segment, $currentWall->xStart, $currentWall->xEnd);

                return;
            }
            $this->storeWallRange($segment, $currentWall->xStart, $this->solidWallRanges[$i]->xStart - 1);

            // The end is already included, just update start
            $this->solidWallRanges[$i]->xStart = $currentWall->xStart;
        }

        // This part is already occupied
        if ($currentWall->xEnd <= $this->solidWallRanges[$i]->xEnd) {
            return;
        }

        $j = $i;
        while ($currentWall->xEnd >= ($this->solidWallRanges[$j + 1]->xStart - 1)) {
            // partially clipped by other walls, store each fragment
            $this->storeWallRange(
                $segment,
                $this->solidWallRanges[$j]->xEnd + 1,
                $this->solidWallRanges[$j + 1]->xStart - 1
            );
            ++$j;

            if ($currentWall->xEnd <= $this->solidWallRanges[$j]->xEnd) {
                $this->solidWallRanges[$i]->xEnd = $this->solidWallRanges[$j]->xEnd;

                if ($this->solidWallRanges[$i] !== $this->solidWallRanges[$j]) {
                    // Delete a range of walls
                    ++$i;
                    ++$j;
                    $this->eraseSolidWallRanges($i, $j);
                }
                return;
            }
        }

        $this->storeWallRange($segment, $this->solidWallRanges[$j]->xEnd + 1, $currentWall->xEnd);
        $this->solidWallRanges[$i]->xEnd = $currentWall->xEnd;

        if ($this->solidWallRanges[$i] !== $this->solidWallRanges[$j]) {
            ++$i;
            ++$j;
            $this->eraseSolidWallRanges($i, $j);
        }
    }

    private function storeWallRange(Segment $segment, int $v1XScreen, int $v2XScreen): void
    {
        $this->visibleWalls[] = new SolidSegmentData($segment, $v1XScreen, $v2XScreen);
    }

    private function insertSolidSegmentRange(int $i, SolidSegmentRange $range): void
    {
        $firstSlice = array_slice($this->solidWallRanges, 0, $i);
        $secondSlice = array_slice($this->solidWallRanges, $i);

        $this->solidWallRanges = [...$firstSlice, $range, ...$secondSlice];
    }

    private function eraseSolidWallRanges(int $from, int $to): void
    {
        $firstSlice = array_slice($this->solidWallRanges, 0, $from);
        $secondSlice = array_slice($this->solidWallRanges, $to);

        $this->solidWallRanges = [...$firstSlice, ...$secondSlice];
    }
}
