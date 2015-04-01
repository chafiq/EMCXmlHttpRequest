<?php

namespace EMC\XmlHttpRequestBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Description of EventInfo
 *
 * @author emc
 */
class EventInfo extends Event {
    /**
     * @var array
     */
    private $data;
    
    function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param type $data
     * @return \EMC\XmlHttpRequestBundle\Event\EventInfo
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }
}
