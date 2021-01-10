<?php

namespace ProAI\Struct\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class CollectionCast implements CastsAttributes
{
    /**
     * The struct collection class name.
     *
     * @var string
     */
    protected $class;

    /**
     * Create a new collection cast.
     *
     * @param  string  $class
     * @return void
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * Transform the attribute from the underlying model values.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, $key, $value, $attributes)
    {
        if (is_null($value)) {
            return null;
        }

        $class = $this->class;
        $typeClass = (new $class)->getTypeClass();

        $items = array_map(function ($item) use ($typeClass) {
            return new $typeClass($typeClass::parseDatabase($item));
        }, json_decode($value, true));

        return new $class($items);
    }

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, $key, $value, $attributes)
    {
        $class = $this->class;

        if (! $value instanceof $class) {
            throw new InvalidArgumentException('The given value is not a struct collection instance, "'.get_class($value).'" given.');
        }

        $typeClass = $value->getTypeClass();

        $value = array_map(function ($item) use ($typeClass) {
            return $typeClass::serializeDatabase($item);
        }, $value->all());
        
        return json_encode($value);
    }
}
