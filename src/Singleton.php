<?php
namespace GigaAI\LightKit;

trait Singleton
{
    /**
     * @var mixed
     */
    private static $instance;
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }
    private function __construct()
    {}
    private function __clone()
    {}
    private function __wakeup()
    {}
}
