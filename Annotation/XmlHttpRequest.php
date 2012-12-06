<?php

namespace EMC\XmlHttpRequestBundle\Annotation;

/**
 * Description of XmlHttpRequest
 *
 * @author Chafiq El Mechrafi <chafiq.elmechrafi@gmail.com>
 * @Annotation
 */
class XmlHttpRequest {
    protected $type;
    protected $streaming;


    function __construct(array $data) {
        $this->setType(isset($data['type']) ? $data['type'] : 'json');
        $this->setStreaming(isset($data['streaming']) && $data['streaming']);
    }
    
    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getStreaming() {
        return $this->streaming;
    }

    public function setStreaming($streaming) {
        $this->streaming = $streaming;
    }

}