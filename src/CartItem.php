<?php

namespace Gloudemans\Shoppingcart;

use Gloudemans\Shoppingcart\Calculation\DefaultCalculator;
use Gloudemans\Shoppingcart\Contracts\Buyable;
use Gloudemans\Shoppingcart\Contracts\Calculator;
use Gloudemans\Shoppingcart\Exceptions\InvalidCalculatorException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * CartItems are serialized into the session and the database, so the properties
 * holding user supplied values are intentionally left without a native type:
 * adding one would make unserializing previously stored carts fail.
 *
 * @property-read mixed $discount
 * @property-read float $discountTotal
 * @property-read float $priceTarget
 * @property-read float $priceNet
 * @property-read float $priceTotal
 * @property-read float $subtotal
 * @property-read float $taxTotal
 * @property-read float $tax
 * @property-read float $total
 * @property-read float $priceTax
 * @property-read float $weightTotal
 * @property-read mixed $model
 * @property-read class-string|null $modelFQCN
 *
 * @implements \Illuminate\Contracts\Support\Arrayable<string, mixed>
 */
class CartItem implements Arrayable, Jsonable
{
    /**
     * The rowID of the cart item.
     */
    public string $rowId;

    /**
     * The ID of the cart item.
     *
     * @var int|string
     */
    public $id;

    /**
     * The quantity for this cart item.
     *
     * @var int|float
     */
    public $qty;

    /**
     * The name of the cart item.
     *
     * @var string
     */
    public $name;

    /**
     * The price without TAX of the cart item.
     *
     * @var float
     */
    public $price;

    /**
     * The weight of the product.
     *
     * @var float
     */
    public $weight;

    /**
     * The options for this cart item.
     */
    public CartItemOptions $options;

    /**
     * The tax rate for the cart item.
     *
     * @var int|float
     */
    public $taxRate = 0;

    /**
     * The FQN of the associated model.
     *
     * @var class-string|null
     */
    private ?string $associatedModel = null;

    /**
     * The discount rate for the cart item.
     *
     * @var int|float
     */
    private $discountRate = 0;

    /**
     * The cart instance of the cart item.
     */
    public ?string $instance = null;

