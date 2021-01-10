<?php

namespace ProAI\Struct;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use ProAI\Struct\Casts\ComposedStructCast;
use ProAI\Struct\Casts\StructCast;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use ReflectionProperty;
use JsonSerializable;
use InvalidArgumentException;

abstract class Struct implements Arrayable, JsonSerializable, Castable
{
    /**
     * The reflection property instances.
     *
     * @var \ReflectionProperty[][]
     */
    protected static $cache = [];

    /**
     * Create a new struct.
     *
     * @param  array  $properties
     * @return void
     */
    public function __construct(array $properties = [])
    {
        $reflections = $this->getReflectionProperties();

        foreach ($reflections as $reflection) {
            $name = $reflection->getName();

            $value = $properties[$name] ?? $this->{$name} ?? null;

            $type = $reflection->getType();

            if (! $type->allowsNull() && is_null($value)) {
                throw new InvalidArgumentException('Property "'.$name.'" cannot be null.');
            }

            $class = $type->getName();

            if (is_array($value) && is_subclass_of($class, Collection::class)) {
                $value = $this->buildCollection($class, $value);
            } elseif (! is_null($value) && class_exists($class)) {
                $value = $this->buildInstance($class, $value);
            }

            $this->{$name} = $value;
        }
    }

    /**
     * Build struct collection.
     *
     * @param  string  $class
     * @param  array  $items
     * @return array
     */
    protected function buildCollection($class, array $items)
    {
        $typeClass = (new $class)->getTypeClass();

        $items = array_map(function ($item) use ($typeClass) {
            return $this->buildInstance($typeClass, $item);
        }, $items);

        return new $class($items);
    }

    /**
     * Build instance.
     *
     * @param  string  $class
     * @param  mixed  $value
     * @return array
     */
    protected function buildInstance($class, $value)
    {
        if ($value instanceof $class) {
            return $value;
        }

        return new $class($value);
    }

    /**
     * Get the properties.
     *
     * @return array
     */
    public function getProperties()
    {
        $reflections = $this->getReflectionProperties();

        $result = [];

        foreach($reflections as $reflection) {
            $name = $reflection->getName();

            $result[$name] = $this->{$name};
        }

        return $result;
    }

    /**
     * Get the properties as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->getProperties());
    }

    /**
     * Get the reflection property instances.
     *
     * @return \ReflectionProperty[]
     */
    protected function getReflectionProperties()
    {
        if (isset(static::$cache[static::class])) {
            return static::$cache[static::class];
        }

        $class = new ReflectionClass(static::class);

        $reflections = $class->getProperties(ReflectionProperty::IS_PUBLIC);

        return static::$cache[static::class] = array_filter(
            $reflections,
            function ($reflection) {
                return ! $reflection->isStatic();
            }
        );
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Cast values loaded from the database before constructing a struct from them.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function parseDatabase($value)
    {
        return $value;
    }

    /**
     * Transform value from the struct instance before it's persisted to the database.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function serializeDatabase($value)
    {
        return array_map(function ($property) {
            if ($property instanceof Struct) {
                return static::serializeDatabase($property);
            } elseif ($property instanceof Collection) {
                return array_map(function ($item) {
                    return static::serializeDatabase($item);
                }, $property->all());
            } elseif ($property instanceof Arrayable) {
                return $property->toArray();
            } else {
                return $property;
            }
        }, $value->getProperties());
    }

    /**
     * Get the name of the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return string
     */
    public static function castUsing(array $arguments)
    {
        if (in_array('composed', $arguments)) {
            return new ComposedStructCast(static::class);
        }
        
        return new StructCast(static::class);
    }
}
