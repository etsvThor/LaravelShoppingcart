<?php

namespace Gloudemans\Tests\Shoppingcart\Fixtures;

use Gloudemans\Shoppingcart\Contracts\InstanceIdentifier;

class Identifiable implements InstanceIdentifier
{
    public function __construct(
        private int | string $identifier = 'identifier',
        private float | int $discountRate = 0,
    ) {}

    public function getInstanceIdentifier(mixed $options = null): int | string
    {
        return $this->identifier;
    }

    public function getInstanceGlobalDiscount(mixed $options = null): float | int
    {
        return $this->discountRate;
    }
}
