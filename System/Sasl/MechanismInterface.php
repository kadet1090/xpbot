<?php
namespace XPBot\System\Sasl;

interface MechanismInterface {
    public function challenge($packet);
    public function get($jid, $password);
}