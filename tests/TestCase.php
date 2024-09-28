<?php

namespace Darinlarimore\SimpleCommerceUps\Tests;

use Statamic\Testing\AddonTestCase;
use Darinlarimore\SimpleCommerceUps\ServiceProvider;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
