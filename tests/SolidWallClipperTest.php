<?php

declare(strict_types=1);

namespace Tests\Nawarian\Dumm;

use Nawarian\Dumm\SolidSegmentData;
use Nawarian\Dumm\SolidWallClipper;
use Nawarian\Dumm\WAD\Linedef;
use Nawarian\Dumm\WAD\Segment;
use Nawarian\Raylib\Types\Vector2;
use PHPUnit\Framework\TestCase;
use SplStack;

class SolidWallClipperTest extends TestCase
{
    private SolidWallClipper $clipper;

    protected function setUp(): void
    {
        $this->clipper = new SolidWallClipper();
    }

    public function testSpansOutsideScreenAreNotVisible(): void
    {
        $segment = $this->craftDummySegment();
        $this->clipper->registerVisibleWallPortion($segment, PHP_INT_MIN, -1);
        $this->clipper->registerVisibleWallPortion($segment, 320, PHP_INT_MAX);

        self::assertEmpty($this->clipper->visibleWalls);

        $this->clipper->registerVisibleWallPortion($segment, 100, 200);

        self::assertEquals(
            new SolidSegmentData($segment, 100, 200),
            $this->clipper->visibleWalls->pop(),
        );

        self::assertEmpty($this->clipper->visibleWalls);
    }

    public function testPartiallyVisibleSegment(): void
    {
        $segment = $this->craftDummySegment();

        $this->clipper->registerVisibleWallPortion($segment, 100, 200);

        // Only the portion between 50 and 99 should be visible
        $this->clipper->registerVisibleWallPortion($segment, 50, 150);

        // Only the portion between 201 and 220 should be visible
        $this->clipper->registerVisibleWallPortion($segment, 180, 220);

        self::assertEquals(
            $this->craftStackFromArray([
                new SolidSegmentData($segment, 100, 200),
                new SolidSegmentData($segment, 50, 99),
                new SolidSegmentData($segment, 201, 220),
            ]),
            $this->clipper->visibleWalls,
        );
    }

    public function testWallOcclusion(): void
    {
        $segment = $this->craftDummySegment();

        $this->clipper->registerVisibleWallPortion($segment, 50, 80);

        // This wall will be split into 2 parts (25-49 and 81-100)
        $this->clipper->registerVisibleWallPortion($segment, 25, 100);

        self::assertEquals(
            $this->craftStackFromArray([
                new SolidSegmentData($segment, 50, 80),
                new SolidSegmentData($segment, 25, 49),
                new SolidSegmentData($segment, 81, 100),
            ]),
            $this->clipper->visibleWalls,
        );
    }

    public function testPartiallyOutsideFOV(): void
    {
        $segment = $this->craftDummySegment();

        $this->clipper->registerVisibleWallPortion($segment, -200, 100);
        $this->clipper->registerVisibleWallPortion($segment, 200, 400);

        self::assertEquals(
            $this->craftStackFromArray([
                new SolidSegmentData($segment, 0, 100),
                new SolidSegmentData($segment, 200, 319),
            ]),
            $this->clipper->visibleWalls,
        );
    }

    private function craftDummySegment(): Segment
    {
        return new Segment(
            new Vector2(0, 0),
            new Vector2(0, 0),
            0,
            new Linedef(
                new Vector2(0, 0),
                new Vector2(0, 0),
                0,
                0,
                0,
                null,
                null,
            ),
            0,
            0,
        );
    }

    private function craftStackFromArray(array $input): SplStack
    {
        $stack = new SplStack();

        foreach ($input as $item) {
            $stack->push($item);
        }

        return $stack;
    }
}
