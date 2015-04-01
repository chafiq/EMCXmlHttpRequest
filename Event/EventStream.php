<?php

namespace EMC\XmlHttpRequestBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * EventStream is an event who can be dispatch during an action who may take a long time.
 * It's binded during a XmlHttpRequest by adding the XmlHttpRequest annotation in the controller action.
 * Make sure that the parameter "streaming=true" is set in the annotation.
 *
 * @author Chafiq El Mechrafi  <chafiq.elmechrafi@gmail.com>
 */
class EventStream extends Event {
    
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
	
	/**
	 * Data content to stream
	 * @var mixed
	 */
	protected $data;

	/**
	 * @param int $percent
	 * @param string $message
	 * @param mixed $data
	 */
	function __construct(/*integer*/ $percent, /*string*/ $message='', $data=null) {
        $this->setPercent($percent);
        $this->setMessage($message);
		$this->setData($data);
    }
    
	/**
	 * Returns the progression value %
	 * @return int
	 */
    public function getPercent() {
        return $this->percent;
    }

	/**
	 * Set the progression value %
	 * @param int $percent
	 * @return \EMC\XmlHttpRequestBundle\Event\EventStream
	 */
    public function setPercent($percent) {
        $this->percent = $percent;
		return $this;
    }
    
	/**
	 * returns the related message for the progression
	 * @return string
	 */
    public function getMessage() {
        return $this->message;
    }

	/**
	 * Set the progression message
	 * @param string $message
	 * @return \EMC\XmlHttpRequestBundle\Event\EventStream
	 */
    public function setMessage($message) {
        $this->message = $message;
		return $this;
    }
    
	/**
	 * returns the data progression content
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Set the data progression content
	 * @param mixed $data
	 * @return \EMC\XmlHttpRequestBundle\Event\EventStream
	 */
	public function setData($data) {
		$this->data = $data;
		return $this;
	}

}