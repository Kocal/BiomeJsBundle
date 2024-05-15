<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;

final class MyTest extends TestCase
{
    public function testTrue(): void
    {
        $this->assertTrue(true);
    }
}
