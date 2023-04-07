<?php

namespace ProAI\Struct\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Str;
use ProAI\Struct\Struct;
use InvalidArgumentException;

class ComposedStructCast implements CastsAttributes
{
    /**
     * The struct class name.
     *
     * @var string
     */
    protected $class;

    /**
     * Create a new composed struct cast.
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
        $properties = [];

        foreach ($attributes as $name => $attribute) {
            if (is_null($attribute) || ! Str::startsWith($name, $key.'_')) {
                continue;
            }

            $properties[Str::after($name, $key.'_')] = $attribute;
        }

        // Assume that result is null when all properties are null.
        if (count($properties) === 0) {
            return null;
        }

        $class = $this->class;

        return new $class($class::parseDatabase($properties));
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

        if (is_null($value)) {
            $value = array_fill_keys($class::getPropertyNames(), null);
        } else {
            if (! $value instanceof Struct) {
                throw new InvalidArgumentException('The given value is not a struct instance, "'.get_class($value).'" given.');
            }

            $value = $class::serializeDatabase($value);
        }

        return $this->composeProperties($key, $value);
    }

    /**
     * Compose properties of struct.
     *
     * @param  array  $columns
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function composeProperties($prefix, $properties)
    {
        foreach ($properties as $name => $property) {
            $key = $prefix.'_'.$name;

            if (! is_array($property)) {
                $columns[$key] = $property;

                continue;
            }

            $columns = array_merge(
                $this->composeProperties($key, $property),
                $columns
            );
        }

        return $columns;
    }
}
