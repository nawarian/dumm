<?php

declare(strict_types=1);

namespace Tests\Nawarian\Dumm;

use Nawarian\Dumm\Player;
use Nawarian\Raylib\Types\Vector2;
use PHPUnit\Framework\TestCase;

final class PlayerTest extends TestCase
{
    private Player $player;

    protected function setUp(): void
    {
        $e1m1PlayerSpawnPosition = new Vector2(1056, -3616);
        $this->player = new Player($e1m1PlayerSpawnPosition, 90, 0, 0);
    }

    /**
     * @dataProvider clipVertexesInFovDataProvider
     */
    public function testClipVertexesInFOV(
        Vector2 $v1,
        Vector2 $v2,
        bool $expectedInFov,
        float $expectedV1Angle,
        float $expectedV2Angle,
    ): void {
        $v1Angle = 0.0;
        $v2Angle = 0.0;

        self::assertEquals($expectedInFov, $this->player->clipVertexesInFOV($v1, $v2, $v1Angle, $v2Angle));
        self::assertEquals($expectedV1Angle, $v1Angle, 'v1 angles should match');
        self::assertEquals($expectedV2Angle, $v2Angle, 'v2 angles should match');
    }

    public function clipVertexesInFovDataProvider(): array
    {
        return [
            // Walls behind player
            [new Vector2(1152, -3648), new Vector2(1088, -3648), false, 251.57, 225.0],
            [new Vector2(1024, -3680), new Vector2(1088, -3680), false, 153.43, 206.57],
            [new Vector2(832, -3552), new Vector2(960, -3648), false, 74.05, 108.43],

            // Walls right in front of the player
            [new Vector2(1184, -3392), new Vector2(1216, -3392), true, 60.26, 54.46],

            // Partially in FOV
            // $v2Angle would normally be 41.63 but it is clipped by FOV, increasing the angle
            [new Vector2(1216, -3392), new Vector2(1344, -3360), true, 54.46, 45],
            [new Vector2(512, -3136), new Vector2(680, -3104), true, 135, 126.29],
        ];
    }
}
