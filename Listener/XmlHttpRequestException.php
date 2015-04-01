<?php

namespace EMC\XmlHttpRequestBundle\Listener;

class XmlHttpRequestException extends \Exception implements XmlHttpRequestExceptionInterface {
    
    protected static $_message = '';
    protected static $_code = 0;
    
    /**
     * 
     * @param mixed $data
     */
    private $data;
    
    public function __construct($message=null, $code=0, $data = null) {
        parent::__construct( $message ? $message : static::$_message, $code ? $code : static::$_code);
        $this->data = $data;
    }
    
    public function setMessage($message) {
        $this->message = $message;
    }
    
    public function getData() {
        return $this->data;
    }
}
