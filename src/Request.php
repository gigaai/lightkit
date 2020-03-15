<?php

namespace GigaAI\LightKit;

use GigaAI\LightKit\EasyCall;
use GigaAI\LightKit\Singleton;

class Request
{
    use EasyCall, Singleton;

    /**
     * @var array
     */
    public $data = [];

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    private function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->all();
        }

        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    private function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->merge($key);

            return $this;
        }

        $this->data[$key] = $value;

        $this->$key = $value;

        return $this;
    }

    /**
     * @param $key
     */
    private function has($key = '')
    {
        return is_string($key) ? isset($this->data[$key]) : array_has($this->data, $key);
    }

    /**
     * @param array $data
     * @return mixed
     */
    private function merge(array $data)
    {
        $this->data = array_merge($this->data, $data);

        foreach ($this->data as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    private function all()
    {
        return $this->data;
    }

    /**
     * @param $key
     */
    private function filled($key)
    {
        return isset($this->data[$key]) && ! empty($this->data[$key]);
    }

    /**
     * @param $fields
     */
    private function only($fields)
    {
        return array_only($this->data, $fields);
    }

    /**
     * @param $fields
     */
    private function except($fields)
    {
        return array_except($this->data, $fields);
    }

    private function ajax()
    {
        return wp_doing_ajax();
    }

    /**
     * @param $key
     * @param $value
     */
    private function is($key, $value)
    {
        return trim($this->$key) === $value;
    }

    private function getMethod()
    {
        if ($this->filled('_method')) {
            return strtolower($this->get('_method'));
        }

        return  ! empty($_POST) ? 'post' : 'get';
    }

    /**
     * @param $method
     */
    private function isMethod($method)
    {
        $method        = is_string($method) ? strtolower($method) : $method;
        $currentMethod = $this->getMethod();

        return is_array($method) ? in_array($currentMethod, $method) : $method === $currentMethod;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }
}
