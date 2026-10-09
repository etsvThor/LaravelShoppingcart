<?php

namespace Gloudemans\Tests\Shoppingcart\Fixtures;

class ProductModel
{
    public string $someValue = 'Some value';

    public function find(int | string $id): static
    {
        return $this;
    }
}
