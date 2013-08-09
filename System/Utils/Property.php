<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kacper
 * Date: 08.08.13
 * Time: 22:44
 * To change this template use File | Settings | File Templates.
 */

namespace XPBot\System\Utils;

trait Property {
    public function __get($name) {
        $reflection = new \ReflectionClass(get_called_class());
        try {
            $getter = '_get_'.$name;
            $method = $reflection->getMethod($getter);

            if($this->hasAccess($method, $this->getCaller()))
                return $this->$getter();
            else
                throw new \RuntimeException('Nie masz dostępu do pobierania tej właściwości.');
        } catch (\ReflectionException $exception) {
            throw new \RuntimeException('Ta właściwość nie może być pobrana.');
        }
    }

    public function __set($name, $value) {
        $reflection = new \ReflectionClass(get_called_class());

        try {
            $setter = '_set_'.$name;
            $method = $reflection->getMethod($setter);

            if($this->hasAccess($method, $this->getCaller()))
                return $this->$setter($value);
            else
                throw new \RuntimeException('Nie masz dostępu do ustawiania tej właściwości.');
        } catch (\ReflectionException $exception) {
            throw new \RuntimeException('Ta właściwość nie może być ustawiona.');
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