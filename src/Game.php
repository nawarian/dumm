<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Raylib\Raylib;
use function Nawarian\Raylib\{InitWindow, IsKeyDown, IsKeyPressed, SetTargetFPS, WindowShouldClose};

class Game
{
    public const SCREEN_WIDTH = 320 * 3.5;
    public const SCREEN_HEIGHT = 200 * 3.5;

    private GameState $state;
    private Renderer $renderer;

    public function __construct(WAD $wad)
    {
        $this->state = new GameState($wad);
    }

    public function start(): void
    {
        InitWindow((int) self::SCREEN_WIDTH, (int) self::SCREEN_HEIGHT, 'DUMM - PHP DOOM');
        SetTargetFPS(60);
        $this->switchGameMap('E1M1');

        while (false === WindowShouldClose()) {
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
        if (IsKeyPressed(Raylib::KEY_TAB)) {
            $this->renderer->toggleAutomap();
        }

        if (IsKeyPressed(Raylib::KEY_BACKSPACE)) {
            $this->renderer->toggleDebug();
        }

        if (IsKeyDown(Raylib::KEY_RIGHT)) {
            $this->state->player->rotateRight();
        }

        if (IsKeyDown(Raylib::KEY_LEFT)) {
            $this->state->player->rotateLeft();
        }

        if (IsKeyDown(Raylib::KEY_UP)) {
            $this->state->player->y += 1;
        }

        if (IsKeyDown(Raylib::KEY_DOWN)) {
            $this->state->player->y -= 1;
        }
    }
}
