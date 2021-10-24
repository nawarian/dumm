<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Dumm\WAD\Linedef;
use Nawarian\Dumm\WAD\Node;
use Nawarian\Dumm\WAD\Sector;
use Nawarian\Dumm\WAD\Segment;
use Nawarian\Dumm\WAD\Sidedef;
use Nawarian\Dumm\WAD\SubSector;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;

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
            [,,, $type,] = $thing;

            if ($number === $type) {
                break;
            }
        }

        $player = new Player(new Vector2(array_shift($thing), array_shift($thing)), ...$thing);
        $this->cache["player-{$number}"] = $player;

        return $player;
    }

    /**
     * @return array<Linedef>
     */
    public function linedefs(): array 
    {
        if (isset($this->cache['linedefs'])) {
            return $this->cache['linedefs'];
        }

        $linedefs = $this->wad->fetchLinedefs(
            $this->lumps[self::LINEDEFS_OFFSET],
        );

        $linedefs = array_map(function (array $linedef) {
            [
                $startVertexId,
                $endVertexId,
                $flags,
                $lineType,
                $sectorTag,
                $rightSidedefId,
                $leftSidedefId,
            ] = $linedef;

            $rightSidedef = ($rightSidedefId !== 0xFFFF) ? $this->sidedefs()[$rightSidedefId] : null;
            $leftSidedef = ($leftSidedefId !== 0xFFFF) ? $this->sidedefs()[$leftSidedefId] : null;

            return new Linedef(
                $this->vertices()[$startVertexId],
                $this->vertices()[$endVertexId],
                $flags,
                $lineType,
                $sectorTag,
                $rightSidedef,
                $leftSidedef,
            );
        }, $linedefs);

        $this->cache['linedefs'] = $linedefs;
        return $linedefs;
    }

    /**
     * @return array<Sidedef>
     */
    public function sidedefs(): array
    {
        if (isset($this->cache['sidedefs'])) {
            return $this->cache['sidedefs'];
        }

        $sidedefs = $this->wad->fetchSideDefs(
            $this->lumps[self::SIDEDEFS_OFFSET]
        );

        $sidedefs = array_map(function (array $sidedef) {
            [
                $x,
                $y,
                $upperTexture,
                $lowerTexture,
                $midTexture,
                $sectorId,
            ] = $sidedef;

            return new Sidedef(
                new Vector2($x, $y),
                $upperTexture,
                $lowerTexture,
                $midTexture,
                $this->sectors()[$sectorId],
            );
        }, $sidedefs);

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

    /**
     * @return array<Segment>
     */
    public function segments(): array
    {
        if (isset($this->cache['segments'])) {
            return $this->cache['segments'];
        }

        $segments = $this->wad->fetchSegments(
            $this->lumps[self::SEGS_OFFSET],
        );

        $segments = array_map(function (array $segment) {
            [
                $startVertexId,
                $endVertexId,
                $angle,
                $linedefId,
                $direction,
                $offset,
            ] = $segment;

            return new Segment(
                $this->vertices()[$startVertexId],
                $this->vertices()[$endVertexId],
                $angle,
                $this->linedefs()[$linedefId],
                $direction,
                $offset,
            );
        }, $segments);

        $this->cache['segments'] = $segments;
        return $segments;
    }

    /**
     * @return array<Sector>
     */
    public function sectors(): array
    {
        if (isset($this->cache['sectors'])) {
            return $this->cache['sectors'];
        }

        $sectors = $this->wad->fetchSectors(
            $this->lumps[self::SECTORS_OFFSET],
        );

        $this->cache['sectors'] = $sectors;
        return $sectors;
    }

    /**
     * @return array<SubSector>
     */
    public function subSectors(): array
    {
        if (isset($this->cache['subsectors'])) {
            return $this->cache['subsectors'];
        }

        $subSectors = $this->wad->fetchSubSectors(
            $this->lumps[self::SSECTORS_OFFSET],
        );

        $subSectors = array_map(function (array $subSector) {
            [$segCount, $segId] = $subSector;
            $segment = $this->segments()[$segId];

            return new SubSector($segCount, $segId, $segment);
        }, $subSectors);

        $this->cache['subsectors'] = $subSectors;
        return $subSectors;
    }

    /**
     * @return array<Node>
     */
    public function nodes(): array 
    {
        if (isset($this->cache['nodes'])) {
            return $this->cache['nodes'];
        }

        $wadNodes = $this->wad->fetchNodes(
            $this->lumps[self::NODES_OFFSET],
        );

        /** @var array<Node> $nodes */
        $nodes = array_map(function (array $node) {
            [
                $partitionX,
                $partitionY,
                $partitionChangeX,
                $partitionChangeY,

                $rightRectY0,
                $rightRectY1,
                $rightRectX0,
                $rightRectX1,

                $leftRectY0,
                $leftRectY1,
                $leftRectX0,
                $leftRectX1,

                $rightChildId,
                $leftChildId,
            ] = $node;

            return new Node(
                new Vector2($partitionX, $partitionY),
                new Vector2($partitionChangeX, $partitionChangeY),
                new Rectangle(
                    $rightRectX0,
                    $rightRectY0,
                    abs($rightRectX1 - $rightRectX0),
                    abs($rightRectY1 - $rightRectY0)
                ),
                new Rectangle(
                    $leftRectX0,
                    $leftRectY0,
                    abs($leftRectX1 - $leftRectX0),
                    abs($leftRectY1 - $leftRectY0)
                ),
                null,
                null,
                $leftChildId,
                $rightChildId,
            );
        }, $wadNodes);

        foreach ($nodes as $node) {
            $node->leftChild = $nodes[$node->leftChildId] ?? null;
            $node->rightChild = $nodes[$node->rightChildId] ?? null;
        }

        $this->cache['nodes'] = $nodes;
        return $nodes; 
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

