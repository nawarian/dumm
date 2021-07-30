<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\RaylibFactory;

class Game
{
    public const SCREEN_WIDTH = 320 * 3.5;
    public const SCREEN_HEIGHT = 200 * 3.5;

    public const VIRTUAL_WIDTH = 320;
    public const VIRTUAL_HEIGHT = 200;

    private WAD $wad;
    private GameState $state;
    private Renderer $renderer;

    public static Raylib $raylib;

    public function __construct(WAD $wad)
    {
        $this->wad = $wad;
        $this->state = new GameState($wad);

        $factory = new RaylibFactory();
        self::$raylib = $factory->newInstance();
    }

    public function start(): void
    {
        self::$raylib->initWindow((int) self::SCREEN_WIDTH, (int) self::SCREEN_HEIGHT, 'DUMM - PHP DOOM');
        self::$raylib->setTargetFPS(60);
        $this->switchGameMap('E1M1');

        while (false === self::$raylib->windowShouldClose()) {
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
        if (self::$raylib->isKeyPressed(Raylib::KEY_TAB)) {
            $this->renderer->toggleAutomap();
        }

        if (self::$raylib->isKeyPressed(Raylib::KEY_BACKSPACE)) {
            $this->renderer->toggleDebug();
        }

        if (self::$raylib->isKeyDown(Raylib::KEY_RIGHT)) {
            $this->state->player->rotateRight();
        }

        if (self::$raylib->isKeyDown(Raylib::KEY_LEFT)) {
            $this->state->player->rotateLeft();
        }

        if (self::$raylib->isKeyDown(Raylib::KEY_UP)) {
            $this->state->player->y += 1;
        }

        if (self::$raylib->isKeyDown(Raylib::KEY_DOWN)) {
            $this->state->player->y -= 1;
        }
    }
}

