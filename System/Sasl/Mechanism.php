<?php
namespace XPBot\System\Sasl;

use XPBot\System\Xmpp\Jid;

abstract class Mechanism implements MechanismInterface {
    /**
     * @var Jid
     */
    protected $jid;
    protected $password;

    public function __construct($jid, $password) {
        $this->jid      = $jid;
        $this->password = $password;
    }
}