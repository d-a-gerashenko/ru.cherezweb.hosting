<?php

namespace CherezWeb\Lib;

class SimpleStorage
{
    private $file;
    private $data;

    public function __construct($file)
    {
        $this->file = $file;
        $this->load();
    }

    /**
     * @param string $key
     * @param mixed $value serializable
     */
    public function set($key, $value)
    {
        if ($value === null) {
            unset($this->data[$key]);
        }
        $this->data[$key] = $value;
        $this->save();
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $this->load();
        if (key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        return null;
    }

    private function save()
    {
        file_put_contents($this->file, serialize($this->data));
        if (!file_exists($this->file)) {
            throw new \Exception(sprintf('Не хватает прав доступа к файлу %s.', $this->file));
        }
    }

    private function load()
    {
        if (file_exists($this->file)) {
            $unserialized = @unserialize(file_get_contents($this->file));
            if ($unserialized === false) {
                throw new \Exception('Неудается загрузить данные из файла.');
            }
            $this->data = $unserialized;
        } else {
            $this->data = array();
            $this->save();
        }

    }
}