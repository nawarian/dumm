<?php

declare(strict_types=1);

namespace  Nawarian\Dumm;

use raylib\{
    Color,
    Draw,
    Timming,
    Window,
};

class Game
{
    private WAD $wad;
    private GameState $state;

    public function __construct(WAD $wad)
    {
        $this->wad = $wad;
        $this->state = new GameState($wad);
    }

    public function start(): void
    {
        Window::init(320, 200, 'DUMM - PHP DOOM');
        Timming::setTargetFPS(60);
        $this->state->setMap('E1M1');

        while (false === Window::shouldClose()) {
            $this->update();
            $this->draw();
        }
    }

    private function update(): void
    {}

    private function draw(): void
    {
        $scaleFactor = 15;
        list($xMin, $yMin) = $this->state->map->fetchMapEdges()[0];
        $xShift = -$xMin;
        $yShift = -$yMin;

        Draw::begin();

        Draw::clearBackground(new Color(255, 255, 255, 255));

        $vertices = $this->state->map->vertices();
        foreach ($this->state->map->linedefs() as $line) {
            list($v1, $v2) = $line;
            list($x0, $y0) = $vertices[$v1];
            list($x1, $y1) = $vertices[$v2];

            Draw::line(
                (int) (($x0 + $xShift) / $scaleFactor),
                (int) (($y0 + $yShift) / $scaleFactor),
                (int) (($x1 + $xShift) / $scaleFactor),
                (int) (($y1 + $yShift) / $scaleFactor),
                new Color(rand(0, 255), rand(0, 255), rand(0, 255), 255)
            );
        }

        $player = $this->state->map->fetchPlayer(1);
        Draw::circle(
            (int) (($player->x + $xShift) / $scaleFactor),
            (int) (($player->y + $yShift) / $scaleFactor),
            1,
            new Color(255, 0, 0, 255),
        );

        Draw::end();
    }
}

