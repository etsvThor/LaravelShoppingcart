<?php

namespace Gloudemans\Shoppingcart;

trait CanBeBought
{
    /**
     * Get the identifier of the Buyable item.
     *
     * @param \Gloudemans\Shoppingcart\CartItemOptions|array<string, mixed>|null $options
     */
    public function getBuyableIdentifier(CartItemOptions | array | null $options = null): int | string
    {
        return $this->getKey();
    }

    /**
     * Get the name, title or description of the Buyable item.
     *
     * @param \Gloudemans\Shoppingcart\CartItemOptions|array<string, mixed>|null $options
     */
    public function getBuyableDescription(CartItemOptions | array | null $options = null): string
    {
        return (string) ($this->getAttribute('name') ?: $this->getAttribute('title') ?: $this->getAttribute('description'));
    }

    /**
     * Get the price of the Buyable item.
     *
     * @param \Gloudemans\Shoppingcart\CartItemOptions|array<string, mixed>|null $options
     */
    public function getBuyablePrice(CartItemOptions | array | null $options = null): float
    {
        return (float) $this->getAttribute('price');
    }

    /**
     * Get the weight of the Buyable item.
     *
     * @param \Gloudemans\Shoppingcart\CartItemOptions|array<string, mixed>|null $options
     */
    public function getBuyableWeight(CartItemOptions | array | null $options = null): float
    {
        return (float) $this->getAttribute('weight');
    }
}
