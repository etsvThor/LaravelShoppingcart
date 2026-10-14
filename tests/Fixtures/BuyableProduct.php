<?php

namespace Gloudemans\Tests\Shoppingcart\Fixtures;

use Gloudemans\Shoppingcart\CartItemOptions;
use Gloudemans\Shoppingcart\Contracts\Buyable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property float $price
 * @property float $weight
 */
class BuyableProduct extends Model implements Buyable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'title',
        'description',
        'price',
        'weight',
    ];

    protected $attributes = [
        'id' => 1,
        'name' => 'Item name',
        'price' => 10.00,
        'weight' => 0,
    ];

    public function getBuyableIdentifier(CartItemOptions | array | null $options = null): int | string
    {
        return $this->id;
    }

    public function getBuyableDescription(CartItemOptions | array | null $options = null): string
    {
        return $this->name;
    }

    public function getBuyablePrice(CartItemOptions | array | null $options = null): float
    {
        return $this->price;
    }

    public function getBuyableWeight(CartItemOptions | array | null $options = null): float
    {
        return $this->weight;
    }
}
