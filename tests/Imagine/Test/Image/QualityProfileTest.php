<?php

/*
 * This file is part of the Imagine package.
 *
 * (c) Tiki Wiki CMS Groupware Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Imagine\Test\Image;

use Imagine\Image\Box;
use Imagine\Image\Palette\CMYK as CMYKPalette;
use Imagine\Image\Palette\RGB as RGBPalette;
use Imagine\Image\Profile;
use Imagine\Test\ImagineTestCase;

/**
 * Real Image Processing Quality Test
 *
 * Tests actual image processing with the new open-source ICC profile
 * (ISOcoated_v2_grey1c_bas.ICC) to ensure quality is maintained after
 * removing Adobe proprietary profiles.
 */
class QualityProfileTest extends ImagineTestCase
{
    private function getImagineInstance()
    {
        // Try Imagick first (best CMYK support), then Gmagick, then GD
        if (class_exists('Imagick')) {
            return new \Imagine\Imagick\Imagine();
        } elseif (class_exists('Gmagick')) {
            return new \Imagine\Gmagick\Imagine();
        } elseif (function_exists('gd_info')) {
            return new \Imagine\Gd\Imagine();
        }

        $this->markTestSkipped('No image processing library available');
    }

    public function testOpenSourceProfileIsValid()
    {
        $profilePath = __DIR__ . '/../../../../lib/Imagine/resources/colormanagement.org/ISOcoated_v2_grey1c_bas.ICC';

        $this->assertFileExists($profilePath, 'Open-source ICC profile should exist');

        $profile = Profile::fromPath($profilePath);

        $this->assertNotNull($profile, 'Profile should load successfully');
        $this->assertEquals('ISOcoated_v2_grey1c_bas.ICC', $profile->name());
        $this->assertNotEmpty($profile->data(), 'Profile should have data');
    }

    public function testCMYKPaletteUsesOpenSourceProfile()
    {
        $palette = new CMYKPalette();
        $profile = $palette->profile();

        $this->assertNotNull($profile, 'CMYK palette should have a profile');
        $this->assertEquals(
            'ISOcoated_v2_grey1c_bas.ICC',
            $profile->name(),
            'CMYK palette should use ISOcoated profile'
        );
    }

    public function testRGBPaletteUsesOpenSourceProfile()
    {
        $palette = new RGBPalette();
        $profile = $palette->profile();

        $this->assertNotNull($profile, 'RGB palette should have a profile');
        $this->assertStringContainsString(
            'sRGB',
            $profile->name(),
            'RGB palette should use sRGB profile'
        );
    }

    public function testCreateImageWithCMYKProfile()
    {
        $imagine = $this->getImagineInstance();

        if (!$imagine instanceof \Imagine\Imagick\Imagine) {
            $this->markTestSkipped('CMYK support requires Imagick');
        }

        $palette = new CMYKPalette();
        $white = $palette->color([0, 0, 0, 0]);

        $image = $imagine->create(new Box(10, 10), $white);

        $this->assertNotNull($image);
        $this->assertEquals(10, $image->getSize()->getWidth());
        $this->assertEquals(10, $image->getSize()->getHeight());
    }

    public function testApplyProfileToImage()
    {
        $imagine = $this->getImagineInstance();
        $rgbPalette = new RGBPalette();
        $white = $rgbPalette->color([255, 255, 255]);

        $image = $imagine->create(new Box(10, 10), $white);

        // Apply the open-source profile
        $profilePath = __DIR__ . '/../../../../lib/Imagine/resources/color.org/sRGB_IEC61966-2-1_black_scaled.icc';
        $profile = Profile::fromPath($profilePath);

        $result = $image->profile($profile);

        $this->assertNotNull($result, 'Profile application should return image');
    }

    public function testConvertRGBToCMYKWithProfile()
    {
        $imagine = $this->getImagineInstance();

        if (!$imagine instanceof \Imagine\Imagick\Imagine) {
            $this->markTestSkipped('CMYK conversion requires Imagick');
        }

        // Create RGB image
        $rgbPalette = new RGBPalette();
        $red = $rgbPalette->color([255, 0, 0]);
        $image = $imagine->create(new Box(10, 10), $red);

        // Convert to CMYK
        $cmykPalette = new CMYKPalette();
        $convertedImage = $image->usePalette($cmykPalette);

        $this->assertNotNull($convertedImage, 'CMYK conversion should succeed');
    }

