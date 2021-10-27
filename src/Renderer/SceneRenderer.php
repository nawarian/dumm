<?php

declare(strict_types=1);

namespace Nawarian\Dumm\Renderer;

use Nawarian\Dumm\{Game, GameState, WAD\Segment};
use Nawarian\Raylib\Types\{Camera2D, Color};
use function Nawarian\Raylib\{BeginMode2D, ClearBackground, DrawLine, EndMode2D};
use function Nawarian\Dumm\{normalize360, xrange};
use const Nawarian\Dumm\PI;

final class SceneRenderer extends AbstractRenderer
{
    private array $screenXToAngle = [];
    private array $colors = [];
    private float $distancePlayerToScreen = 0.0;

    public function __construct(
        private SolidWallClipper $solidWallClipper,
        GameState $state,
    ) {
        parent::__construct($state);
    }

    private function init(): void
    {
        $this->screenXToAngle = [];
        $this->solidWallClipper->reset();

        $halfScreenWidth = Game::SCREEN_WIDTH / 2;
        $halfFOV = $this->state->player->fov / 2;
        $this->distancePlayerToScreen = $halfScreenWidth / tan($halfFOV);

        foreach (xrange(0, Game::SCREEN_WIDTH) as $i) {
            $this->screenXToAngle[$i] = atan(
                ($halfScreenWidth - $i) / ($this->distancePlayerToScreen * 180 / PI)
            );
        }
    }

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

        $this->update();

        ClearBackground(Color::black());
        BeginMode2D($camera);
            /** @var SolidSegmentData $visibleWall */
            while (
                !$this->solidWallClipper->visibleWalls->isEmpty()
                && $visibleWall = $this->solidWallClipper->visibleWalls->dequeue()
            ) {
                $this->drawSolidSegment($visibleWall);
            }
        EndMode2D();
    }

    private function drawSolidSegment(SolidSegmentData $visibleWall): void
    {
        $distanceToV1 = $this->state->player->distanceToPoint($visibleWall->segment->startVertex);
        $distanceToV2 = $this->state->player->distanceToPoint($visibleWall->segment->endVertex);

        if ($visibleWall->v1XScreen <= 0) {
            // partial seg
        }

        if ($visibleWall->v2XScreen >= Game::SCREEN_WIDTH - 1) {
            // partial seg
        }

        $ceilingV1OnScreen = 0;
        $floorV1OnScreen = 0;
        $ceilingV2OnScreen = 0;
        $floorV2OnScreen = 0;

        $this->calculateCeilingFloorHeight(
            $visibleWall->segment,
            $visibleWall->v1XScreen,
            $distanceToV1,
            $ceilingV1OnScreen,
            $floorV1OnScreen,
        );
        $this->calculateCeilingFloorHeight(
            $visibleWall->segment,
            $visibleWall->v2XScreen,
            $distanceToV2,
            $ceilingV2OnScreen,
            $floorV2OnScreen,
        );

        $color = $this->getWallColor($visibleWall->segment->linedef->rightSidedef->midTexture);
        DrawLine(
            (int) $visibleWall->v1XScreen,
            (int) $ceilingV1OnScreen,
            (int) $visibleWall->v1XScreen,
            (int) $floorV1OnScreen,
            $color,
        );
        DrawLine(
            (int) $visibleWall->v2XScreen,
            (int) $ceilingV2OnScreen,
            (int) $visibleWall->v2XScreen,
            (int) $floorV2OnScreen,
            $color,
        );
        DrawLine(
            (int) $visibleWall->v1XScreen,
            (int) $ceilingV1OnScreen,
            (int) $visibleWall->v2XScreen,
            (int) $ceilingV2OnScreen,
            $color,
        );
        DrawLine(
            (int) $visibleWall->v1XScreen,
            (int) $floorV1OnScreen,
            (int) $visibleWall->v2XScreen,
            (int) $floorV2OnScreen,
            $color
        );
    }

    private function calculateCeilingFloorHeight(
        Segment $segment,
        int $VXScreen,
        float $distanceToV,
        float &$ceilingVOnScreen,
        float &$floorVOnScreen,
    ): void {
        $halfScreenHeight = Game::SCREEN_HEIGHT / 2;

        $ceiling = $segment->linedef->rightSidedef->sector->ceilingHeight - $this->state->player->z;
        $floor = $segment->linedef->rightSidedef->sector->floorHeight - $this->state->player->z;

        $vScreenAngle = $this->screenXToAngle[$VXScreen];

        $distanceToVScreen = $this->distancePlayerToScreen / cos($vScreenAngle);

        $ceilingVOnScreen = (abs($ceiling) * $distanceToVScreen) / $distanceToV;
        $floorVOnScreen = (abs($floor) * $distanceToVScreen) / $distanceToV;

        if ($ceiling > 0) {
            $ceilingVOnScreen = $halfScreenHeight - $ceilingVOnScreen;
        } else {
            $ceilingVOnScreen += $halfScreenHeight;
        }

        if ($floor > 0) {
            $floorVOnScreen = $halfScreenHeight - $floorVOnScreen;
        } else {
            $floorVOnScreen += $halfScreenHeight;
        }
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

            $this->solidWallClipper->registerVisibleWallPortion($segment, $v1XScreen, $v2XScreen);
        }
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
        $playerHeight = 160;

        // Left side
        if ($angle > 90) {
            $angle = normalize360($angle - 90);
            return (int) ($playerHeight - round(tan($angle * PI / 180.0) * $playerHeight));
        }

        // Right side
        $angle = normalize360(90 - $angle);
        return (int) (round(tan($angle * PI / 180.0) * $playerHeight) + $playerHeight);
    }
}
