<?php

namespace Gloudemans\Shoppingcart\Contracts;

interface InstanceIdentifier
{
    /**
     * Get the unique identifier to load the Cart from.
     */
    public function getInstanceIdentifier(mixed $options = null): int | string;

    /**
     * Get the global discount percentage for the Cart instance.
     */
    public function getInstanceGlobalDiscount(mixed $options = null): float | int;
}
