<?php
/**
 * Copyright 2014 Kadet <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 */
namespace Kadet\Utils;

trait Property {
    public function __get($name) {
        $reflection = new \ReflectionClass(get_called_class());
        try {
            $getter = '_get_'.$name;
            $method = $reflection->getMethod($getter);

            if($this->hasAccess($method, $this->getCaller()))
                return $this->$getter();
            else
                throw new \RuntimeException('Cannot access ' . ($method->isPrivate() ? 'private' : 'protected') . ' property ' . get_class($this) . '::$' . $name);
        } catch (\ReflectionException $exception) {
            if (method_exists($this, '_get'))
                return $this->_get($name);
            else
                throw new \RuntimeException('Trying to get non-existent property ' . get_class($this) . '::$' . $name);
        }
    }

    public function __set($name, $value) {
        $reflection = new \ReflectionClass(get_called_class());

        try {
            $setter = '_set_'.$name;
            $method = $reflection->getMethod($setter);

            if($this->hasAccess($method, $this->getCaller()))
                $this->$setter($value);
            else
                throw new \RuntimeException('Cannot access ' . ($method->isPrivate() ? 'private' : 'protected') . ' property ' . get_class($this) . '::$' . $name);
        } catch (\ReflectionException $exception) {
            if (method_exists($this, '_set'))
                $this->_set($name, $value);
            else
                $this->$name = $value;
        }
    }

    private function hasAccess(\ReflectionMethod $method, $caller) {
        return $method->isPublic() ||
        ($method->isProtected() && $caller == get_called_class()) ||
        ($method->isPrivate() && $caller == $method->getDeclaringClass()->getName());
    }

    private function getCaller() {
        $backtrace = debug_backtrace();

        return isset($backtrace[2]['class']) ?
            $backtrace[2]['class'] :
            null;
    }
}