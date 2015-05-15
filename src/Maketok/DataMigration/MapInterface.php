<?php

namespace Maketok\DataMigration;

interface MapInterface
{
    /**
     * @param array $row
     */
    public function feed(array $row);

    /**
     * @param array $row
     * @return bool
     */
    public function isFresh(array $row);

    /**
     * clean data
     */
    public function clear();

    /**
     * attempt to increment and get result value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getIncrement($key, $default);
}
