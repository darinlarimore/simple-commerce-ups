<?php

namespace Darinlarimore\SimpleCommerceUps\Console\Commands;

use Statamic\Console\RunsInPlease;
use Darinlarimore\SimpleCommerceUps\Console\Commands\GeneratorCommand;

class MakeUPSShippingMethod extends GeneratorCommand
{
    use RunsInPlease;

    protected $name = 'statamic:make:ups-shipping-method';

    protected $description = 'Create a new UPS shipping method';

    protected $type = 'ShippingMethod';

    protected $stub = 'ups-shipping.php.stub';

    public function handle()
    {
        if (parent::handle() === false) {
            return false;
        }
    }
}
