<?php

declare(strict_types=1);

namespace Nawarian\Dumm\Renderer;

use Nawarian\Dumm\GameState;
use Nawarian\Raylib\Types\{Camera2D, Color};
use function Nawarian\Raylib\{DrawFPS, DrawText, GetScreenHeight, GetScreenWidth, MeasureText};

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

        $systemMessage = sprintf('Mem.: %d Kb', memory_get_usage(true) / 1024);
        $systemMessageSize = MeasureText($systemMessage, $this->fontSize);
        DrawText(
            $systemMessage,
            GetScreenWidth() - $systemMessageSize,
            GetScreenHeight() - $this->fontSize,
            $this->fontSize,
            Color::darkGreen(),
        );
    }
}
