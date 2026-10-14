<?php

namespace Gloudemans\Shoppingcart;

use Illuminate\Support\Collection;

/**
 * @extends \Illuminate\Support\Collection<string, mixed>
 */
class CartItemOptions extends Collection
{
    /**
     * Get the option by the given key.
     *
     * @param string $key
     */
    public function __get($key): mixed
    {
        return $this->get($key);
    }
}
