<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Raylib\Types\Camera2D;
use Nawarian\Raylib\Types\Color;
use function Nawarian\Raylib\DrawFPS;
use function Nawarian\Raylib\DrawText;
use function Nawarian\Raylib\GetScreenHeight;

final class DebugRenderer implements Renderer
{
    public Renderer $innerRenderer;

    private int $fontSize = 20;

    public function __construct(private GameState $gameState)
    {
    }

    public function render(Camera2D $camera): void
    {
        $player = $this->gameState->player;
        $this->innerRenderer->render($camera);

        DrawFPS(0, 0);

        $playerDebugMessage = sprintf(
            'Player (X = %d; Y = %d; A = %0.2f)',
            $player->position->x,
            $player->position->y,
            $player->angle,
        );

        DrawText(
            $playerDebugMessage,
            0,
            GetScreenHeight() - $this->fontSize,
            $this->fontSize,
            Color::darkGreen(),
        );
    }
}