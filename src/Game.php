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
    private Renderer $renderer;

    public function __construct(WAD $wad)
    {
        $this->wad = $wad;
        $this->state = new GameState($wad);
    }

    public function start(): void
    {
        Window::init(320, 200, 'DUMM - PHP DOOM');
        Timming::setTargetFPS(60);
        $this->switchGameMap('E1M1');

        while (false === Window::shouldClose()) {
            $this->update();
            $this->renderer->render();
        }
    }

    private function switchGameMap(string $identifier): void
    {
        $this->state->setMap($identifier);
        $this->renderer = new Renderer(320, 200, $this->state->map);
    }

    private function update(): void
    {}
}

