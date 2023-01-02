<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Unit\Platforms;

use Kafoso\DoctrineFirebirdDriver\Platforms\FirebirdInterbasePlatform;

abstract class AbstractFirebirdInterbasePlatformTest extends \PHPUnit\Framework\TestCase
{
    protected $_platform;

    public function setUp(): void
    {
        $this->_platform = new FirebirdInterbasePlatform;
    }
}
