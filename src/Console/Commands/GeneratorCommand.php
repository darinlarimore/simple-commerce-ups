<?php

namespace Darinlarimore\SimpleCommerceUps\Console\Commands;

use Statamic\Console\Commands\GeneratorCommand as StatamicGeneratorCommand;

class GeneratorCommand extends StatamicGeneratorCommand{

    protected $name = 'simple-commerce-ups:generate';

    protected function getStub($stub = null): string
    {
        $stub = $stub ?? $this->stub;

        return __DIR__.'/stubs/'.$stub;
    }
}
