<?php

namespace Gloudemans\Shoppingcart;

use Carbon\Carbon;
use Closure;
use Gloudemans\Shoppingcart\Contracts\Buyable;
use Gloudemans\Shoppingcart\Contracts\InstanceIdentifier;
use Gloudemans\Shoppingcart\Exceptions\CartAlreadyStoredException;
use Gloudemans\Shoppingcart\Exceptions\InvalidRowIDException;
use Gloudemans\Shoppingcart\Exceptions\UnknownModelException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;

/**
 * @property-read string $total
 * @property-read string $tax
 * @property-read string $subtotal
 */
class Cart
{
    use Macroable;
    public const string DEFAULT_INSTANCE = 'default';

    /**
     * Holds the current cart instance (prefixed with 'cart.').
     */
    private string $instance;

    /**
     * Holds the creation date of the cart.
     */
    private ?Carbon $createdAt = null;

    /**
     * Holds the update date of the cart.
     */
    private ?Carbon $updatedAt = null;

    /**
     * Defines the discount percentage.
     */
    private float | int $discount = 0;

    /**
     * Defines the tax rate.
     */
    private float | int $taxRate = 0;

    public function __construct(
        private SessionManager $session,
        private Dispatcher $events,
    ) {
        $this->taxRate = config('cart.tax') ?? 0;

        $this->instance(self::DEFAULT_INSTANCE);
    }

    /**
     * Set the current cart instance.
     */
    public function instance(InstanceIdentifier | string | null $instance = null): static
    {
        $instance = $instance ?: self::DEFAULT_INSTANCE;

        if ($instance instanceof InstanceIdentifier) {
            $this->discount = $instance->getInstanceGlobalDiscount();
            $instance = $instance->getInstanceIdentifier();
        }

        $this->instance = 'cart.'.$instance;

        return $this;
    }

    /**
     * Get the current cart instance.
     */
    public function currentInstance(): string
    {
        return str_replace('cart.', '', $this->instance);
    }

    /**
     * Add an item to the cart.
     *
     * When $id is a Buyable, $name is the quantity and $qty the options.
     *
     * @param \Gloudemans\Shoppingcart\Contracts\Buyable|array<mixed>|int|string|null $id
     * @param array<string, mixed> $options
     * @return \Gloudemans\Shoppingcart\CartItem|array<int, \Gloudemans\Shoppingcart\CartItem|array<mixed>>
     */
    public function add(
        Buyable | array | int | string | null $id,
        mixed $name = null,
        mixed $qty = null,
        float | int | string | null $price = null,
        float | int | string | null $weight = 0,
        array $options = [],
    ): CartItem | array {
        if (is_array($id) && $this->isMulti($id)) {
            return array_map(fn ($item) => $this->add($item), $id);
        }

        $cartItem = $this->createCartItem($id, $name, $qty, $price, $weight, $options);

        return $this->addCartItem($cartItem);
    }

    /**
     * Add an item to the cart.
     *
     * @param \Gloudemans\Shoppingcart\CartItem $item Item to add to the Cart
     * @param bool $keepDiscount Keep the discount rate of the Item
     * @param bool $keepTax Keep the Tax rate of the Item
     */
    public function addCartItem(CartItem $item, bool $keepDiscount = false, bool $keepTax = false, bool $dispatchEvent = true): CartItem
    {
        if (! $keepDiscount) {
            $item->setDiscountRate($this->discount);
        }

        if (! $keepTax) {
            $item->setTaxRate($this->taxRate);
        }

        $content = $this->getContent();

        $existingItem = $content->get($item->rowId);
        if ($existingItem !== null) {
            $item->qty += $existingItem->qty;
        }

        $content->put($item->rowId, $item);

        if ($dispatchEvent) {
            $this->events->dispatch('cart.adding', $item);
        }

        $this->session->put($this->instance, $content);

        if ($dispatchEvent) {
            $this->events->dispatch('cart.added', $item);
        }

        return $item;
    }

