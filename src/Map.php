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

    public function fetchPlayer(int $number): object
    {
        // @todo
        $player = new stdClass();
        $player->number = 1;
        $player->x = 0;
        $player->y = 0;

        return $player;
    }

    public function linedefs(): array 
    {
        if (isset($this->cache['linedefs'])) {
            return $this->cache['linedefs'];
        }

        list(, $offset, $size) = $this->lumps[self::LINEDEFS_OFFSET];

        $this->wad->reader->setPosition($offset);
        $lineDefLumpSizeInBytes = 2 * 7;
        $linedefs = [];
        for ($i = 0; $i < $size / $lineDefLumpSizeInBytes; ++$i) {
            $linedefs[$i] = [
                $this->wad->reader->readUint16(), // start vertex
                $this->wad->reader->readUint16(), // end vertex
                $this->wad->reader->readUint16(), // flags
                $this->wad->reader->readUint16(), // line type / action
                $this->wad->reader->readUint16(), // sector tag
                $this->wad->reader->readUint16(), // right sidedef (0xFFFF side not present)
                $this->wad->reader->readUint16(), // left sidedef (0xFFFF side not present)
            ];
        }

        /**
         * Linedef Flags
         * 0 = block players and monsters
         * 1 = block monsters
         * 2 = two sided
         * 3 = upper texture is unpegged
         * 4 = lower texture is unpegged
         * 5 = secret (one-sided on automap)
         * 6 = blocks sound
         * 7 = never shows on automap
         * 8 = always shows on automap
         */

        $this->cache['linedefs'] = $linedefs;
        return $linedefs;
    }

    public function sidedefs(): iterable
    {
        $sidedefs = $this->lumps[self::SIDEDEFS_OFFSET];

        // @todo
        return [];
    }

    public function vertices(): array
    {
        if (isset($this->cache['vertices'])) {
            return $this->cache['vertices'];
        }

        list(, $offset, $size) = $this->lumps[self::VERTICES_OFFSET];

        $this->wad->reader->setPosition($offset);
        $vertexLumpSizeInBytes = 2 * 2;
        $vertices = [];
        for ($i = 0; $i < $size / $vertexLumpSizeInBytes; ++$i) {
            $vertices[$i] = [
                $this->wad->reader->readInt16(),
                $this->wad->reader->readInt16(),
            ];
        }

        $this->cache['vertices'] = $vertices;
        return $vertices; 
    }

    public function fetchMapEdges(): array
    {
        $xMin = 0;
        $yMin = 0;
        $xMax = 0;
        $yMax = 0;

        foreach ($this->vertices() as $v) {
            list($x, $y) = $v;
            $xMin = $xMin < $x ? $xMin : $x;
            $yMin = $yMin < $y ? $yMin : $y;
            $xMax = $xMax > $x ? $xMax : $x;
            $yMax = $yMax > $y ? $yMax : $y;
        }

        return [[$xMin, $yMin], [$xMax, $yMax]];
    }

    public function segments(): iterable
    {
        $segments = $this->lumps[self::SEGMENTS_OFFSET];

        // @todo
        return [];
    }

    public function subSectors(): iterable
    {
        $subSectors = $this->lumps[self::SSECTORS_OFFSET];

        // @todo
        return [];
    }

    public function nodes(): iterable
    {
        $nodes = $this->lumps[self::NODES_OFFSET];

        // @todo
        return [];
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

