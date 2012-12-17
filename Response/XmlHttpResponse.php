<?php

namespace EMC\XmlHttpRequestBundle\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * XmlHttpResponse is a related class to the XmlHttpRequest.
 * It simplify the multi type response ("json", "xml", "text", "csv", ...)
 * It permit to use mixed response content and stream data with flushing output buffer.
 *
 * @author Chafiq El Mechrafi <chafiq.elmechrafi@gmail.com>
 */
class XmlHttpResponse extends Response {
    
    /**
     * response type
     * @var integer
     */
    protected $type;
    
    /**
     * streaming response
     * @var bool
     */
    protected $streaming;
    
	/**
	 * XmlHttpResponse constructor.
	 * 
	 * @see Symfony\Component\HttpFoundation\Response
	 * 
	 * @param mixed $content
	 * @param string $type
	 * @param bool $streaming
	 */
    function __construct($content = '', $type='json', $streaming=false)
    {
        $headers[ 'Content-Type' ] = self::getContentType($type);
        
        parent::__construct($content, 200, $headers);
        
        $this->setCharset('UTF-8');
        
        $this->setType($type);
        
        $this->setStreaming($streaming);
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
    
    /**
     * This method overide Symfony\Component\HttpFoundation\Response::setContent.
	 * It permit to set a mixed content.
     * @param mixed $content
     * @return \EMC\XmlHttpRequestBundle\Response\XmlHttpResponse
     * @throws \UnexpectedValueException
     */
    public function setContent($content) {
        if (null === $content) {
            throw new \UnexpectedValueException('The Response content must be a string, "'.gettype($content).'" given.');
        }

        $this->content = $content;

        return $this;
    }
    
	/**
	 * Send content response
	 * @return \EMC\XmlHttpRequestBundle\Response\XmlHttpResponse
	 */
    public function sendContent()
	{
        $content = self::prepareContent($this->getType(), $this->getContent());
        
        if ( $this->getStreaming() )
			self::streamContent($content);
        else
			echo $content;
        
        return $this;
    }
    
	/**
	 * returns the response "string" formated for the $type
	 * @param string $type
	 * @param mixed $content
	 * @return string
	 * @throws \InvalidArgumentException
	 */
    private static function prepareContent($type, $content)
    {
        switch ($type) {
            case 'json' :
                return json_encode($content, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_FORCE_OBJECT);

            default:
                throw new \InvalidArgumentException('Unreconized or unimplemented response type "' . $type . '"' );
        }
    }
    
	/**
	 * returns the content-type related to the $type
	 * @param string $type
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private static function getContentType($type)
	{
		switch ($type)
		{
            case 'json' :
                return 'application/json';

            default:
                throw new \InvalidArgumentException('Unreconized or unimplemented response type "' . $type . '"' );
        }
	}
	
	/**
	 * This method write the content in the output buffer and flush it.
	 * It depend to the JS plugin $.stream.
	 * The final content will be formated as :
	 * 
	 * {CONTENT_LENGTH};{CONTENT};
	 * 
	 * Note that the CONTENT_LENGTH is >= 513 caracters
	 * 
	 * @see http://php.net/manual/en/function.flush.php
	 * 
	 * @param string $content
	 */
    public static function streamContent( $content )
    {
		ob_start();
        $content = str_pad($content, 512) . PHP_EOL;
        echo strlen($content) . ';' . $content . ';';
        ob_flush();
        flush();
		ob_end_clean();
    }


}