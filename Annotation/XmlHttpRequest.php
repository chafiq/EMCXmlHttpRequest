<?php

namespace EMC\XmlHttpRequestBundle\Annotation;

/**
 * XmlHttpRequest is an annotation.
 * It simplies the AJAX requests with enveloping and unifying responses.
 *
 * @author Chafiq El Mechrafi <chafiq.elmechrafi@gmail.com>
 * @Annotation
 */
class XmlHttpRequest {
	/**
	 * Response type. Default "json"
	 * @var string
	 */
    protected $type = 'json';
	
	/**
	 * Is streaming response or not. Default FALSE
	 * @var bool
	 */
    protected $streaming = false;


	/**
	 * XmlHttpRequest constructor.
	 * @param array $data	parameters that can be expected are :
	 *						type		=> string possible values ("json"),
	 *						streaming	=> bool
	 */
    function __construct(array $data) {
		if ( isset($data['type']) ) {
			$this->setType( $data['type'] );
		}
		
		if ( isset($data['streaming']) ) {
			$this->setStreaming($data['streaming']);
		}
    }
    
	/**
	 * returns the response type
	 * @return string
	 */
    public function getType() {
        return $this->type;
    }

	/**
	 * Set the response type
	 * @param string $type
	 * @return \EMC\XmlHttpRequestBundle\Annotation\XmlHttpRequest
	 */
	public function setType($type) {
        $this->type = $type;
		return $this;
    }

	/**
	 * Returns TRUE if the respoonse is streamed. FALSE otherwise.
	 * @return bool
	 */
    public function getStreaming() {
        return $this->streaming;
    }

	/**
	 * Set the response is streamed or not
	 * @param bool $streaming
	 * @return \EMC\XmlHttpRequestBundle\Annotation\XmlHttpRequest
	 */
    public function setStreaming($streaming) {
        $this->streaming = $streaming;
		return $this;
    }

}