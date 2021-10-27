<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Dumm\Renderer\{AutomapRenderer, DebugRenderer, Renderer, SceneRenderer};
use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\{Camera2D, Vector2};
use function Nawarian\Raylib\{
    BeginDrawing,
    EndDrawing,
    GetMouseWheelMove,
    InitWindow,
    IsKeyDown,
    IsKeyPressed,
    SetTargetFPS,
    WindowShouldClose
};

class Game
{
    public const SCREEN_WIDTH = 320;
    public const SCREEN_HEIGHT = 200;

    private GameState $state;
    private AutomapRenderer $automapRenderer;
    private SceneRenderer $sceneRenderer;

    private Renderer $renderer;
    private Camera2D $camera;

    public function __construct(WAD $wad)
    {
        $this->state = new GameState($wad);
        $this->renderer = new DebugRenderer($this->state);
    }

    public function start(): void
    {
        InitWindow((int) (self::SCREEN_WIDTH * 3.5), (int) (self::SCREEN_HEIGHT * 3.5), 'DUMM - PHP DOOM');
        SetTargetFPS(60);
        $this->switchGameMap('E1M1');

        $this->camera = new Camera2D(new Vector2(0, 0), new Vector2(0, 0), 0.0, 3.5);

        while (false === WindowShouldClose()) {
            $this->update();
            BeginDrawing();
                $this->renderer->render($this->camera);
            EndDrawing();
        }
    }

    private function switchGameMap(string $identifier): void
    {
        $this->state->setMap($identifier);
        $this->automapRenderer = new AutomapRenderer($this->state);
        $this->sceneRenderer = new SceneRenderer(new SolidWallClipper(), $this->state);
        $this->renderer->innerRenderer = $this->sceneRenderer;
    }

    private function update(): void
    {
        if (IsKeyPressed(Raylib::KEY_TAB)) {
            if ($this->renderer->innerRenderer === $this->sceneRenderer) {
                $this->renderer->innerRenderer = $this->automapRenderer;
            } else {
                $this->renderer->innerRenderer = $this->sceneRenderer;
            }
        }

        if (IsKeyDown(Raylib::KEY_RIGHT)) {
            $this->state->player->rotateRight();
        }

        if (IsKeyDown(Raylib::KEY_LEFT)) {
            $this->state->player->rotateLeft();
        }

        if (IsKeyDown(Raylib::KEY_UP)) {
            $this->state->player->position->y += 1;
        }

        if (IsKeyDown(Raylib::KEY_DOWN)) {
            $this->state->player->position->y -= 1;
        }

        $scroll = GetMouseWheelMove();
        if (0.0 !== $scroll) {
            $this->camera->zoom += $scroll / 10;
        }

        if (IsKeyDown(Raylib::KEY_W)) {
            $this->camera->offset->y += 20;
        }

        if (IsKeyDown(Raylib::KEY_S)) {
            $this->camera->offset->y -= 20;
        }

        if (IsKeyDown(Raylib::KEY_D)) {
            $this->camera->offset->x -= 20;
        }

        if (IsKeyDown(Raylib::KEY_A)) {
            $this->camera->offset->x += 20;
        }
    }
}
