<?php

namespace ProAI\Struct\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use ProAI\Struct\Struct;
use InvalidArgumentException;

class StructCast implements CastsAttributes
{
    /**
     * The struct collection class name.
     *
     * @var string
     */
    protected $class;

    /**
     * Create a new struct cast.
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

        $value = json_decode($value, true);

        $class = $this->class;

        return new $class($class::parseDatabase($value));
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
            throw new InvalidArgumentException('The given value is not a struct instance, "'.get_class($value).'" given.');
        }

        $value = $class::serializeDatabase($value);

        return json_encode($value);
    }
}
