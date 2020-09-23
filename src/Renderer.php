<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use raylib\{
    Color,
    Draw,
    Text,
    Timming,
    Window,
};

class Renderer
{
    use WallClippingTrait;

    public Map $map;

    private array $mapEdges = [];
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
            list($vx, $vy) = $v;
            $x[] = $vx;
            $y[] = $vy;
        }
        $this->mapEdges = [[min($x), min($y)], [max($x), max($y)]];

        list($xMin, $yMin) = $this->mapEdges[0];
        list($xMax, $yMax) = $this->mapEdges[1];

        $this->mapWidth = abs($xMax - $xMin);
        $this->mapHeight = abs($yMax - $yMin);
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

        // Clip solid walls to visible area only
        $this->prepareRenderableSegments();

        // Draw lines in FOV
        Draw::begin();
        Draw::clearBackground(new Color(0, 0, 0, 255));

        $this->renderScene($this->linesInFOV);
        $this->flags['showDebugInformation'] && $this->renderDebugInfo();

        Draw::end();
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

        list($xPartition, $yPartition, $changeXPartition, $changeYPartition) = $this->nodes[$nodeId];
        $dx = $this->player->x - $xPartition;
        $dy = $this->player->y - $yPartition;

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
        list($segCount, $segmentId) = $this->map->subSectors()[$subSectorId];
        $fovColor = new Color(255, 0, 0, 255);

        for ($i = 0; $i < $segCount; ++$i) {
            list(
                $vertexStart,
                $vertexEnd,
                ,
                $lineDefId,
                $direction,
            ) = $this->map->segments()[$segmentId + $i];

            $lineDef = $this->lineDefs[$lineDefId];
            list(,,,,,$rightSideDef, $leftSideDef) = $lineDef;

            if ($leftSideDef === 0xFFFF) {
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
            list(,, $v1Angle, $v2Angle, $sideDef) = $line;
            $xStart = $this->angleToScreenX($v1Angle);
            $xEnd = $this->angleToScreenX($v2Angle);

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
                list($v1, $v2) = $line;
                list($x0, $y0) = $vertices[$v1];
                list($x1, $y1) = $vertices[$v2];

                Draw::line(
                    $this->remapXToScreen($x0),
                    $this->remapYToScreen($y0),
                    $this->remapXToScreen($x1),
                    $this->remapYToScreen($y1),
                    new Color(255, 255, 255, 127),
                );
            }

            Draw::circle(
                $this->remapXToScreen($this->player->x),
                $this->remapYToScreen($this->player->y),
                1,
                new Color(255, 0, 0, 255),
            );

            // Draw visible lines only
            $red = new Color(255, 0, 0, 255);
            $orange = new Color(100, 100, 0, 255);
            foreach ($linesInFOV as $line) {
                list($v1, $v2, $v1Angle, $v2Angle) = $line;
                list($x0, $y0) = $v1;
                list($x1, $y1) = $v2;

                // Render segments 
                Draw::line(
                    $this->remapXToScreen($x0),
                    $this->remapYToScreen($y0),
                    $this->remapXToScreen($x1),
                    $this->remapYToScreen($y1),
                    $red,
                );

                // Draw sight lines
                Draw::line(
                    $this->remapXToScreen($this->player->x),
                    $this->remapYToScreen($this->player->y),
                    $this->remapXToScreen($x0),
                    $this->remapYToScreen($y0),
                    $orange,
                );

                Draw::line(
                    $this->remapXToScreen($this->player->x),
                    $this->remapYToScreen($this->player->y),
                    $this->remapXToScreen($x1),
                    $this->remapYToScreen($y1),
                    $orange,
                );
            }
        } else {
            // Render 3D scene
            foreach ($this->renderableSegments as $range) {
                list($xStart, $xEnd, $sideDef) = $range;
                list(,,,, $midTexture) = $sideDef;
                $width = abs($xEnd - $xStart);
                $this->textureColorMap[$midTexture] = $this->textureColorMap[$midTexture]
                    ?? new Color(rand(0, 255), rand(0, 255), rand(0, 255), 255);
                $color = $this->textureColorMap[$midTexture];

                Draw::rectangle((int) $xStart, 0, (int) $width, (int) Game::SCREEN_HEIGHT, $color);
            }
        }
    }

    private function remapXToScreen(int $xMapPosition): int
    {
        list($xMin) = $this->mapEdges[0]; 
        list($xMax) = $this->mapEdges[1];
        $scaleFactor = $this->mapWidth / Game::SCREEN_WIDTH;

        return (int) (($xMapPosition + (-$xMin)) / $scaleFactor);
    }

    private function remapYToScreen(int $yMapPosition): int
    {
        list(, $yMin) = $this->mapEdges[0]; 
        list(, $yMax) = $this->mapEdges[1];
        $mapHeight= abs($yMax - $yMin);
        $scaleFactor = $this->mapHeight / Game::SCREEN_HEIGHT;

        return (int) (Game::SCREEN_HEIGHT - ($yMapPosition + (-$yMin)) / $scaleFactor);
    }

    private function angleToScreenX(float $angle): int
    {
        $halfScreenAngle = 90;
        $fullScreenAngle = 180;
        $halfScreenWidth = Game::SCREEN_WIDTH / 2;

        // Left side
        if ($angle > $halfScreenAngle) {
            $angle -= $halfScreenAngle;
            return (int) ($halfScreenWidth - round(
                $halfScreenWidth * tan($angle * pi() / $fullScreenAngle)
            ));
        }

        // Right side
        $angle = $halfScreenAngle - $angle;
        return (int) ($halfScreenWidth + round(
            $halfScreenWidth * tan($angle * pi() / $fullScreenAngle)
        ));
    }

    private function renderDebugInfo(): void
    {
        $green = new Color(0, 255, 0, 255);
        Text::drawFPS(0, 0);

        $playerPosition = sprintf(
            'Coords: (%d, %d) | Angle: %03d',
            $this->player->x,
            $this->player->y,
            $this->player->angle,
        );

        Text::draw(
            $playerPosition,
            0,
            (int) Game::SCREEN_HEIGHT - 12,
            12,
            $green,
        );
    }
}

