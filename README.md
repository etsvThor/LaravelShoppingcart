# LaravelShoppingcart

A simple shopping cart for Laravel, backed by the session with optional database persistence.

This is the [E.T.S.V. Thor](https://github.com/etsvThor) fork of [bumbummen99/LaravelShoppingcart](https://github.com/bumbummen99/LaravelShoppingcart), which itself is a fork of [Crinsane/LaravelShoppingcart](https://github.com/Crinsane/LaravelShoppingcart). Upstream is no longer maintained; this fork is kept up to date for our own projects.

## Requirements

- PHP 8.5+
- Laravel 12 or 13

## Installation

The package is not published on Packagist, so add this repository to your `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:etsvThor/LaravelShoppingcart.git"
    }
]
```

Then require it:

    composer require bumbummen99/shoppingcart

The service provider and the `Cart` facade are registered automatically through package discovery. You can also inject `Gloudemans\Shoppingcart\Cart` through the container instead of using the facade.

Publish the config file to change the defaults:

    php artisan vendor:publish --provider="Gloudemans\Shoppingcart\ShoppingcartServiceProvider" --tag="config"

## Upgrading

The public API is now fully typed. When upgrading from an older version:

- Classes implementing `Buyable` must declare return types: `getBuyableIdentifier(): int|string`, `getBuyableDescription(): string`, `getBuyablePrice(): float` and `getBuyableWeight(): float`.
- Classes implementing `InstanceIdentifier` must declare `getInstanceIdentifier(): int|string` and `getInstanceGlobalDiscount(): float|int`.
- Custom calculators must return `float|int|null` (or something narrower, such as `?float`) from `getAttribute()`.
- `Cart::merge()` only accepts booleans for its flags.
- The `CanBeBought` trait now returns `0.0` for a missing price instead of `null`, so items with price `0` can be added. It requires an Eloquent model and uses its primary key as identifier.
- `Cart::update()` with an array validates `qty`, `price` and `weight` and stores them as numbers; a non-numeric value throws an `InvalidArgumentException`.
- Tax and discount rates set to `null` are stored as `0`.

When used with PostgreSQL, the cart content is base64 encoded before it is stored (since **4.2.0**). Clear your cart table when upgrading a PostgreSQL setup from a version below 4.2.0.

## Table of Contents

* [Rounding](#rounding)
* [Usage](#usage)
* [Buyable](#buyable)
* [Collections](#collections)
* [Instances](#instances)
* [Models](#models)
* [Database](#database)
* [Calculators](#calculators)
* [Exceptions](#exceptions)
* [Events](#events)
* [Example](#example)
* [Development](#development)

## Rounding

Like any cart that calculates prices including taxes and discounts, this package can be affected by the [totals rounding issue](https://stackoverflow.com/questions/13529580/magento-tax-rounding-issue). To minimize it, totals are calculated **per row** and rounded using the default number format from `config/cart.php`.

Because of this, **do not set a high precision as the default and then format the output with fewer decimals**, as that can reintroduce the rounding issue. The base (product) price itself is never rounded.

## Usage

### Cart::add()

In its most basic form you specify the id, name, quantity, price and weight of the product:

```php
Cart::add('293ad', 'Product 1', 1, 9.99, 550);
```

An optional sixth parameter takes options, so you can add items with the same id but, for instance, a different size:

```php
Cart::add('293ad', 'Product 1', 1, 9.99, 550, ['size' => 'large']);
```

You can also pass an array. The `options` key is optional:

```php
Cart::add(['id' => '293ad', 'name' => 'Product 1', 'qty' => 1, 'price' => 9.99, 'weight' => 550, 'options' => ['size' => 'large']]);
```

If your model implements the [Buyable](#buyable) interface you can pass the model, the quantity and optionally the options. The model is automatically [associated](#models) with the cart item:

```php
Cart::add($product, 1, ['size' => 'large']);
```

Finally, you can add multiple items at once by passing an array of arrays or an array of Buyables:

```php
Cart::add([
    ['id' => '293ad', 'name' => 'Product 1', 'qty' => 1, 'price' => 10.00, 'weight' => 550],
    ['id' => '4832k', 'name' => 'Product 2', 'qty' => 1, 'price' => 10.00, 'weight' => 550, 'options' => ['size' => 'large']],
]);

Cart::add([$product1, $product2]);
```

`add()` returns the `CartItem` that was added, or an array of `CartItem`s when adding multiple items.

### Cart::update()

To update an item you need its `rowId`. Pass a new quantity:

```php
Cart::update($rowId, 2);
```

Or an array of attributes to change, or a `Buyable` to update the id, name and price from:

```php
Cart::update($rowId, ['name' => 'Product 1']);
Cart::update($rowId, ['options' => ['size' => 'small']]);
Cart::update($rowId, $product);
```

`update()` returns the updated `CartItem`. When the quantity drops to zero or below, the item is removed and `null` is returned.

### Cart::remove()

```php
Cart::remove($rowId);
```

### Cart::get()

```php
Cart::get($rowId);
```

Throws an `InvalidRowIDException` when the cart does not contain the given `rowId`.

### Cart::content()

Returns a [Collection](#collections) of `CartItem`s for the current instance:

```php
Cart::content();
Cart::instance('wishlist')->content();
```

### Cart::destroy()

Removes all items from the current cart instance:

```php
Cart::destroy();
```

### Totals

The following methods return a formatted string. All of them accept the optional parameters `$decimals`, `$decimalPoint` and `$thousandSeparator`, which default to the values in the config file.

| Method                 | Returns                                                         |
| ---------------------- | --------------------------------------------------------------- |
| `Cart::total()`        | Total of all items, including tax                               |
| `Cart::subtotal()`     | Total of all items, excluding tax                               |
| `Cart::tax()`          | Total tax of all items                                          |
| `Cart::discount()`     | Total discount of all items                                     |
| `Cart::priceTotal()`   | Total price of all items before discount and tax (rounded)      |
| `Cart::initial()`      | Total price of all items before discount and tax (not rounded)  |
| `Cart::weight()`       | Total weight of all items                                       |

```php
Cart::total();
Cart::total(2, ',', '.');
```

Each method has a `*Float()` variant returning the raw `float`, e.g. `Cart::totalFloat()`, `Cart::subtotalFloat()` and `Cart::taxFloat()`.

`total`, `subtotal` and `tax` are also available as properties on a cart instance, e.g. `$cart->total`.

### Cart::count()

Returns the total quantity of all items. If you've added 2 books and 1 shirt, it returns 3. Use `Cart::countItems()` for the number of rows instead.

```php
Cart::count();
```

### Cart::search()

Filters the cart content with a closure, using the `filter()` method of Laravel's Collection. The closure receives the `CartItem` and its `rowId`, and a Collection of all matching items is returned:

```php
Cart::search(function (CartItem $cartItem, string $rowId) {
    return $cartItem->id === 1;
});
```

### Tax and discount

```php
// Tax rate for a single item, overriding the config value
Cart::setTax($rowId, 21);

// Tax rate for all items, including items added later
Cart::setGlobalTax(21);

// Discount percentage for a single item
Cart::setDiscount($rowId, 10);

// Discount percentage for all items, including items added later
Cart::setGlobalDiscount(50);
```

Setting the global discount overrides any discount set on individual items.

## Buyable

To add models to the cart directly and have them associated automatically, implement the `Buyable` interface. The `CanBeBought` trait implements it using the primary key, `name`/`title`/`description`, `price` and `weight` attributes of your model:

```php
use Gloudemans\Shoppingcart\CanBeBought;
use Gloudemans\Shoppingcart\Contracts\Buyable;
use Illuminate\Database\Eloquent\Model;

class Product extends Model implements Buyable
{
    use CanBeBought;
}
```

To map the fields yourself, implement the methods directly. `$options` contains the options of the cart item:

```php
use Gloudemans\Shoppingcart\CartItemOptions;
use Gloudemans\Shoppingcart\Contracts\Buyable;
use Illuminate\Database\Eloquent\Model;

class Product extends Model implements Buyable
{
    public function getBuyableIdentifier(CartItemOptions|array|null $options = null): int|string
    {
        return $this->id;
    }

    public function getBuyableDescription(CartItemOptions|array|null $options = null): string
    {
        return $this->name;
    }

    public function getBuyablePrice(CartItemOptions|array|null $options = null): float
    {
        return $this->price;
    }

    public function getBuyableWeight(CartItemOptions|array|null $options = null): float
    {
        return $this->weight;
    }
}
```

## Collections

`content()` and `search()` return a regular Laravel Collection, so all Collection methods are available:

```php
Cart::content()->count();
Cart::content()->groupBy('id');
```

## Instances

The package supports multiple cart instances. Call `Cart::instance('name')` to switch; all following calls operate on that instance until you switch again.

```php
Cart::instance('shopping')->add('192ao12', 'Product 1', 1, 9.99, 550);

// Content of the 'shopping' cart
Cart::content();

Cart::instance('wishlist')->add('sdjk922', 'Product 2', 1, 19.95, 550, ['size' => 'medium']);

// Content of the 'wishlist' cart
Cart::content();

// Back to the 'shopping' cart
Cart::instance('shopping')->content();
```

The default instance is called `default`, so `Cart::content()` is the same as `Cart::instance('default')->content()` when you don't use instances.

You can also implement the `InstanceIdentifier` contract, for example on your `User` model, to use it as the instance. This also sets the global discount of the cart:

```php
use Gloudemans\Shoppingcart\Contracts\InstanceIdentifier;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements InstanceIdentifier
{
    public function getInstanceIdentifier(mixed $options = null): int|string
    {
        return $this->email;
    }

    public function getInstanceGlobalDiscount(mixed $options = null): float|int
    {
        return $this->discount_rate ?? 0;
    }
}

$cart = Cart::instance(auth()->user());
```

## Models

You can associate a model with a cart item and access it through the `model` property. Items added through a [Buyable](#buyable) are associated automatically.

```php
$cartItem = Cart::add('293ad', 'Product 1', 1, 9.99, 550, ['size' => 'large']);

Cart::associate($cartItem->rowId, Product::class);

// Or directly on the CartItem
$cartItem->associate(Product::class);

foreach (Cart::content() as $row) {
    echo "You have {$row->qty} items of {$row->model->name} in your cart.";
}
```

## Database

### Configuration

By default the cart is stored using the default database connection in a table named `shoppingcart`. Both can be changed in the config file. Publish and run the migration to create the table:

    php artisan vendor:publish --provider="Gloudemans\Shoppingcart\ShoppingcartServiceProvider" --tag="migrations"
    php artisan migrate

### Storing the cart

Store the current instance under an identifier, for instance the id of the user. A `CartAlreadyStoredException` is thrown if a cart was already stored with that identifier.

```php
Cart::store('username');
Cart::instance('wishlist')->store('username');
```

### Restoring the cart

Restoring loads the stored items into the current instance and removes the stored cart from the database:

```php
Cart::restore('username');
Cart::instance('wishlist')->restore('username');
```

### Merging the cart

Merge a stored cart into the current instance. You can choose to keep the discount and tax rates of the stored items, whether `cart.added` events should be dispatched, and which stored instance to merge:

```php
Cart::instance('username')->merge('savedcart', keepDiscount: false, keepTax: false, dispatchAdd: true, instance: 'savedcartinstance');
```

### Erasing the cart

```php
Cart::erase('username');
Cart::instance('wishlist')->erase('username');
```

## Calculators

Prices, taxes and discounts are calculated by a class implementing `Gloudemans\Shoppingcart\Contracts\Calculator`, configured through `cart.calculator`. Two calculators are included:

- `DefaultCalculator`: the item price excludes tax.
- `GrossPrice`: the item price includes tax.

Custom attributes returned by your calculator are available as properties on the `CartItem`. This is the default calculator:

```php
use Gloudemans\Shoppingcart\CartItem;
use Gloudemans\Shoppingcart\Contracts\Calculator;

class DefaultCalculator implements Calculator
{
    public static function getAttribute(string $attribute, CartItem $cartItem): float|int|null
    {
        $decimals = config('cart.format.decimals', 2);

        return match ($attribute) {
            'discount' => $cartItem->price * ($cartItem->getDiscountRate() / 100),
            'tax' => round($cartItem->priceTarget * ($cartItem->taxRate / 100), $decimals),
            'priceTax' => round($cartItem->priceTarget + $cartItem->tax, $decimals),
            'discountTotal' => round($cartItem->discount * $cartItem->qty, $decimals),
            'priceTotal' => round($cartItem->price * $cartItem->qty, $decimals),
            'subtotal' => max(round($cartItem->priceTotal - $cartItem->discountTotal, $decimals), 0),
            'priceTarget' => round(($cartItem->priceTotal - $cartItem->discountTotal) / $cartItem->qty, $decimals),
            'taxTotal' => round($cartItem->subtotal * ($cartItem->taxRate / 100), $decimals),
            'total' => round($cartItem->subtotal + $cartItem->taxTotal, $decimals),
            default => null,
        };
    }
}
```

## Exceptions

| Exception                    | Reason                                                                             |
| ---------------------------- | ---------------------------------------------------------------------------------- |
| `CartAlreadyStoredException` | When trying to store a cart that was already stored using the specified identifier |
| `InvalidRowIDException`      | When the given rowId doesn't exist in the current cart instance                    |
| `UnknownModelException`      | When associating a model class that doesn't exist                                  |
| `InvalidCalculatorException` | When the configured calculator doesn't implement the `Calculator` contract         |

Invalid item data (such as an empty name or a non-numeric price) throws an `InvalidArgumentException`.

## Events

| Event         | Fired                                    | Parameter                             |
| ------------- | ---------------------------------------- | ------------------------------------- |
| cart.adding   | When adding an item to the cart.         | The `CartItem` that is being added.   |
| cart.added    | When an item was added to the cart.      | The `CartItem` that was added.        |
| cart.updating | When updating an item in the cart.       | The `CartItem` that is being updated. |
| cart.updated  | When an item was updated in the cart.    | The `CartItem` that was updated.      |
| cart.removing | When removing an item from the cart.     | The `CartItem` that is being removed. |
| cart.removed  | When an item was removed from the cart.  | The `CartItem` that was removed.      |
| cart.merged   | When the content of a cart was merged.   | -                                     |
| cart.stored   | When the content of a cart was stored.   | -                                     |
| cart.restored | When the content of a cart was restored. | -                                     |
| cart.erased   | When the content of a cart was erased.   | -                                     |

## Example

```php
// In your controller
Cart::add('192ao12', 'Product 1', 1, 9.99);
Cart::add('1239ad0', 'Product 2', 2, 5.95, 0, ['size' => 'large']);
```

```blade
<table>
    <thead>
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach (Cart::content() as $row)
            <tr>
                <td>
                    <p><strong>{{ $row->name }}</strong></p>
                    <p>{{ $row->options->get('size') }}</p>
                </td>
                <td>{{ $row->qty }}</td>
                <td>{{ $row->price() }}</td>
                <td>{{ $row->total() }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">Subtotal</td>
            <td>{{ Cart::subtotal() }}</td>
        </tr>
        <tr>
            <td colspan="3">Tax</td>
            <td>{{ Cart::tax() }}</td>
        </tr>
        <tr>
            <td colspan="3">Total</td>
            <td>{{ Cart::total() }}</td>
        </tr>
    </tfoot>
</table>
```

## Development

The repository includes a [Sail](https://laravel.com/docs/sail) setup for running the tests and static analysis:

    cp .env.example .env
    composer install
    vendor/bin/sail up -d
    vendor/bin/sail php vendor/bin/phpunit
    vendor/bin/sail php vendor/bin/phpstan analyse
    vendor/bin/sail php vendor/bin/php-cs-fixer fix

## Credits

Originally created by [Rob Gloudemans](https://github.com/Crinsane) and maintained by [Patrick Henninger](https://github.com/bumbummen99), with help from [many contributors](https://github.com/bumbummen99/LaravelShoppingcart/graphs/contributors).

## License

MIT, see [LICENSE](LICENSE).
