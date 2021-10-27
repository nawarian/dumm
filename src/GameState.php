<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Dumm\WAD\{Map, Player, WAD};

class GameState
{
    private WAD $wad;

    public Map $map;

    public Player $player;

    public function __construct(WAD $wad)
    {
        $this->wad = $wad;
    }

    public function setMap(string $mapId): void
    {
        $this->map = $this->wad->fetchMap($mapId);
        $this->player = $this->map->fetchPlayer(1);
    }
}
