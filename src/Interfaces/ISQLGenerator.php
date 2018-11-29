<?php

namespace Tsukasa\QueryBuilder\Interfaces;

interface ISQLGenerator
{
    /**
     * @param $value
     * @return bool
     */
    public function hasLimit($value);

    /**
     * @param $value
     * @return bool
     */
    public function hasOffset($value);

    /**
     * @return string
     */
    public function getRandomOrder();

    /**
     * @param $value
     * @return string
     */
    public function getBoolean($value = null);

    /**
     * @param null $value
     * @return string
     */
    public function getDateTime($value = null);

    /**
     * @param null $value
     * @return string
     */
    public function getDate($value = null);

    /**
     * @param null $value
     * @return mixed
     */
    public function getTimestamp($value = null);
}