    /**
     * Update the cart item with the given rowId.
     *
     * Returns null when the item was removed because its quantity dropped to zero or below.
     *
     * @param \Gloudemans\Shoppingcart\Contracts\Buyable|array<string, mixed>|int|float $qty
     */
    public function update(string $rowId, Buyable | array | int | float $qty): ?CartItem
    {
        $cartItem = $this->get($rowId);

        if ($qty instanceof Buyable) {
            $cartItem->updateFromBuyable($qty);
        } elseif (is_array($qty)) {
            $cartItem->updateFromArray($qty);
        } else {
            $cartItem->qty = $qty;
        }

        $content = $this->getContent();
        $itemOldIndex = false;

        if ($rowId !== $cartItem->rowId) {
            $itemOldIndex = $content->keys()->search($rowId);

            $content->pull($rowId);

            if ($content->has($cartItem->rowId)) {
                $existingCartItem = $this->get($cartItem->rowId);
                $cartItem->setQuantity($existingCartItem->qty + $cartItem->qty);
            }
        }

        if ($cartItem->qty <= 0) {
            $this->remove($cartItem->rowId);

            return null;
        }

        if ($itemOldIndex !== false) {
            $content = $content->slice(0, $itemOldIndex)
                ->merge([$cartItem->rowId => $cartItem])
                ->merge($content->slice($itemOldIndex));
        } else {
            $content->put($cartItem->rowId, $cartItem);
        }

        $this->events->dispatch('cart.updating', $cartItem);

        $this->session->put($this->instance, $content);

        $this->events->dispatch('cart.updated', $cartItem);

        return $cartItem;
    }

    /**
     * Remove the cart item with the given rowId from the cart.
     */
    public function remove(string $rowId): void
    {
        $cartItem = $this->get($rowId);

        $content = $this->getContent();

        $content->pull($cartItem->rowId);

        $this->events->dispatch('cart.removing', $cartItem);

        $this->session->put($this->instance, $content);

        $this->events->dispatch('cart.removed', $cartItem);
    }

    /**
     * Get a cart item from the cart by its rowId.
     *
     * @throws \Gloudemans\Shoppingcart\Exceptions\InvalidRowIDException
     */
    public function get(string $rowId): CartItem
    {
        $cartItem = $this->getContent()->get($rowId);

        if ($cartItem === null) {
            throw new InvalidRowIDException("The cart does not contain rowId {$rowId}.");
        }

        return $cartItem;
    }

    /**
     * Destroy the current cart instance.
     */
    public function destroy(): void
    {
        $this->session->remove($this->instance);
    }

    /**
     * Get the content of the cart.
     *
     * @return \Illuminate\Support\Collection<string, \Gloudemans\Shoppingcart\CartItem>
     */
    public function content(): Collection
    {
        if (is_null($this->session->get($this->instance))) {
            return new Collection([]);
        }

        return $this->session->get($this->instance);
    }

    /**
     * Get the total quantity of all CartItems in the cart.
     */
    public function count(): int | float
    {
        return $this->getContent()->sum('qty');
    }

    /**
     * Get the amount of CartItems in the Cart.
     * Keep in mind that this does NOT count quantity.
     */
    public function countItems(): int
    {
        return $this->getContent()->count();
    }

    /**
     * Get the total price of the items in the cart.
     */
    public function totalFloat(): float
    {
        return $this->getContent()->reduce(fn ($total, CartItem $cartItem) => $total + $cartItem->total, 0);
    }

