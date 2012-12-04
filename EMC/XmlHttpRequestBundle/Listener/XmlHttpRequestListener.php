<?php

namespace EMC\XmlHttpRequestBundle\Listener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Doctrine\Common\Annotations\Reader;
use EMC\XmlHttpRequestBundle\Annotation\XmlHttpRequest;
use EMC\XmlHttpRequestBundle\Event\StreamingProgress;
use EMC\XmlHttpRequestBundle\Response\XmlHttpResponse;

/**
 * Description of XmlHttpRequestListener
 *
 * @author Chafiq El Mechrafi <chafiq.elmechrafi@gmail.com>
 */
class XmlHttpRequestListener {

    private $reader;
    /**
     * @var XmlHttpRequest
     */
    private $annotation;
    
    public function __construct(Reader $reader) {
        $this->reader = $reader;
    }
    
    public function getAnnotation() {
        return $this->annotation;
    }

    public function setAnnotation(XmlHttpRequest $annotation) {
        $this->annotation = $annotation;
    }
    
    private function isHandled()
    {
        return $this->getAnnotation() instanceof XmlHttpRequest;
    }
    
    public function onCoreController(FilterControllerEvent $event) {
        
        if (!is_array($controller = $event->getController())) {
            return;
        }
 
        $method = new \ReflectionMethod($controller[0], $controller[1]);
        
        if (!$annotations = $this->reader->getMethodAnnotations($method)) {
            return;
        }

        foreach($annotations as $annotation){
            if($annotation instanceof XmlHttpRequest) {
                $this->setAnnotation($annotation);
            }
        }
    }
    
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $event->setResponse(
            new XmlHttpResponse(
                $event->getControllerResult(),
                $this->getAnnotation()->getType(),
                $this->getAnnotation()->getStreaming()
            )
        );
    }
    
    
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ( !$this->isHandled() ) {
            return;
        }
        
        $response = $event->getResponse();
        $content = $response->getContent();
        if ( is_array( $content ) && isset( $content[ 'code' ] ) && isset( $content[ 'error' ] ) ) {
            return;
        }
        
        $content = array(
            'code' => 0,
            'data' => $content
        );
        
        $response->setContent($content);
    }
    
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ( !$this->isHandled() ) {
            return;
        }
        $exception = $event->getException();
        
        $code   = 1;
        $error  = 'Erreur lors de l\'excecution du programme';
        
        if ( $exception instanceof \ErrorException ) {
        	$severity = E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE;
        	if ( ($exception->getSeverity() & (E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE)) != 0 ) {
        		$code = $exception->getCode();
			$error = $exception->getMessage();
        	}
        }
        
        $response = array(
            'code' => $code,
            'error'=> $error
        );
        
        $event->setResponse(
            new XmlHttpResponse(
                $response,
                $this->getAnnotation()->getType(),
                $this->getAnnotation()->getStreaming()
            )
        );
    }
    
    public function onStreamingProgress( StreamingProgress $event )
    {
        if ( !$this->isHandled() || !$this->getAnnotation()->getStreaming() ) 
        {
            return;
        }
        
        $data = json_encode(array(
            'percent'   => $event->getPercent(),
            'message'   => $event->getMessage()
        ));
        
        XmlHttpResponse::streamContent($data);
    }
}
