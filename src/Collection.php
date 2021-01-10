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