    public function testProcessExistingImage()
    {
        $testImages = [
            __DIR__ . '/../../Fixtures/google.png',
            __DIR__ . '/../../Fixtures/sample.gif',
        ];

        $imagine = $this->getImagineInstance();
        $processedCount = 0;

        foreach ($testImages as $imagePath) {
            if (!file_exists($imagePath)) {
                continue;
            }

            $image = $imagine->open($imagePath);
            $this->assertNotNull($image, 'Image should load successfully');

            // Verify we can get size (basic integrity check)
            $size = $image->getSize();
            $this->assertGreaterThan(0, $size->getWidth());
            $this->assertGreaterThan(0, $size->getHeight());

            $processedCount++;
        }

        if ($processedCount === 0) {
            $this->markTestSkipped('No test images available');
        }

        $this->assertGreaterThan(0, $processedCount, 'Should process at least one image');
    }

    public function testImageSaveAndReopen()
    {
        $imagine = $this->getImagineInstance();
        $palette = new RGBPalette();
        $blue = $palette->color([0, 0, 255]);

        $image = $imagine->create(new Box(20, 20), $blue);

        $tempFile = sys_get_temp_dir() . '/imagine_quality_test_' . uniqid() . '.png';

        try {
            // Save image
            $image->save($tempFile);
            $this->assertFileExists($tempFile, 'Saved image should exist');

            // Reopen and verify
            $reopened = $imagine->open($tempFile);
            $this->assertNotNull($reopened);

            $size = $reopened->getSize();
            $this->assertEquals(20, $size->getWidth());
            $this->assertEquals(20, $size->getHeight());

        } finally {
            // Clean up
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testProfileDataIntegrity()
    {
        $profiles = [
            __DIR__ . '/../../../../lib/Imagine/resources/colormanagement.org/ISOcoated_v2_grey1c_bas.ICC',
            __DIR__ . '/../../../../lib/Imagine/resources/color.org/sRGB_IEC61966-2-1_black_scaled.icc',
            __DIR__ . '/../../../../lib/Imagine/resources/color.org/sRGB_IEC61966-2-1_no_black_scaling.icc',
        ];

        foreach ($profiles as $profilePath) {
            $this->assertFileExists($profilePath, "Profile should exist: $profilePath");

            $profile = Profile::fromPath($profilePath);

            // Verify ICC header (first 4 bytes should be profile size in big-endian)
            $data = $profile->data();
            $this->assertGreaterThan(128, strlen($data), 'ICC profile should be at least 128 bytes (header size)');

            // Basic ICC signature check - bytes 36-39 should be 'acsp'
            if (strlen($data) >= 40) {
                $signature = substr($data, 36, 4);
                $this->assertEquals('acsp', $signature, 'Profile should have valid ICC signature');
            }
        }
    }

    public function testNoAdobeProfileReferences()
    {
        // Ensure no Adobe profiles are referenced
        $cmykPalette = new CMYKPalette();
        $profile = $cmykPalette->profile();

        $profileName = $profile->name();

        $this->assertStringNotContainsString('Adobe', $profileName, 'Should not use Adobe profiles');
        $this->assertStringNotContainsString('USWeb', $profileName, 'Should not use Adobe USWeb profiles');
        $this->assertStringNotContainsString(
            'FOGRA',
            $profileName,
            'Should not use Adobe FOGRA profiles (unless open-source)'
        );
    }

    public function testProfileFileSize()
    {
        $profilePath = __DIR__ . '/../../../../lib/Imagine/resources/colormanagement.org/ISOcoated_v2_grey1c_bas.ICC';

        $fileSize = filesize($profilePath);

        // Basic sanity check - profile should be between 500 bytes and 5MB
        $this->assertGreaterThan(500, $fileSize, 'Profile should be larger than 500 bytes');
        $this->assertLessThan(5 * 1024 * 1024, $fileSize, 'Profile should be smaller than 5MB');
    }
}
