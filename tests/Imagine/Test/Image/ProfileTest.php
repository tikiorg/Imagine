<?php

/*
 * This file is part of the Imagine package.
 *
 * (c) Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Imagine\Test\Image;

use Imagine\Image\Profile;
use Imagine\Test\ImagineTestCase;

class ProfileTest extends ImagineTestCase
{
    public function testName()
    {
        $profile = new Profile('romain', 'neutron');
        $this->assertEquals('romain', $profile->name());
    }

    public function testData()
    {
        $profile = new Profile('romain', 'neutron');
        $this->assertEquals('neutron', $profile->data());
    }

    public function testFromPath()
    {
        $file = __DIR__ . '/../../../../lib/Imagine/resources/colormanagement.org/ISOcoated_v2_grey1c_bas.ICC';
        $profile = Profile::fromPath($file);

        $this->assertEquals(basename($file), $profile->name());
        $this->assertEquals(file_get_contents($file), $profile->data());
    }

    /**
     * @expectedException Imagine\Exception\InvalidArgumentException
     */
    public function testFromInvalidPath()
    {
        $this->expectException(\Imagine\Exception\InvalidArgumentException::class);
        $file = __DIR__ . '/non-existent-profile.icc';
        Profile::fromPath($file);
    }
}