    /**
     * @param array<string, mixed> $options
     * @throws \InvalidArgumentException
     */
    public function __construct(
        int | string | null $id,
        ?string $name,
        float | int | string | null $price,
        float | int | string | null $weight = 0,
        array $options = [],
    ) {
        if (empty($id)) {
            throw new InvalidArgumentException('Please supply a valid identifier.');
        }
        if (empty($name)) {
            throw new InvalidArgumentException('Please supply a valid name.');
        }
        if (! is_numeric($price)) {
            throw new InvalidArgumentException('Please supply a valid price.');
        }
        if (! is_numeric($weight)) {
            throw new InvalidArgumentException('Please supply a valid weight.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->price = floatval($price);
        $this->weight = floatval($weight);
        $this->options = new CartItemOptions($options);
        $this->rowId = $this->generateRowId($id, $options);
    }

    /**
     * Returns the formatted weight.
     */
    public function weight(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->weight, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted price without TAX.
     */
    public function price(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->price, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted price with discount applied.
     */
    public function priceTarget(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->priceTarget, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted price with TAX.
     */
    public function priceTax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->priceTax, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted subtotal.
     * Subtotal is price for whole CartItem without TAX.
     */
    public function subtotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->subtotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted total.
     * Total is price for whole CartItem with TAX.
     */
    public function total(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->total, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted tax.
     */
    public function tax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->tax, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted total tax.
     */
    public function taxTotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->taxTotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted discount.
     */
    public function discount(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->discount, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted total discount for this cart item.
     */
    public function discountTotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->discountTotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted total price for this cart item.
     */
    public function priceTotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->priceTotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Set the quantity for this cart item.
     *
     * @throws \InvalidArgumentException
     */
    public function setQuantity(int | float | string | null $qty): void
    {
        if (empty($qty)) {
            throw new InvalidArgumentException('Please supply a valid quantity.');
        }

        $this->qty = self::toNumber($qty, 'Please supply a valid quantity.');
    }

    /**
     * Update the cart item from a Buyable.
     */
    public function updateFromBuyable(Buyable $item): void
    {
        $this->id = $item->getBuyableIdentifier($this->options);
        $this->name = $item->getBuyableDescription($this->options);
        $this->price = $item->getBuyablePrice($this->options);
    }

    /**
     * Update the cart item from an array.
     *
     * @param array<string, mixed> $attributes
     * @throws \InvalidArgumentException
     */
    public function updateFromArray(array $attributes): void
    {
        $this->id = Arr::get($attributes, 'id', $this->id);
        $this->qty = self::toNumber(Arr::get($attributes, 'qty', $this->qty), 'Please supply a valid quantity.');
        $this->name = Arr::get($attributes, 'name', $this->name);
        $this->price = (float) self::toNumber(Arr::get($attributes, 'price', $this->price), 'Please supply a valid price.');
        $this->weight = (float) self::toNumber(Arr::get($attributes, 'weight', $this->weight), 'Please supply a valid weight.');
        $this->options = new CartItemOptions(Arr::get($attributes, 'options', $this->options));

        $this->rowId = $this->generateRowId($this->id, $this->options->all());
    }

    /**
     * Associate the cart item with the given model.
     *
     * @param object|class-string $model
     */
    public function associate(object | string $model): static
    {
        $this->associatedModel = is_string($model) ? $model : $model::class;

        return $this;
    }

    /**
     * Set the tax rate.
     */
    public function setTaxRate(float | int | null $taxRate): static
    {
        $this->taxRate = $taxRate ?? 0;

        return $this;
    }

    /**
     * Set the discount rate.
     */
    public function setDiscountRate(float | int | null $discountRate): static
    {
        $this->discountRate = $discountRate ?? 0;

        return $this;
    }

    /**
     * Set cart instance.
     */
    public function setInstance(?string $instance): static
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Create a new instance from a Buyable.
     *
     * @param array<string, mixed> $options
     */
    public static function fromBuyable(Buyable $item, array $options = []): self
    {
        return new self($item->getBuyableIdentifier($options), $item->getBuyableDescription($options), $item->getBuyablePrice($options), $item->getBuyableWeight($options), $options);
    }

    /**
     * Create a new instance from the given array.
     *
     * @param array<string, mixed> $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $options = Arr::get($attributes, 'options', []);

        return new self($attributes['id'], $attributes['name'], $attributes['price'], $attributes['weight'], $options);
    }

    /**
     * Create a new instance from the given attributes.
     *
     * @param array<string, mixed> $options
     */
    public static function fromAttributes(
        int | string | null $id,
        ?string $name,
        float | int | string | null $price,
        float | int | string | null $weight,
        array $options = [],
    ): self {
        return new self($id, $name, $price, $weight, $options);
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param array<string, mixed> $options
     */
    protected function generateRowId(int | string | null $id, array $options): string
    {
        ksort($options);

        return md5($id.serialize($options));
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rowId' => $this->rowId,
            'id' => $this->id,
            'name' => $this->name,
            'qty' => $this->qty,
            'price' => $this->price,
            'weight' => $this->weight,
            'options' => $this->options->toArray(),
            'discount' => $this->discount,
            'tax' => $this->tax,
            'subtotal' => $this->subtotal,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string|false
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the formatted number.
     */
    private function numberFormat(float | int | string | null $value, ?int $decimals, ?string $decimalPoint, ?string $thousandSeperator): string
    {
        return number_format(
            (float) $value,
            $decimals ?? config('cart.format.decimals', 2),
            $decimalPoint ?? config('cart.format.decimal_point', '.'),
            $thousandSeperator ?? config('cart.format.thousand_separator', ','),
        );
    }

    /**
     * Getter for the raw internal discount rate.
     * Should be used in calculators.
     */
    public function getDiscountRate(): float | int
    {
        return $this->discountRate;
    }

    /**
     * Convert a numeric value to an int or float.
     *
     * @throws \InvalidArgumentException
     */
    private static function toNumber(mixed $value, string $message): int | float
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException($message);
        }

        return $value + 0;
    }

    /**
     * Get an attribute from the cart item or get the associated model.
     *
     * @throws \Gloudemans\Shoppingcart\Exceptions\InvalidCalculatorException
     */
    public function __get(string $attribute): mixed
    {
        if (property_exists($this, $attribute)) {
            return $this->{$attribute};
        }
        $decimals = config('cart.format.decimals', 2);

        switch ($attribute) {
            case 'model':
                if (isset($this->associatedModel)) {
                    // Not method_exists(): Eloquent forwards find() to the query builder through __call()
                    $model = new $this->associatedModel();

                    return is_callable([$model, 'find']) ? $model->find($this->id) : null;
                }
                // no break
            case 'modelFQCN':
                if (isset($this->associatedModel)) {
                    return $this->associatedModel;
                }
                // no break
            case 'weightTotal':
                return round($this->weight * $this->qty, $decimals);
        }

        $calculator = config('cart.calculator', DefaultCalculator::class);
        if (! is_string($calculator) || ! is_subclass_of($calculator, Calculator::class)) {
            throw new InvalidCalculatorException('The configured Calculator seems to be invalid. Calculators have to implement the Calculator Contract.');
        }

        return $calculator::getAttribute($attribute, $this);
    }
}
