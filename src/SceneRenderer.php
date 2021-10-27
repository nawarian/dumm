<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Dumm\WAD\Segment;
use Nawarian\Raylib\Types\Camera2D;
use Nawarian\Raylib\Types\Color;
use function Nawarian\Raylib\{
    BeginMode2D,
    ClearBackground,
    DrawRectangle,
    EndMode2D
};

final class SceneRenderer extends AbstractRenderer
{
    /** @var SolidSegmentRange[] */
    private array $solidWallRanges = [];

    private array $screenXToAngle = [];
    private array $colors = [];
    private float $distancePlayerToScreen;

    public function render(Camera2D $camera): void
    {
        $this->colors = [
            'STARTAN3' => Color::pink(),
            'SUPPORT2' => Color::darkBlue(),
            'BROWN1' => Color::blue(),
            'DOORSTOP' => Color::orange(),
            'COMPTILE' => Color::lime(),
        ];

        $this->init();

        ClearBackground(Color::black());
        BeginMode2D($camera);
            $this->update();
        EndMode2D();
    }

    protected function handleSegmentFound(Segment $segment): void
    {
        if ($segment->linedef->leftSidedef !== null) {
            return;
        }

        $v1Angle = 0.0;
        $v2Angle = 0.0;

        if ($this->state->player->clipVertexesInFOV($segment->startVertex, $segment->endVertex, $v1Angle, $v2Angle)) {
            $v1XScreen = $this->angleToScreen($v1Angle);
            $v2XScreen = $this->angleToScreen($v2Angle);

            // Skip same pixel
            if ($v1XScreen === $v2XScreen) {
                return;
            }

            $this->clipSolidWalls($segment, $v1XScreen, $v2XScreen);
        }
    }

    private function clipSolidWalls(Segment $segment, int $v1XScreen, int $v2XScreen): void
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
        DrawRectangle(
            $v1XScreen,
            0,
            $v2XScreen - $v1XScreen + 1,
            Game::SCREEN_HEIGHT,
            $this->getWallColor($segment->linedef->rightSidedef->midTexture),
        );
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

    private function getWallColor(string $textureName): Color
    {
        $textureName = trim($textureName);

        $this->colors[$textureName] = $this->colors[$textureName] ?? new Color(
            rand(0, 255),
            rand(0, 255),
            rand(0, 255),
            255,
        );

        return $this->colors[$textureName];
    }

    private function angleToScreen(float $angle): int
    {
        $halScreenWidth = Game::SCREEN_WIDTH / 2;
        $iX = 0;

        // Left side
        if ($angle > 90) {
            $angle -= 90;
            $angle = normalize360($angle);
            $iX = $halScreenWidth - round(tan($angle * PI / 180.0) * $halScreenWidth);
        } else {
            // Right side
            $angle = 90 - $angle;
            $angle = normalize360($angle);
            $iX = round(tan($angle * PI / 180.0) * $halScreenWidth);
            $iX += $halScreenWidth;
        }

        return (int) $iX;
    }

    private function init(): void
    {
        // Update angle map
        $this->screenXToAngle = [];
        $screenAngle = normalize360($this->state->player->fov / 2);
        $step = $this->state->player->fov / Game::SCREEN_WIDTH + 1;
        foreach (xrange(0, (int)Game::SCREEN_WIDTH) as $i) {
            $this->screenXToAngle[$i] = $screenAngle;
            $screenAngle -= $step;
            $screenAngle = normalize360($screenAngle);
        }
        $this->distancePlayerToScreen = (Game::SCREEN_WIDTH / 2) / tan($this->state->player->fov / 2);

        // Update walls in FoV
        $this->solidWallRanges = [
            new SolidSegmentRange(PHP_INT_MIN, -1),
            new SolidSegmentRange((int) Game::SCREEN_WIDTH, PHP_INT_MAX),
        ];
    }
}
