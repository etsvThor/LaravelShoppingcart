<?php

namespace Gloudemans\Shoppingcart\Contracts;

use Gloudemans\Shoppingcart\CartItem;

interface Calculator
{
    /**
     * Calculate the given attribute for the cart item, or return null when the attribute is unknown.
     */
    public static function getAttribute(string $attribute, CartItem $cartItem): float | int | null;
}
