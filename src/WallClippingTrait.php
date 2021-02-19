<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

/**
 * Why would I create a trait for this, you may ask...
 *
 * Well, getting this logic right is simply a pain and I refuse to
 * melt my brain on it without Unit Tests backing me up!
 */
trait WallClippingTrait
{
    private array $renderableSegments = [];

    /**
     * @todo I really dislike recursion, but here it made things easier. Removing
     * this recursion would be awesome! But for now, I'm just moving on.
     */
    private function storeClippedWallSegments(int $xStart, int $xEnd, array $sideDef): void
    {
        if ($xEnd >= (int) Game::SCREEN_WIDTH) {
            $xEnd = (int) Game::SCREEN_WIDTH - 1;
        }

        // Find closest span to the right
        foreach ($this->renderableSegments as $i => $segment) {
            // xs = "X Start"; xe = "X End"; p = "Previous"; c = "Current"; n = "Next"
            [$pxs, $pxe] = $this->renderableSegments[$i - 1] ?? [PHP_INT_MIN, -1];
            [$cxs, $cxe] = $segment;
            [$nxs, $nxe] = $this->renderableSegments[$i + 1] ?? [(int) Game::SCREEN_WIDTH, PHP_INT_MAX];

            if ($cxs > $xStart) {
                break;
            }
        }

        if ($xStart > $pxe) {
            if ($xEnd > $cxs) {
                // Needs to clip right as well?
                if ($xEnd > $cxe && $xEnd < $nxs) {
                    $this->storeClippedWallSegments($cxe + 1, $xEnd, $sideDef);
                }

                // Current end is longer than next rect?
                $xEnd = $cxs - 1;
            }

            array_splice($this->renderableSegments, $i, 0, [[$xStart, $xEnd, $sideDef]]);

        } else if ($xStart > $pxs) {
            $xStart = $pxe;

            if ($xEnd > $cxs) {
                if ($xEnd > $cxe && $xEnd > $nxs) {
                    $this->storeClippedWallSegments($cxs + 1, $xEnd, $sideDef);
                }

                $xEnd = $cxs;
            }

            array_splice($this->renderableSegments, $i, 0, [[$xStart, $xEnd, $sideDef]]);
        }
    }
}
