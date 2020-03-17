<?php

namespace GigaAI\LightKit;

class Setting
{
    use EasyCall;

    public $namespace;

    public $fillable = [];

    public $options = [];

    public function __construct($namespace = null)
    {
        $this->namespace = $namespace;
    }

    private function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    private function of($namespace)
    {
        return $this->setNamespace($namespace);
    }

    private function getAll()
    {
        if (empty($this->options)) {
            $this->options = get_option($this->namespace);
        }

        return $this->options;
    }

    private function get($field = null, $default = null)
    {
        $data = $this->getAll();

        if (is_string($field)) {
            return $data[$field] ?? $default;
        }

        if (is_array($field)) {
            $only = [];

            foreach ($field as $fieldName) {
                // if (array_key_exists($fieldName, $this->props)) {
                //     $only[$fieldName] = $data[$fieldName];
                // }
                $only[$fieldName] = $data[$fieldName];
            }

            return $only;
        }

        return $data;
    }

    private function set($field, $value = null)
    {
        $data = $this->getAll();

        if (is_string($field) && isset($value)) {
            $data[$field] = $value;
        }

        if (is_array($field)) {
            foreach ($field as $key => $value) {
                // if (array_key_exists($key, $this->props)) {
                //     $data[$key] = $value;
                // }

                $data[$key] = $value;
            }
        }

        update_option($this->namespace, $data);

        $this->options = $data;

        return $this;
    }
}
