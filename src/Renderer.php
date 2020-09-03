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
    public int $screenWidth;

    public int $screenHeight;

    public Map $map;

    private array $mapEdges;

    private Player $player;

    private int $scaleFactor = 15;

    public function __construct(int $screenWidth, int $screenHeight, Map $map)
    {
        $this->screenWidth = --$screenWidth;
        $this->screenHeight = --$screenHeight;
        $this->map = $map;
        $this->mapEdges = $map->fetchMapEdges();
        $this->player = $map->fetchPlayer(1);
    }

    public function render(): void
    {
        Draw::begin();

        Draw::clearBackground(new Color(255, 255, 255, 255));

        $this->renderAutomap();
        $this->renderScene();

        $this->renderDebugInfo();

        Draw::end();
    }

    private function renderAutomap(): void
    {
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
                new Color(rand(0, 255), rand(0, 255), rand(0, 255), 255),
            );
        }

        Draw::circle(
            $this->remapXToScreen($this->player->x),
            $this->remapYToScreen($this->player->y),
            1,
            new Color(255, 0, 0, 255),
        );

        $nodes = $this->map->nodes();
        $rootNode = array_pop($nodes);

        list(
            $xPartition,
            $yPartition,
            $xChangePartition,
            $yChangePartition,

            $rightRectY0,
            $rightRectY1,
            $rightRectX0,
            $rightRectX1,

            $leftRectY0,
            $leftRectY1,
            $leftRectX0,
            $leftRectX1,
        ) = $rootNode;

        Draw::rectangleLines(
            $this->remapXToScreen($rightRectX0),
            $this->remapYToScreen($rightRectY0),
            $this->remapXToScreen($rightRectX1),
            $this->remapYToScreen($rightRectY1),
            new Color(0, 255, 0, 255),
        );

        Draw::rectangleLines(
            $this->remapXToScreen($leftRectX0),
            $this->remapYToScreen($leftRectY0),
            $this->remapXToScreen($leftRectX1),
            $this->remapYToScreen($leftRectY1),
            new Color(255, 0, 0, 255),
        );
    }

    private function remapXToScreen(int $xMapPosition): int
    {
        list($xMin) = $this->mapEdges[0]; 

        return (int) (($xMapPosition + (-$xMin)) / $this->scaleFactor);
    }

    private function remapYToScreen(int $yMapPosition): int
    {
        list(, $yMin) = $this->mapEdges[0]; 

        return (int) ($this->screenHeight - ($yMapPosition + (-$yMin)) / $this->scaleFactor);
    }

    private function renderScene(): void
    {}

    private function renderDebugInfo(): void
    {
        Text::drawFPS(0, 0);
    }
}

