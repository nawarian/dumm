<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Raylib\Types\Color;
use function Nawarian\Raylib\{
    BeginDrawing,
    ClearBackground,
    DrawCircle,
    DrawFPS,
    DrawLine,
    DrawRectangle,
    DrawText,
    EndDrawing,
    GetMouseX,
    GetMouseY,
    MeasureText
};

class Renderer
{
    use WallClippingTrait;

    public Map $map;

    private array $lowestMapCoords = [];
    private int $mapWidth;
    private int $mapHeight;

    private array $nodes = [];
    private array $sectors = [];
    private array $lineDefs = [];
    private array $sideDefs = [];

    private array $textureColorMap = [];

    private Player $player;

    private array $linesInFOV = [];

    private array $flags = [
        'showAutomap' => false,
        'showDebugInformation' => true,
    ];

    public function __construct(Map $map)
    {
        $this->map = $map;

        $this->nodes = $this->map->nodes();
        $this->sectors = $this->map->sectors();
        $this->lineDefs = $this->map->linedefs();
        $this->sideDefs = $this->map->sidedefs();
        $this->player = $map->fetchPlayer(1);

        // Fetching map edges
        $x = $y = [];
        foreach ($this->map->vertices() as $v) {
            $x[] = (int) $v->x;
            $y[] = (int) $v->y;
        }
        $this->lowestMapCoords = [min($x), min($y)];

        $this->mapWidth = abs(max($x) - min($x));
        $this->mapHeight = abs(max($y) - min($y));
    }

    public function toggleAutomap(): void
    {
        $this->flags['showAutomap'] = !$this->flags['showAutomap'];
    }

    public function toggleDebug(): void
    {
        $this->flags['showDebugInformation'] = !$this->flags['showDebugInformation'];
    }

    public function render(): void
    {
        $this->linesInFOV = [];

        // Traverse BSP nodes and fill $this->linesInFOV
        // Last node is the root node
        $this->traverseBSPNodes(count($this->nodes) - 1);

        // Draw lines in FOV
        BeginDrawing();
        ClearBackground(Color::black(255));

        $this->renderScene($this->linesInFOV);
        $this->flags['showDebugInformation'] && $this->renderDebugInfo();

        EndDrawing();
    }

    private function traverseBSPNodes(int $nodeId): void
    {
        // If that's a subsector, render the subsector instead
        if ($nodeId & 0x8000) {
            // "Convert" this int into int(16)
            $nodeId &= 0xFFFF;

            // Get the subSectorId bit only
            $nodeId &= ~0x8000;

            $this->addSubSectorToLinesInFOV($nodeId);
            return;
        }

        [$xPartition, $yPartition, $changeXPartition, $changeYPartition] = $this->nodes[$nodeId];
        $dx = $this->player->position->x - $xPartition;
        $dy = $this->player->position->y - $yPartition;

        $isOnLeftSide = (
            ($dx * $changeYPartition) - ($dy * $changeXPartition)
        ) <= 0;

        if (true === $isOnLeftSide) {
            $this->traverseBSPNodes($this->nodes[$nodeId][13]);
            $this->traverseBSPNodes($this->nodes[$nodeId][12]);
        } else {
            $this->traverseBSPNodes($this->nodes[$nodeId][12]);
            $this->traverseBSPNodes($this->nodes[$nodeId][13]);
        }
    }

    private function addSubSectorToLinesInFOV(int $subSectorId): void
    {
        [$segCount, $segmentId] = $this->map->subSectors()[$subSectorId];

        for ($i = 0; $i < $segCount; ++$i) {
            [
                $vertexStart,
                $vertexEnd,
                ,
                $lineDefId,
                $direction,
            ] = $this->map->segments()[$segmentId + $i];

            $lineDef = $this->lineDefs[$lineDefId];
            [,,,,,$rightSideDef, $leftSideDef] = $lineDef;

            if ($leftSideDef === -1) {
                continue;
            }

            $sidedef = $this->sideDefs[$rightSideDef];

            $v1 = $this->map->vertices()[$vertexStart];
            $v2 = $this->map->vertices()[$vertexEnd];
            $v1Angle = 0.0;
            $v2Angle = 0.0;
            if ($this->player->clipVertexesInFOV($v1, $v2, $v1Angle, $v2Angle)) {
                $this->linesInFOV[] = [$v1, $v2, $v1Angle, $v2Angle, $sidedef];
            }
        }
    }

