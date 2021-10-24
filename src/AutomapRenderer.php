<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Dumm\WAD\Segment;
use Nawarian\Raylib\Types\{Camera2D, Color, Vector2};
use function Nawarian\Raylib\{
    BeginMode2D,
    ClearBackground,
    DrawCircle,
    DrawLine,
    EndMode2D
};

final class AutomapRenderer extends AbstractRenderer implements Renderer
{
    private array $linesInFOV = [];

    private Vector2 $lowestMapCoords;
    private int $mapWidth;
    private int $mapHeight;

    public function __construct(GameState $state) {
        parent::__construct($state);

        // Fetching map edges
        $x = $y = [];
        foreach ($this->state->map->vertices() as $v) {
            $x[] = (int) $v->x;
            $y[] = (int) $v->y;
        }
        $this->lowestMapCoords = new Vector2(min($x), min($y));

        $this->mapWidth = abs(max($x) - min($x));
        $this->mapHeight = abs(max($y) - min($y));
    }

    public function render(Camera2D $camera): void
    {
        $this->linesInFOV = [];
        $this->update();

        // Draw lines in FOV
        ClearBackground(Color::black());

        BeginMode2D($camera);
            $this->renderAutomap($this->linesInFOV);
        EndMode2D();
    }

    protected function handleSegmentFound(Segment $segment): void
    {
        if ($segment->linedef->leftSidedef !== null) {
            return;
        }

        $v1Angle = 0.0;
        $v2Angle = 0.0;
        if (
            $this->state->player->clipVertexesInFOV(
                $segment->startVertex,
                $segment->endVertex,
                $v1Angle,
                $v2Angle,
            )
        ) {
            $this->linesInFOV[] = [$segment->startVertex, $segment->endVertex];
        }
    }

    private function renderAutomap(iterable $linesInFOV): void
    {
        // Draw automap lines
        foreach ($this->state->map->linedefs() as $line) {
            $v1 = $line->startVertex;
            $v2 = $line->endVertex;

            DrawLine(
                $this->remapXToScreen((int) $v1->x),
                $this->remapYToScreen((int) $v1->y),
                $this->remapXToScreen((int) $v2->x),
                $this->remapYToScreen((int) $v2->y),
                Color::white(127),
            );
        }

        DrawCircle(
            $this->remapXToScreen((int) $this->state->player->position->x),
            $this->remapYToScreen((int) $this->state->player->position->y),
            1,
            Color::red(),
        );

        // Draw visible lines only
        foreach ($linesInFOV as $line) {
            [$v1, $v2] = $line;

            // Render segments
            DrawLine(
                $this->remapXToScreen((int) $v1->x),
                $this->remapYToScreen((int) $v1->y),
                $this->remapXToScreen((int) $v2->x),
                $this->remapYToScreen((int) $v2->y),
                Color::red(),
            );

            // Draw sight lines
            DrawLine(
                $this->remapXToScreen((int) $this->state->player->position->x),
                $this->remapYToScreen((int) $this->state->player->position->y),
                $this->remapXToScreen((int) $v1->x),
                $this->remapYToScreen((int) $v1->y),
                Color::orange(),
            );

            DrawLine(
                $this->remapXToScreen((int) $this->state->player->position->x),
                $this->remapYToScreen((int) $this->state->player->position->y),
                $this->remapXToScreen((int) $v2->x),
                $this->remapYToScreen((int) $v2->y),
                Color::orange(),
            );
        }
    }

    private function remapXToScreen(int $xMapPosition): int
    {
        $xMin = $this->lowestMapCoords->x;
        $scaleFactor = $this->mapWidth / Game::SCREEN_WIDTH;

        return (int) (($xMapPosition + (-$xMin)) / $scaleFactor);
    }

    private function remapYToScreen(int $yMapPosition): int
    {
        $yMin = $this->lowestMapCoords->y;
        $scaleFactor = $this->mapHeight / Game::SCREEN_HEIGHT;

        return (int) (Game::SCREEN_HEIGHT - ($yMapPosition + (-$yMin)) / $scaleFactor);
    }
}
