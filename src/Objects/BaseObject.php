<?php

namespace Telegram\Bot\Objects;

use Illuminate\Support\Collection;
use Telegram\Bot\Exceptions\TelegramUndefinedPropertyException;

/**
 * Class BaseObject
 *
 * @package Telegram\Bot\Objects
 */
abstract class BaseObject extends Collection
{
    /**
     * Builds collection entity.
     *
     * @param array|mixed $data
     */
    public function __construct($data)
    {
        parent::__construct($this->getRawResult($data));

        $this->mapRelatives();
    }

    /**
     * Property relations.
     *
     * @return array
     */
    abstract public function relations();

    /**
     * Get an item from the collection by key.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed|static
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return is_array($this->items[$key]) ? new static($this->items[$key]) : $this->items[$key];
        }

        return value($default);
    }

    /**
     * Map property relatives to appropriate objects.
     *
     * @return array|void
     */
    public function mapRelatives()
    {
        $relations = $this->relations();

        if (!$relations || !is_array($relations)) {
            return false;
        }

        $results = $this->all();
        foreach ($results as $key => $data) {
            foreach ($relations as $property => $class) {
                if (!is_object($data) && isset($results[$key][$property])) {
                    $results[$key][$property] = new $class($results[$key][$property]);
                }
            }
        }

        return $this->items = $results;
    }

    /**
     * Returns raw response.
     *
     * @return array|mixed
     */
    public function getRawResponse()
    {
        return $this->items;
    }

    /**
     * Returns raw result.
     *
     * @param $data
     *
     * @return mixed
     */
    public function getRawResult($data)
    {
        return array_get($data, 'result', $data);
    }

    /**
     * Get Status of request.
     *
     * @return mixed
     */
    public function getStatus()
    {
        return array_get($this->items, 'ok', false);
    }

    /**
     * Magic method to get properties dynamically.
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     *
     * @throws TelegramUndefinedPropertyException
     */
    public function __call($name, $arguments)
    {
        $action = substr($name, 0, 3);

        if ($action === 'get') {
            $property = snake_case(substr($name, 3));
            $response = $this->get($property, false);

            if ($response) {
                // Map relative property to an object
                $relations = $this->relations();
                if (isset($relations[$property])) {
                    return new $relations[$property]($response);
                }

                return $response;
            }

            throw new TelegramUndefinedPropertyException();
        }

        return false;
    }
}
