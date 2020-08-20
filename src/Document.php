<?php
/**
 * Created by PhpStorm.
 * User: AmorPro
 * Date: 20.08.2020
 * Time: 15:52
 */

namespace Selastic;


class Document
{
    private $hit = [];

    /**
     * Document constructor.
     * @param array $hit
     */
    public function __construct(array $hit)
    {
        $this->hit = $hit;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->hit['_id'];
    }

    /**
     * @param $columnName
     * @return mixed
     */
    public function get($columnName)
    {
        return $this->hit[$columnName] ?? null;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if(strpos($name, 'get') === 0){
            $name = lcfirst(substr($name, 3));

            return $this->get($name);
        }

        throw new \InvalidArgumentException('Method does not exists');
    }

}