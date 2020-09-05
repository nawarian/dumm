<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

class Map
{
    private const THINGS_OFFSET = 1;
    private const LINEDEFS_OFFSET = 2;
    private const SIDEDEFS_OFFSET = 3;
    private const VERTICES_OFFSET = 4;
    private const SEGS_OFFSET = 5;
    private const SSECTORS_OFFSET = 6;
    private const NODES_OFFSET = 7;
    private const SECTORS_OFFSET = 8;
    private const REJECT_OFFSET = 9;
    private const BLOCKMAP_OFFSET = 10;

    private WAD $wad;
    private array $lumps = [];

    private array $cache = [];

    public function __construct(int $offset, WAD $wad)
    {
        $this->wad = $wad;
        $this->lumps = array_slice($wad->lumps, $offset, 11);
    }

    public function things(): iterable
    {
        $things = $this->lumps[self::THINGS_OFFSET];

        // @todo
        return [];
    }

    public function fetchPlayer(int $number): Player 
    {
        if (isset($this->cache["player-{$number}"])) {
            return $this->cache["player-{$number}"];
        }

        $things = $this->wad->fetchThings(
            $this->lumps[self::THINGS_OFFSET]
        );

        foreach ($things as $thing) {
            list (
                $x,
                $y,
                $angle,
                $type,
                $flags
            ) = $thing;

            if ($number === $type) {
                break;
            }
        }

        $player = new Player(...$thing);
        $this->cache["player-{$number}"] = $player;

        return $player;
    }

    public function linedefs(): array 
    {
        if (isset($this->cache['linedefs'])) {
            return $this->cache['linedefs'];
        }

        $linedefs = $this->wad->fetchLinedefs(
            $this->lumps[self::LINEDEFS_OFFSET],
        );

        $this->cache['linedefs'] = $linedefs;
        return $linedefs;
    }

    public function sidedefs(): array
    {
        if (isset($this->cache['sidedefs'])) {
            return $this->cache['sidedefs'];
        }

        $sidedefs = $this->wad->fetchSideDefs(
            $this->lumps[self::SIDEDEFS_OFFSET]
        );

        $this->cache['sidedefs'] = $sidedefs;
        return $sidedefs;
    }

    public function vertices(): array
    {
        if (isset($this->cache['vertices'])) {
            return $this->cache['vertices'];
        }

        $vertices = $this->wad->fetchVertices(
            $this->lumps[self::VERTICES_OFFSET],
        );

        $this->cache['vertices'] = $vertices;
        return $vertices; 
    }

    public function fetchMapEdges(): array
    {
        $x = [];
        $y = [];
        foreach ($this->vertices() as $v) {
            list($vx, $vy) = $v;
            $x[] = $vx;
            $y[] = $vy;
        }

        return [[min($x), min($y)], [max($x), max($y)]];
    }

    public function segments(): iterable
    {
        if (isset($this->cache['segments'])) {
            return $this->cache['segments'];
        }

        $segments = $this->wad->fetchSegments(
            $this->lumps[self::SEGS_OFFSET],
        );

        $this->cache['segments'] = $segments;
        return $segments;
    }

    public function subSectors(): array
    {
        if (isset($this->cache['subsectors'])) {
            return $this->cache['subsectors'];
        }

        $subSectors = $this->wad->fetchSubSectors(
            $this->lumps[self::SSECTORS_OFFSET],
        );

        $this->cache['subsectors'] = $subSectors;
        return $subSectors;
    }

    public function nodes(): array 
    {
        if (isset($this->cache['nodes'])) {
            return $this->cache['nodes'];
        }

        $nodes = $this->wad->fetchNodes(
            $this->lumps[self::NODES_OFFSET],
        );

        $this->cache['nodes'] = $nodes;
        return $nodes; 
    }

    public function sectors(): iterable
    {
        $sectors = $this->lumps[self::SECTORS_OFFSET];

        // @todo
        return [];
    }

    public function reject(): iterable
    {
        $reject = $this->lumps[self::REJECT_OFFSET];

        // @todo
        return [];
    }

    public function blockmap(): iterable
    {
        $blockmap = $this->lumps[self::BLOCKMAP_OFFSET];

        // @todo
        return [];
    }
}

