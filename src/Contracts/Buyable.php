<?php

namespace Gloudemans\Shoppingcart\Contracts;

use Gloudemans\Shoppingcart\CartItemOptions;

interface Buyable
{
    /**
     * Get the identifier of the Buyable item.
     *
     * @param \Gloudemans\Shoppingcart\CartItemOptions|array<string, mixed>|null $options
     */
    public function getBuyableIdentifier(CartItemOptions | array | null $options = null): int | string;

    /**
     * Get the description or title of the Buyable item.
     *
     * @param \Gloudemans\Shoppingcart\CartItemOptions|array<string, mixed>|null $options
     */
    public function getBuyableDescription(CartItemOptions | array | null $options = null): string;

    /**
     * Get the price of the Buyable item.
     *
     * @param \Gloudemans\Shoppingcart\CartItemOptions|array<string, mixed>|null $options
     */
    public function getBuyablePrice(CartItemOptions | array | null $options = null): float;

    /**
     * Get the weight of the Buyable item.
     *
     * @param \Gloudemans\Shoppingcart\CartItemOptions|array<string, mixed>|null $options
     */
    public function getBuyableWeight(CartItemOptions | array | null $options = null): float;
}