    /**
     * Get the total price of the items in the cart as formatted string.
     */
    public function total(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->totalFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the total tax of the items in the cart.
     */
    public function taxFloat(): float
    {
        return $this->getContent()->reduce(fn ($tax, CartItem $cartItem) => $tax + $cartItem->taxTotal, 0);
    }

    /**
     * Get the total tax of the items in the cart as formatted string.
     */
    public function tax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->taxFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the subtotal (total - tax) of the items in the cart.
     */
    public function subtotalFloat(): float
    {
        return $this->getContent()->reduce(fn ($subTotal, CartItem $cartItem) => $subTotal + $cartItem->subtotal, 0);
    }

    /**
     * Get the subtotal (total - tax) of the items in the cart as formatted string.
     */
    public function subtotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->subtotalFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the discount of the items in the cart.
     */
    public function discountFloat(): float
    {
        return $this->getContent()->reduce(fn ($discount, CartItem $cartItem) => $discount + $cartItem->discountTotal, 0);
    }

    /**
     * Get the discount of the items in the cart as formatted string.
     */
    public function discount(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->discountFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the price of the items in the cart (not rounded).
     */
    public function initialFloat(): float
    {
        return $this->getContent()->reduce(fn ($initial, CartItem $cartItem) => $initial + ($cartItem->qty * $cartItem->price), 0);
    }

    /**
     * Get the price of the items in the cart as formatted string.
     */
    public function initial(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->initialFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the price of the items in the cart (previously rounded).
     */
    public function priceTotalFloat(): float
    {
        return $this->getContent()->reduce(fn ($initial, CartItem $cartItem) => $initial + $cartItem->priceTotal, 0);
    }

    /**
     * Get the price of the items in the cart as formatted string.
     */
    public function priceTotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->priceTotalFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the total weight of the items in the cart.
     */
    public function weightFloat(): float
    {
        return $this->getContent()->reduce(fn ($total, CartItem $cartItem) => $total + ($cartItem->qty * $cartItem->weight), 0);
    }

    /**
     * Get the total weight of the items in the cart.
     */
    public function weight(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->weightFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Search the cart content for a cart item matching the given search closure.
     *
     * @return \Illuminate\Support\Collection<string, \Gloudemans\Shoppingcart\CartItem>
     */
    public function search(Closure $search): Collection
    {
        return $this->getContent()->filter($search);
    }

    /**
     * Associate the cart item with the given rowId with the given model.
     *
     * @throws \Gloudemans\Shoppingcart\Exceptions\UnknownModelException
     */
    public function associate(string $rowId, object | string $model): void
    {
        if (is_string($model) && ! class_exists($model)) {
            throw new UnknownModelException("The supplied model {$model} does not exist.");
        }

        $cartItem = $this->get($rowId);

        $cartItem->associate($model);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->session->put($this->instance, $content);
    }

    /**
     * Set the tax rate for the cart item with the given rowId.
     */
    public function setTax(string $rowId, float | int | null $taxRate): void
    {
        $cartItem = $this->get($rowId);

        $cartItem->setTaxRate($taxRate);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->session->put($this->instance, $content);
    }

    /**
     * Set the global tax rate for the cart.
     * This will set the tax rate for all items.
     */
    public function setGlobalTax(float | int $taxRate): void
    {
        $this->taxRate = $taxRate;

        $content = $this->getContent();
        if ($content->count()) {
            $content->each(function (CartItem $item) {
                $item->setTaxRate($this->taxRate);
            });
        }
    }

    /**
     * Set the discount rate for the cart item with the given rowId.
     */
    public function setDiscount(string $rowId, float | int | null $discount): void
    {
        $cartItem = $this->get($rowId);

        $cartItem->setDiscountRate($discount);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->session->put($this->instance, $content);
    }

    /**
     * Set the global discount percentage for the cart.
     * This will set the discount for all cart items.
     */
    public function setGlobalDiscount(float | int $discount): void
    {
        $this->discount = $discount;

        $content = $this->getContent();
        if ($content->count()) {
            $content->each(function (CartItem $item) {
                $item->setDiscountRate($this->discount);
            });
        }
    }

    /**
     * Store the current instance of the cart.
     *
     * @throws \Gloudemans\Shoppingcart\Exceptions\CartAlreadyStoredException
     */
    public function store(InstanceIdentifier | int | string $identifier): void
    {
        $content = $this->getContent();

        if ($identifier instanceof InstanceIdentifier) {
            $identifier = $identifier->getInstanceIdentifier();
        }

        $instance = $this->currentInstance();

        if ($this->storedCartInstanceWithIdentifierExists($instance, $identifier)) {
            throw new CartAlreadyStoredException("A cart with identifier {$identifier} was already stored.");
        }

        if ($this->getConnection()->getDriverName() === 'pgsql') {
            $serializedContent = base64_encode(serialize($content));
        } else {
            $serializedContent = serialize($content);
        }

        $this->getConnection()->table($this->getTableName())->insert([
            'identifier' => $identifier,
            'instance' => $instance,
            'content' => $serializedContent,
            'created_at' => $this->createdAt ?: Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->events->dispatch('cart.stored');
    }

    /**
     * Restore the cart with the given identifier.
     */
    public function restore(InstanceIdentifier | int | string $identifier): void
    {
        if ($identifier instanceof InstanceIdentifier) {
            $identifier = $identifier->getInstanceIdentifier();
        }

        $currentInstance = $this->currentInstance();

        if (! $this->storedCartInstanceWithIdentifierExists($currentInstance, $identifier)) {
            return;
        }

        $stored = $this->getConnection()->table($this->getTableName())
            ->where(['identifier' => $identifier, 'instance' => $currentInstance])->first();

        if ($this->getConnection()->getDriverName() === 'pgsql') {
            $storedContent = unserialize(base64_decode(data_get($stored, 'content')));
        } else {
            $storedContent = unserialize(data_get($stored, 'content'));
        }

        $this->instance(data_get($stored, 'instance'));

        $content = $this->getContent();

        foreach ($storedContent as $cartItem) {
            $content->put($cartItem->rowId, $cartItem);
        }

        $this->events->dispatch('cart.restored');

        $this->session->put($this->instance, $content);

        $this->instance($currentInstance);

        $this->createdAt = Carbon::parse(data_get($stored, 'created_at'));
        $this->updatedAt = Carbon::parse(data_get($stored, 'updated_at'));

        $this->getConnection()->table($this->getTableName())->where(['identifier' => $identifier, 'instance' => $currentInstance])->delete();
    }

    /**
     * Erase the cart with the given identifier.
     */
    public function erase(InstanceIdentifier | int | string $identifier): void
    {
        if ($identifier instanceof InstanceIdentifier) {
            $identifier = $identifier->getInstanceIdentifier();
        }

        $instance = $this->currentInstance();

        if (! $this->storedCartInstanceWithIdentifierExists($instance, $identifier)) {
            return;
        }

        $this->getConnection()->table($this->getTableName())->where(['identifier' => $identifier, 'instance' => $instance])->delete();

        $this->events->dispatch('cart.erased');
    }

    /**
     * Merges the contents of another cart into this cart.
     *
     * @param int|string $identifier Identifier of the Cart to merge with.
     * @param bool $keepDiscount Keep the discount of the CartItems.
     * @param bool $keepTax Keep the tax of the CartItems.
     * @param bool $dispatchAdd Flag to dispatch the add events.
     */
    public function merge(int | string $identifier, bool $keepDiscount = false, bool $keepTax = false, bool $dispatchAdd = true, string $instance = self::DEFAULT_INSTANCE): bool
    {
        $stored = $this->getConnection()->table($this->getTableName())
            ->where(['identifier' => $identifier, 'instance' => $instance])->first();

        if ($stored === null) {
            return false;
        }

        if ($this->getConnection()->getDriverName() === 'pgsql') {
            $storedContent = unserialize(base64_decode($stored->content));
        } else {
            $storedContent = unserialize($stored->content);
        }

        foreach ($storedContent as $cartItem) {
            $this->addCartItem($cartItem, $keepDiscount, $keepTax, $dispatchAdd);
        }

        $this->events->dispatch('cart.merged');

        return true;
    }

    /**
     * Get the carts content, if there is no cart content set yet, return a new empty Collection.
     *
     * @return \Illuminate\Support\Collection<string, \Gloudemans\Shoppingcart\CartItem>
     */
    protected function getContent(): Collection
    {
        if ($this->session->has($this->instance)) {
            return $this->session->get($this->instance);
        }

        return new Collection();
    }

    /**
     * Create a new CartItem from the supplied attributes.
     *
     * @param \Gloudemans\Shoppingcart\Contracts\Buyable|array<string, mixed>|int|string|null $id
     * @param array<string, mixed> $options
     */
    private function createCartItem(
        Buyable | array | int | string | null $id,
        mixed $name,
        mixed $qty,
        float | int | string | null $price,
        float | int | string | null $weight,
        array $options,
    ): CartItem {
        if ($id instanceof Buyable) {
            $cartItem = CartItem::fromBuyable($id, $qty ?: []);
            $cartItem->setQuantity($name ?: 1);
            $cartItem->associate($id);
        } elseif (is_array($id)) {
            $cartItem = CartItem::fromArray($id);
            $cartItem->setQuantity($id['qty']);
        } else {
            $cartItem = CartItem::fromAttributes($id, $name, $price, $weight, $options);
            $cartItem->setQuantity($qty);
        }

        $cartItem->setInstance($this->currentInstance());

        return $cartItem;
    }

    /**
     * Check if the item is a multidimensional array or an array of Buyables.
     */
    private function isMulti(mixed $item): bool
    {
        if (! is_array($item)) {
            return false;
        }

        return is_array(head($item)) || head($item) instanceof Buyable;
    }

    private function storedCartInstanceWithIdentifierExists(string $instance, int | string $identifier): bool
    {
        return $this->getConnection()->table($this->getTableName())->where(['identifier' => $identifier, 'instance' => $instance])->exists();
    }

    /**
     * Get the database connection.
     */
    private function getConnection(): Connection
    {
        return app(DatabaseManager::class)->connection($this->getConnectionName());
    }

    /**
     * Get the database table name.
     */
    private function getTableName(): string
    {
        return config('cart.database.table', 'shoppingcart');
    }

    /**
     * Get the database connection name.
     */
    private function getConnectionName(): ?string
    {
        $connection = config('cart.database.connection');

        return is_null($connection) ? config('database.default') : $connection;
    }

    /**
     * Get the Formatted number.
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
     * Get the creation date of the cart (db context).
     */
    public function createdAt(): ?Carbon
    {
        return $this->createdAt;
    }

    /**
     * Get the last update date of the cart (db context).
     */
    public function updatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    /**
     * Magic method to make accessing the total, tax and subtotal properties possible.
     */
    public function __get(string $attribute): ?string
    {
        return match ($attribute) {
            'total' => $this->total(),
            'tax' => $this->tax(),
            'subtotal' => $this->subtotal(),
            default => null,
        };
    }
}
