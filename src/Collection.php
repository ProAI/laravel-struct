<?php

namespace ProAI\Struct;

use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Contracts\Database\Eloquent\Castable;
use ProAI\Struct\Casts\CollectionCast;
use InvalidArgumentException;

abstract class Collection extends BaseCollection implements Castable
{
    /**
     * The struct class name.
     *
     * @var string
     */
    protected $type;

    /**
     * Create a new collection.
     *
     * @param  mixed  $items
     * @return void
     */
    public function __construct($items = [])
    {
        $class = $this->getTypeClass();

        foreach ($items as $item) {
            if ($item instanceof $class) {
                continue;
            }

            throw new InvalidArgumentException('All items must be an instance of "'.$class.'".');
        }

        parent::__construct($items);
    }

    /**
     * Run a map over each of the items.
     *
     * @param  callable  $callback
     * @return \Illuminate\Support\Collection|static
     */
    public function map(callable $callback)
    {
        $result = $this->toBase()->map($callback);

        $class = $this->getTypeClass();

        return $result->contains(function ($item) use ($class) {
            return ! $item instanceof $class;
        }) ? $result : new static($result->all());
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key / value pair.
     *
     * @param  callable  $callback
     * @return \Illuminate\Support\Collection|static
     */
    public function mapWithKeys(callable $callback)
    {
        $result = $this->toBase()->mapWithKeys($callback);

        $class = $this->getTypeClass();

        return $result->contains(function ($item) use ($class) {
            return ! $item instanceof $class;
        }) ? $result : new static($result->all());
    }

    /**
     * The following methods are intercepted to always return base collections.
     */

    /**
     * Get an array with the values of a given key.
     *
     * @param  string|array  $value
     * @param  string|null  $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($value, $key = null)
    {
        return $this->toBase()->pluck($value, $key);
    }

    /**
     * Get the keys of the collection items.
     *
     * @return \Illuminate\Support\Collection
     */
    public function keys()
    {
        return $this->toBase()->keys();
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * @param  mixed  ...$items
     * @return \Illuminate\Support\Collection
     */
    public function zip($items)
    {
        return $this->toBase()->zip(...func_get_args());
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collapse()
    {
        return $this->toBase()->collapse();
    }

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param  int  $depth
     * @return \Illuminate\Support\Collection
     */
    public function flatten($depth = INF)
    {
        return $this->toBase()->flatten($depth);
    }

    /**
     * Flip the items in the collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function flip()
    {
        return $this->toBase()->flip();
    }

    /**
     * Pad collection to the specified length with a value.
     *
     * @param  int  $size
     * @param  mixed  $value
     * @return \Illuminate\Support\Collection
     */
    public function pad($size, $value)
    {
        return $this->toBase()->pad($size, $value);
    }
    
    /**
     * Get the class name of the items.
     *
     * @return string
     */
    public function getTypeClass()
    {
        return $this->type;
    }
    
    /**
     * Get the name of the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return string
     */
    public static function castUsing(array $arguments)
    {
        return new CollectionCast(static::class);
    }
}
