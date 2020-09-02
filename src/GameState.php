<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

class GameState
{
    private WAD $wad;

    public Map $map;

    public function __construct(WAD $wad)
    {
        $this->wad = $wad;
    }

    public function setMap(string $mapId): void
    {
        $this->map = $this->wad->fetchMap($mapId);
    }
}

