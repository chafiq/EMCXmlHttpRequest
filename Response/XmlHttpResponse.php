<?php

namespace EMC\XmlHttpRequestBundle\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Description of Json
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
    
    function __construct($content = '', $type='json', $streaming=false)
    {
        $headers[ 'Content-Type' ] = 'application/json';
        
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
     * 
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
    
    public function sendContent() {
        
        $content = $this->prepareContent();
        
        if ( $this->getStreaming() )
        {
            $content = self::streamContent($content);
            die;
        } else {
            echo $content;
        }
        
        return $this;
    }
    
    private function prepareContent()
    {
        switch ($this->getType()) {
            case 'json' :
                return json_encode($this->getContent(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

            default:
                throw new \InvalidArgumentException('Unreconized or unimplemented response type "' . $type . '"' );
        }
    }
    
    
    public static function streamContent( $content )
    {
        $content = str_pad($content, 512) . PHP_EOL;
        echo strlen($content) . ';' . $content . ';';
        ob_flush();
        flush();
        
        return $content;
    }


}