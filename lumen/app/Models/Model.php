<?php

namespace App\Models;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

abstract class Model implements Arrayable, Jsonable
{
    /**
     * Class factory.
     *
     * NOTE This function uses the Variable-length argument lists syntax, introduced in PHP 5.6
     *
     * @see http://php.net/manual/en/functions.arguments.php#functions.variable-arg-list
     * @param  mixed ...$attributes
     * @return self
     */
    public static function make(...$attributes): self
    {
        return new static(...$attributes);
    }

    /**
     * Model constructor.
     *
     * @param  array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        if ($attributes) {
            $this->set($attributes);
        }
    }

    /**
     * Set model attributes that have a setter method.
     *
     * Array keys can be in snake_case, camelCase or StudlyCase, it doesn't matter.
     *
     * @param  array $attributes
     * @return self
     */
    public function set(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $method = 'set' . studly_case($key);

            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }

        return $this;
    }

    /**
     * Convert the model to its array representation.
     *
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * Convert the model to its JSON representation.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
