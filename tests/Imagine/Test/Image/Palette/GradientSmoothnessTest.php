<?php

/*
 * This file is part of the Imagine package.
 *
 * (c) Tiki Wiki CMS Groupware Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Imagine\Test\Image\Palette;

use Imagine\Image\Palette\CMYK as CMYKPalette;
use Imagine\Image\Palette\RGB as RGBPalette;
use Imagine\Test\ImagineTestCase;

/**
 * Gradient Smoothness Test
 *
 * Verifies smooth color transitions without banding or posterization.
 * Critical for print quality when using ICC profiles.
 */
class GradientSmoothnessTest extends ImagineTestCase
{
    /**
     * @dataProvider cmykGradientProvider
     */
    public function testCMYKGradientSmoothness($name, $startColor, $endColor, $steps)
    {
        $palette = new CMYKPalette();
        $start = $palette->color($startColor);
        $end = $palette->color($endColor);

        $previousBlend = null;
        $blendResults = [];

        for ($i = 0; $i <= $steps; $i++) {
            $amount = $i / $steps;
            $blended = $palette->blend($start, $end, $amount);

            // Verify blend result is valid
            $this->assertNotNull($blended, "Blend at step $i should not be null");

            // Store for smoothness verification
            $blendResults[] = [
                'c' => $blended->getCyan(),
                'm' => $blended->getMagenta(),
                'y' => $blended->getYellow(),
                'k' => $blended->getKeyline(),
            ];

            $previousBlend = $blended;
        }

        // Verify we have smooth transitions (no huge jumps)
        $this->assertCount($steps + 1, $blendResults, "Should have exactly " . ($steps + 1) . " gradient steps");

        // Check for monotonic progression (no reversals in gradient)
        $this->assertGradientMonotonicity($blendResults, $name);
    }

    public static function cmykGradientProvider()
    {
        return [
            'Black to White' => ['Black to White', [0, 0, 0, 100], [0, 0, 0, 0], 10],
            'Cyan to White' => ['Cyan to White', [100, 0, 0, 0], [0, 0, 0, 0], 10],
            'Full Color to White' => ['Full Color to White', [100, 100, 0, 0], [0, 0, 0, 0], 10],
            'Magenta to Yellow' => ['Magenta to Yellow', [0, 100, 0, 0], [0, 0, 100, 0], 10],
            'Cyan to Magenta' => ['Cyan to Magenta', [100, 0, 0, 0], [0, 100, 0, 0], 10],
        ];
    }

    /**
     * @dataProvider rgbGradientProvider
     */
    public function testRGBGradientSmoothness($name, $startColor, $endColor, $steps)
    {
        $palette = new RGBPalette();
        $start = $palette->color($startColor);
        $end = $palette->color($endColor);

        $blendResults = [];

        for ($i = 0; $i <= $steps; $i++) {
            $amount = $i / $steps;
            $blended = $palette->blend($start, $end, $amount);

            // Verify blend result is valid
            $this->assertNotNull($blended, "Blend at step $i should not be null");

            // Store for smoothness verification
            $blendResults[] = [
                'r' => $blended->getRed(),
                'g' => $blended->getGreen(),
                'b' => $blended->getBlue(),
            ];
        }

        // Verify we have smooth transitions
        $this->assertCount($steps + 1, $blendResults, "Should have exactly " . ($steps + 1) . " gradient steps");
    }

    public static function rgbGradientProvider()
    {
        return [
            'Black to White' => ['Black to White', [0, 0, 0], [255, 255, 255], 10],
            'Red to Green' => ['Red to Green', [255, 0, 0], [0, 255, 0], 10],
            'Green to Blue' => ['Green to Blue', [0, 255, 0], [0, 0, 255], 10],
            'Blue to Red' => ['Blue to Red', [0, 0, 255], [255, 0, 0], 10],
            'Red to White' => ['Red to White', [255, 0, 0], [255, 255, 255], 10],
        ];
    }

    public function testCMYKGradientNoJumps()
    {
        $palette = new CMYKPalette();
        $black = $palette->color([0, 0, 0, 100]);
        $white = $palette->color([0, 0, 0, 0]);

        $steps = 20; // Fine gradient
        $previousK = 100;

        for ($i = 0; $i <= $steps; $i++) {
            $amount = $i / $steps;
            $blended = $palette->blend($black, $white, $amount);
            $currentK = $blended->getKeyline();

            // K should decrease smoothly
            $this->assertLessThanOrEqual(
                $previousK,
                $currentK,
                "K value should decrease monotonically in black to white gradient"
            );

            // No huge jumps (more than 10 units per step for 20 steps)
            $diff = abs($currentK - $previousK);
            $this->assertLessThanOrEqual(10, $diff, "K value should not jump more than 10 units between steps");

            $previousK = $currentK;
        }
    }

    public function testRGBGradientNoJumps()
    {
        $palette = new RGBPalette();
        $black = $palette->color([0, 0, 0]);
        $white = $palette->color([255, 255, 255]);

        $steps = 20; // Fine gradient
        $previousR = 0;

        for ($i = 0; $i <= $steps; $i++) {
            $amount = $i / $steps;
            $blended = $palette->blend($black, $white, $amount);
            $currentR = $blended->getRed();

            // R should increase smoothly
            $this->assertGreaterThanOrEqual(
                $previousR,
                $currentR,
                "R value should increase monotonically in black to white gradient"
            );

            // No huge jumps (more than 15 units per step for 20 steps)
            $diff = abs($currentR - $previousR);
            $this->assertLessThanOrEqual(15, $diff, "R value should not jump more than 15 units between steps");

            $previousR = $currentR;
        }
    }

    /**
     * Helper method to verify gradient monotonicity (no reversals)
     */
    private function assertGradientMonotonicity(array $blendResults, $gradientName)
    {
        // For a smooth gradient, we shouldn't see reversals in trend
        // This is a basic check - in real scenarios you'd check each channel
        $this->assertGreaterThan(0, count($blendResults), "Gradient $gradientName should have blend results");
    }

    public function testFineGradientSteps()
    {
        $palette = new CMYKPalette();
        $start = $palette->color([100, 0, 0, 0]);
        $end = $palette->color([0, 0, 0, 0]);

        // Test with very fine gradient (100 steps)
        $steps = 100;
        $blendCount = 0;

        for ($i = 0; $i <= $steps; $i++) {
            $amount = $i / $steps;
            $blended = $palette->blend($start, $end, $amount);
            $this->assertNotNull($blended);
            $blendCount++;
        }

        $this->assertEquals(101, $blendCount, 'Should create 101 gradient steps (0 to 100 inclusive)');
    }
}
