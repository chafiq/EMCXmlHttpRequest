<?php

namespace EMC\XmlHttpRequestBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Description of StreamingProgress
 *
 * @author Chafiq El Mechrafi  <chafiq.elmechrafi@gmail.com>
 */
class StreamingProgress extends Event {
    
    /**
     * Progression level percent (%)
     * @var integer
     */
    protected $percent;
    
    /**
     * Message
     * @var string
     */
    protected $message;
    
    function __construct($percent, $message) {
        $this->setPercent($percent);
        $this->setMessage($message);
    }
    
    public function getPercent() {
        return $this->percent;
    }

    public function setPercent($percent) {
        $this->percent = $percent;
    }
    
    public function getMessage() {
        return $this->message;
    }

    public function setMessage($message) {
        $this->message = $message;
    }
    
}