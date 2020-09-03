<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use raylib\{
    Color,
    Draw,
    Text,
    Timming,
    Window,
};

class Renderer
{
    public int $screenWidth;

    public int $screenHeight;

    public Map $map;

    private array $mapEdges;

    private Player $player;

    private int $scaleFactor = 15;

    private array $nodes = [];

    public function __construct(int $screenWidth, int $screenHeight, Map $map)
    {
        $this->screenWidth = --$screenWidth;
        $this->screenHeight = --$screenHeight;
        $this->map = $map;
        $this->mapEdges = $map->fetchMapEdges();
        $this->player = $map->fetchPlayer(1);
    }

    public function render(): void
    {
        Draw::begin();

        Draw::clearBackground(new Color(255, 255, 255, 255));

        $this->renderAutomap();
        $this->renderScene();

        $this->renderDebugInfo();

        Draw::end();
    }

    private function renderAutomap(): void
    {
        $vertices = $this->map->vertices();
        foreach ($this->map->linedefs() as $line) {
            list($v1, $v2) = $line;
            list($x0, $y0) = $vertices[$v1];
            list($x1, $y1) = $vertices[$v2];

            Draw::line(
                $this->remapXToScreen($x0),
                $this->remapYToScreen($y0),
                $this->remapXToScreen($x1),
                $this->remapYToScreen($y1),
                new Color(rand(0, 255), rand(0, 255), rand(0, 255), 255),
            );
        }

        Draw::circle(
            $this->remapXToScreen($this->player->x),
            $this->remapYToScreen($this->player->y),
            1,
            new Color(255, 0, 0, 255),
        );

        $this->nodes = $this->map->nodes();
        // the last node is the root node
        $this->renderBSPNodes(count($this->nodes) - 1);
    }

    private function renderBSPNodes(int $nodeId): void
    {
        // If that's a subsector, render the subsector instead
        if ($nodeId & 0x8000) {
            // "Convert" this int into int(16)
            $nodeId &= 0xFFFF;

            // Get the subSectorId bit only
            $nodeId &= ~0x8000;

            $this->renderSubSector($nodeId);
            return;
        }

        list($xPartition, $yPartition, $changeXPartition, $changeYPartition) = $this->nodes[$nodeId];
        $dx = $this->player->x - $xPartition;
        $dy = $this->player->y - $yPartition;

        $isOnLeftSide = (
            ($dx * $changeYPartition) - ($dy * $changeXPartition)
        ) <= 0;

        if (true === $isOnLeftSide) {
            $this->renderBSPNodes($this->nodes[$nodeId][13]);
            $this->renderBSPNodes($this->nodes[$nodeId][12]);
        } else {
            $this->renderBSPNodes($this->nodes[$nodeId][12]);
            $this->renderBSPNodes($this->nodes[$nodeId][13]);
        }
    }

    private function renderSubSector(int $subSectorId): void
    {
        // @todo
    }

    private function remapXToScreen(int $xMapPosition): int
    {
        list($xMin) = $this->mapEdges[0]; 

        return (int) (($xMapPosition + (-$xMin)) / $this->scaleFactor);
    }

    private function remapYToScreen(int $yMapPosition): int
    {
        list(, $yMin) = $this->mapEdges[0]; 

        return (int) ($this->screenHeight - ($yMapPosition + (-$yMin)) / $this->scaleFactor);
    }

    private function renderScene(): void
    {}

    private function renderDebugInfo(): void
    {
        Text::drawFPS(0, 0);
    }
}

