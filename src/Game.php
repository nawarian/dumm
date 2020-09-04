<?php

declare(strict_types=1);

namespace  Nawarian\Dumm;

use raylib\{
    Color,
    Draw,
    Input\Key,
    Timming,
    Window,
};

class Game
{
    public const SCREEN_WIDTH = 320 * 3;
    public const SCREEN_HEIGHT = 200 * 3;

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
        Window::init(self::SCREEN_WIDTH, self::SCREEN_HEIGHT, 'DUMM - PHP DOOM');
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
        $this->renderer = new Renderer(self::SCREEN_WIDTH, self::SCREEN_HEIGHT, $this->state->map);
    }

    private function update(): void
    {
        if (Key::isDown(Key::RIGHT)) {
            $this->state->player->rotateRight();
        }

        if (Key::isDown(Key::LEFT)) {
            $this->state->player->rotateLeft();
        }
    }
}

