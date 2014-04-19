<?php
/**
 * Copyright (C) 2014, Some right reserved.
 *
 * @author  Kacper "Kadet" Donat <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 *
 * Contact with author:
 * Xmpp: kadet@jid.pl
 * E-mail: kadet1090@gmail.com
 *
 * From Kadet with love.
 */

namespace XPBot\Config;

use Kadet\Utils\Event;
use Kadet\XmlSerializer\XmlDeserializer;
use Kadet\XmlSerializer\XmlSerializer;

class Config extends ConfigModule
{
    /**
     * @xml-skip
     * @var \Kadet\Utils\Event
     */
    public $onSave;

    /**
     * @xml-skip
     * @var string
     */
    private $_file;

    /**
     * @xml-skip
     * @var XmlSerializer
     */
    private $_serializer;

    /**
     * @xml-skip
     * @var XmlDeserializer
     */
    private $_deserializer;

    public function __construct($file)
    {
        $this->onSave = new Event();
        $this->_file  = $file;

        $this->_serializer      = new XmlSerializer();
        $this->_serializer->dao = 'XPBot\Config\ConfigModule';

        $this->_deserializer = new XmlDeserializer();

        $result          = $this->_deserializer->deserializeFile($this->_file);
        $this->_children = $result->_children;
        $this->_offsets  = $result->_offsets;

        if (!isset($this->storage))
            $this->storage = new SimpleConfig();
    }

    public function save()
    {
        $this->onSave->run($this);
        file_put_contents($this->_file, $this->_serializer->serialize($this));
    }
} 