    private function prepareRenderableSegments(): void
    {
        $dummySideDef = [0, 0, '', '', '', 0];
        // Initially the two ranges should be outside the viewport
        $this->renderableSegments = [
            [PHP_INT_MIN, -1, $dummySideDef],
            [Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
        ];

        foreach ($this->linesInFOV as $i => $line) {
            [,, $v1Angle, $v2Angle, $sideDef] = $line;
            $xStart = angleToScreenX($v1Angle);
            $xEnd = angleToScreenX($v2Angle);

            $this->storeClippedWallSegments(min($xStart, $xEnd), max($xStart, $xEnd), $sideDef);
        }
    }

    private function renderScene(array $linesInFOV): void
    {
        // Render automap
        if (true === $this->flags['showAutomap']) {
            // Draw automap lines
            $vertices = $this->map->vertices();
            foreach ($this->map->linedefs() as $line) {
                [$v1, $v2] = $line;

                DrawLine(
                    $this->remapXToScreen((int) $vertices[$v1]->x),
                    $this->remapYToScreen((int) $vertices[$v1]->y),
                    $this->remapXToScreen((int) $vertices[$v2]->x),
                    $this->remapYToScreen((int) $vertices[$v2]->y),
                    Color::white(127),
                );
            }

            DrawCircle(
                $this->remapXToScreen((int) $this->player->position->x),
                $this->remapYToScreen((int) $this->player->position->y),
                1,
                Color::red(255),
            );

            // Draw visible lines only
            $red = Color::red(255);
            $orange = Color::orange(255);
            foreach ($linesInFOV as $line) {
                [$v1, $v2] = $line;

                // Render segments
                DrawLine(
                    $this->remapXToScreen((int) $v1->x),
                    $this->remapYToScreen((int) $v1->y),
                    $this->remapXToScreen((int) $v2->x),
                    $this->remapYToScreen((int) $v2->y),
                    $red,
                );

                // Draw sight lines
                DrawLine(
                    $this->remapXToScreen((int) $this->player->position->x),
                    $this->remapYToScreen((int) $this->player->position->y),
                    $this->remapXToScreen((int) $v1->x),
                    $this->remapYToScreen((int) $v1->y),
                    $orange,
                );

                DrawLine(
                    $this->remapXToScreen((int) $this->player->position->x),
                    $this->remapYToScreen((int) $this->player->position->y),
                    $this->remapXToScreen((int) $v2->x),
                    $this->remapYToScreen((int) $v2->y),
                    $orange,
                );
            }
        } else {
            // Clip solid walls to visible area only
            $this->prepareRenderableSegments();

            // Render 3D scene
            foreach ($this->renderableSegments as $range) {
                [$xStart, $xEnd, $sideDef] = $range;
                [,,,, $midTexture] = $sideDef;
                $width = abs($xEnd - $xStart);
                $this->textureColorMap[$midTexture] = $this->textureColorMap[$midTexture] ?? randomColor();
                $color = $this->textureColorMap[$midTexture];

                $ceiling = 100;
                $floor = 200;

                DrawRectangle((int) $xStart, $ceiling, (int) $width, (int) (Game::SCREEN_HEIGHT - $floor), $color);
            }
        }
    }

    private function remapXToScreen(int $xMapPosition): int
    {
        [$xMin] = $this->lowestMapCoords;
        $scaleFactor = $this->mapWidth / Game::SCREEN_WIDTH;

        return (int) (($xMapPosition + (-$xMin)) / $scaleFactor);
    }

    private function remapYToScreen(int $yMapPosition): int
    {
        [, $yMin] = $this->lowestMapCoords;
        $scaleFactor = $this->mapHeight / Game::SCREEN_HEIGHT;

        return (int) (Game::SCREEN_HEIGHT - ($yMapPosition + (-$yMin)) / $scaleFactor);
    }

    private function renderDebugInfo(): void
    {
        $green = Color::green(255);
        DrawFPS(0, 0);

        $mouseX = GetMouseX();
        $mouseY = GetMouseY();

        $playerPosition = sprintf(
            'Coords: (%d, %d) | Angle: %03d | Mouse Coords: (%s, %s)',
            $this->player->position->x,
            $this->player->position->x,
            $this->player->angle,
            $mouseX < 0 || $mouseX > Game::SCREEN_WIDTH ? "{$mouseX}*" : $mouseX,
            $mouseY < 0 || $mouseY > Game::SCREEN_HEIGHT ? "{$mouseY}*" : $mouseY,
        );

        DrawText(
            $playerPosition,
            0,
            (int) Game::SCREEN_HEIGHT - 12,
            12,
            $green,
        );

        $memory = memory_get_usage(true) / 1024;
        $memoryText = sprintf(
            'Mem.: %d kB',
            $memory,
        );

        DrawText(
            $memoryText,
            (int) (Game::SCREEN_WIDTH - MeasureText($memoryText, 12)),
            (int) Game::SCREEN_HEIGHT - 12,
            12,
            $green,
        );
    }
}
