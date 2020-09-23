<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use PhpBinaryReader\{BinaryReader, Endian};

class WAD
{
    public BinaryReader $reader;

    /**
     * @var array<string, array> a map with the format 'name' => [offset<int>, size<int>]
     */
    public array $lumps = [];

    public static function fromFile(string $filePath): self
    {
        $handle = fopen($filePath, 'rb');
        $reader = new BinaryReader($handle, Endian::LITTLE);

        $wad = new self();
        $wad->reader = $reader;

        $wad->initialize();

        return $wad;
    }

    private function initialize(): void
    {
        // Should be "IWAD" or "PWAD"
        $this->reader->setPosition(0);
        $wadType = $this->reader->readString(4);
        $directoryCount = $this->reader->readInt32();

        $this->reader->setPosition($this->reader->readInt32());

        // Read directory
        for ($i = 0; $i < $directoryCount; ++$i) {
            $lumpOffset = $this->reader->readInt32();
            $lumpSize = $this->reader->readInt32();
            $lumpName = $this->reader->readString(8);

            $this->lumps[$i] = [$lumpName, $lumpOffset, $lumpSize];
        }
    }

    public function fetchMap(string $mapId): Map
    {
        $mapId = strtoupper($mapId);

        // Advance array pointer to MAP lump
        foreach ($this->lumps as $offset => $data) {
            list($name) = $data;
            if (trim($name) === $mapId) {
                break;
            }
        }

        return new Map($offset, $this);
    }

    public function fetchThings(array $lump): array
    {
        list(, $offset, $size) = $lump;

        $this->reader->setPosition($offset);
        $thingsLumpSizeInBytes = 2 * 5;
        $things = [];
        for ($i = 0; $i < $size / $thingsLumpSizeInBytes; ++$i) {
            $things[$i] = [
                $this->reader->readInt16(),
                $this->reader->readInt16(),
                $this->reader->readUint16(),
                $this->reader->readUint16(),
                $this->reader->readUint16(),
            ];
        }

        return $things;
    }

    public function fetchVertices(array $lump): array
    {
        list(, $offset, $size) = $lump;

        $this->reader->setPosition($offset);
        $vertexLumpSizeInBytes = 2 * 2;
        $vertices = [];
        for ($i = 0; $i < $size / $vertexLumpSizeInBytes; ++$i) {
            $vertices[$i] = [
                $this->reader->readInt16(),
                $this->reader->readInt16(),
            ];
        }

        return $vertices;
    }

    public function fetchLinedefs(array $lump): array
    {
        list(, $offset, $size) = $lump;

        $this->reader->setPosition($offset);
        $lineDefLumpSizeInBytes = 2 * 7;
        $linedefs = [];
        for ($i = 0; $i < $size / $lineDefLumpSizeInBytes; ++$i) {
            $linedefs[$i] = [
                $this->reader->readUint16(), // start vertex
                $this->reader->readUint16(), // end vertex
                $this->reader->readUint16(), // flags
                $this->reader->readUint16(), // line type / action
                $this->reader->readUint16(), // sector tag
                $this->reader->readUint16(), // right sidedef (0xFFFF side not present)
                $this->reader->readUint16(), // left sidedef (0xFFFF side not present)
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
        return $linedefs;
    }

    public function fetchSideDefs(array $lump): array
    {
        list(, $offset, $size) = $lump;

        $this->reader->setPosition($offset);
        $sideDefsSizeInBytes = 30; // 2 short, 3 8-byte chars, 1 short
        $sidedefs = [];
        for ($i = 0; $i < $size / $sideDefsSizeInBytes; ++$i) {
            $sidedefs[$i] = [
                // X, Y offsets
                $this->reader->readInt16(),
                $this->reader->readInt16(),
                // Textures (upper, lower, mid)
                $this->reader->readString(8),
                $this->reader->readString(8),
                $this->reader->readString(8),
                // sector id
                $this->reader->readUint16(),
            ];
        }

        return $sidedefs;
    }

    public function fetchNodes(array $lump): array
    {
        list(, $offset, $size) = $lump;

        $this->reader->setPosition($offset);
        $nodesLumpSizeInBytes = 14 * 2;
        $nodes = [];
        for ($i = 0; $i < $size / $nodesLumpSizeInBytes; ++$i) {
            $nodes[$i] = [
                // X, Y partition
                $this->reader->readInt16(),
                $this->reader->readInt16(),
                // Change X, Y partition
                $this->reader->readInt16(),
                $this->reader->readInt16(),

                // Right Rect
                $this->reader->readInt16(), // y0
                $this->reader->readInt16(), // y1
                $this->reader->readInt16(), // x0
                $this->reader->readInt16(), // x1

                // Left Rect
                $this->reader->readInt16(), // y0
                $this->reader->readInt16(), // y1
                $this->reader->readInt16(), // x0
                $this->reader->readInt16(), // x1

                // Right, Left child IDs
                $this->reader->readInt16(),
                $this->reader->readInt16(),
            ];
        }

        return $nodes;
    }

    public function fetchSectors(array $lump): array
    {
        list (, $offset, $size) = $lump;

        $this->reader->setPosition($offset);
        $sectorsSizeInBytes = 14; // int16 * 2 + char8 * 2 + uint16 * 3
        $sectors = [];
        for ($i = 0; $i < $size / $sectorsSizeInBytes; ++$i) {
            $sectors[$i] = [
                // Floor & Ceiling height
                $this->reader->readInt16(),
                $this->reader->readInt16(),

                // Floor & Ceiling textures
                $this->reader->readString(8),
                $this->reader->readString(8),

                // LightLevel
                $this->reader->readUInt16(),
                // Type
                $this->reader->readUInt16(),
                // Tag
                $this->reader->readUInt16(),
            ];
        }

        return $sectors;
    }

    public function fetchSubSectors(array $lump): array
    {
        list(, $offset, $size) = $lump;

        $this->reader->setPosition($offset);
        $subSectorsSizeInBytes = 2 * 2;
        $subSectors = [];
        for ($i = 0; $i < $size / $subSectorsSizeInBytes; ++$i) {
            $subSectors[$i] = [
                // Segcount
                $this->reader->readUint16(),
                // Seg
                $this->reader->readUint16(),
            ];
        }

        return $subSectors;
    }

    public function fetchSegments(array $lump): array
    {
        list(, $offset, $size) = $lump;

        $this->reader->setPosition($offset);
        $segmentsSizeInBytes = 6 * 2;
        $segments = [];
        for ($i = 0; $i < $size / $segmentsSizeInBytes; ++$i) {
            $segments[$i] = [
                // Start and End vertices IDs
                $this->reader->readUint16(),
                $this->reader->readUint16(),
                // Angle
                $this->reader->readUint16(),
                // Linedef ID
                $this->reader->readUint16(),
                // Direction
                $this->reader->readUint16(),
                // Offset
                $this->reader->readUint16(),
            ];
        }

        return $segments;
    }
}

