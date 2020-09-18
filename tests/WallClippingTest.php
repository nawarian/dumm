<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use PHPUnit\Framework\TestCase;

class WallClippingTest extends TestCase
{
    use WallClippingTrait;

    public function testFullInsert(): void
    {
        $dummySideDef = [0, 0, '', '', '', 0];
        $this->renderableSegments = [
            [PHP_INT_MIN, -1, $dummySideDef],
            [400, 500, $dummySideDef],
            [(int) Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
        ];

        $this->storeClippedWallSegments(200, 300, $dummySideDef);
        $this->storeClippedWallSegments(600, 700, $dummySideDef);

        self::assertEquals(
            [
                [PHP_INT_MIN, -1, $dummySideDef],
                [200, 300, $dummySideDef],
                [400, 500, $dummySideDef],
                [600, 700, $dummySideDef],
                [Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
            ],
            $this->renderableSegments,
        );
    }

    public function testClipRightPart(): void
    {
        $dummySideDef = [0, 0, '', '', '', 0];
        $this->renderableSegments = [
            [PHP_INT_MIN, -1, $dummySideDef],
            [400, 500, $dummySideDef],
            [(int) Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
        ];

        $this->storeClippedWallSegments(300, 450, $dummySideDef);
        $this->storeClippedWallSegments(250, 450, $dummySideDef);

        self::assertEquals(
            [
                [PHP_INT_MIN, -1, $dummySideDef],
                [250, 299, $dummySideDef],
                [300, 399, $dummySideDef],
                [400, 500, $dummySideDef],
                [Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
            ],
            $this->renderableSegments,
        );
    }

    public function testClipLeftPart(): void
    {
        $dummySideDef = [0, 0, '', '', '', 0];
        $this->renderableSegments = [
            [PHP_INT_MIN, -1, $dummySideDef],
            [400, 500, $dummySideDef],
            [(int) Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
        ];

        $this->storeClippedWallSegments(450, 600, $dummySideDef);
        $this->storeClippedWallSegments(590, 670, $dummySideDef);

        self::assertEquals(
            [
                [PHP_INT_MIN, -1, $dummySideDef],
                [400, 500, $dummySideDef],
                [500, 600, $dummySideDef],
                [600, 670, $dummySideDef],
                [Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
            ],
            $this->renderableSegments,
        );
    }

    public function testClipBetween(): void
    {
        $dummySideDef = [0, 0, '', '', '', 0];
        $this->renderableSegments = [
            [PHP_INT_MIN, -1, $dummySideDef],
            [200, 300, $dummySideDef],
            [400, 500, $dummySideDef],
            [(int) Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
        ];

        $this->storeClippedWallSegments(251, 450, $dummySideDef);

        self::assertEquals(
            [
                [PHP_INT_MIN, -1, $dummySideDef],
                [200, 300, $dummySideDef],
                [300, 400, $dummySideDef],
                [400, 500, $dummySideDef],
                [Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
            ],
            $this->renderableSegments,
        );
    }

    public function testClipOuter(): void
    {
        $dummySideDef = [0, 0, '', '', '', 0];
        $this->renderableSegments = [
            [PHP_INT_MIN, -1, $dummySideDef],
            [200, 300, $dummySideDef],
            [(int) Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
        ];

        $this->storeClippedWallSegments(150, 350, $dummySideDef);

        self::assertEquals(
            [
                [PHP_INT_MIN, -1, $dummySideDef],
                [150, 199, $dummySideDef],
                [200, 300, $dummySideDef],
                [301, 350, $dummySideDef],
                [Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
            ],
            $this->renderableSegments,
        );
    }

    public function testClipBetweenMany(): void
    {
        $dummySideDef = [0, 0, '', '', '', 0];
        $this->renderableSegments = [
            [PHP_INT_MIN, -1, $dummySideDef],
            [100, 150, $dummySideDef],
            [200, 250, $dummySideDef],
            [300, 350, $dummySideDef],
            [(int) Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
        ];

        $this->storeClippedWallSegments(125, 325, $dummySideDef);

        self::assertEquals(
            [
                [PHP_INT_MIN, -1, $dummySideDef],
                [100, 150, $dummySideDef],
                [150, 200, $dummySideDef],
                [200, 250, $dummySideDef],
                [250, 300, $dummySideDef],
                [300, 350, $dummySideDef],
                [Game::SCREEN_WIDTH, PHP_INT_MAX, $dummySideDef],
            ],
            $this->renderableSegments,
        );
    }
}
