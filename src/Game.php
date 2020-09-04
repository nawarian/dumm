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
    public const SCREEN_WIDTH = 320 * 3.5;
    public const SCREEN_HEIGHT = 200 * 3.5;

    public const VIRTUAL_WIDTH = 320;
    public const VIRTUAL_HEIGHT = 200;

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
        Window::init((int) self::SCREEN_WIDTH, (int) self::SCREEN_HEIGHT, 'DUMM - PHP DOOM');
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
        $this->renderer = new Renderer($this->state->map);
    }

    private function update(): void
    {
        if (Key::isPressed(Key::TAB)) {
            $this->renderer->toggleAutomap();
        }

        if (Key::isPressed(Key::BACKSPACE)) {
            $this->renderer->toggleDebug();
        }

        if (Key::isDown(Key::RIGHT)) {
            $this->state->player->rotateRight();
        }

        if (Key::isDown(Key::LEFT)) {
            $this->state->player->rotateLeft();
        }
    }
}

