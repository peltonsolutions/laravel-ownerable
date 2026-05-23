<?php

namespace Tests;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        Container::setInstance($container);
        $container->instance('config', new ConfigRepository([
            'ownerable' => ['owner' => null],
        ]));
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }
}
