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
}

