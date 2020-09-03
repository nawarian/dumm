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
    public Map $map;

    private array $mapEdges;

    private Player $player;

    private int $scaleFactor = 15;

    public function __construct(Map $map)
    {
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
    }

    private function remapXToScreen(int $xMapPosition): int
    {
        list($xMin) = $this->mapEdges[0]; 

        return (int) (($xMapPosition + (-$xMin)) / $this->scaleFactor);
    }

    private function remapYToScreen(int $yMapPosition): int
    {
        list(, $yMin) = $this->mapEdges[0]; 

        return (int) (($yMapPosition + (-$yMin)) / $this->scaleFactor);
    }

    private function renderScene(): void
    {}

    private function renderDebugInfo(): void
    {
        Text::drawFPS(0, 0);
    }
}

