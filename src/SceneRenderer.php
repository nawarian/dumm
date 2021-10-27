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
    private array $colors = [];

    public function __construct(
        private SolidWallClipper $solidWallClipper,
        GameState $state,
    ) {
        parent::__construct($state);
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

        $this->solidWallClipper->reset();
        $this->update();

        ClearBackground(Color::black());
        BeginMode2D($camera);
            foreach ($this->solidWallClipper->visibleWalls as $visibleWall) {
                DrawRectangle(
                    $visibleWall->v1Xscreen,
                    0,
                    abs($visibleWall->v2XScreen - $visibleWall->v1Xscreen) + 1,
                    Game::SCREEN_HEIGHT,
                    $this->getWallColor($visibleWall->segment->linedef->rightSidedef->midTexture),
                );
            }
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
