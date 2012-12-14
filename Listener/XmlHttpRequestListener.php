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
 * XmlHttpRequestListener is an events listener.
 * It bind the following events :
 * - kernel.controller
 * - kernel.view
 * - kernel.response
 * - kernel.exception
 * - emc.streaming.progress
 *
 * @author Chafiq El Mechrafi <chafiq.elmechrafi@gmail.com>
 */
class XmlHttpRequestListener {

	/**
	 * Reader service (Dependency Injection)
	 * @var Reader
	 */
    private $reader;
	
    /**
	 * Annotation set in the controller action
     * @var XmlHttpRequest
     */
    private $annotation;
    
	/**
	 * XmlHttpRequestListener constructor.
	 * @param \Doctrine\Common\Annotations\Reader $reader
	 */
    public function __construct(Reader $reader) {
        $this->reader = $reader;
    }
    
	/**
	 * returns the XmlHttpRequest annotation
	 * @return XmlHttpRequest
	 */
    public function getAnnotation() {
        return $this->annotation;
    }

	/**
	 * Set the XmlHttpRequest annotation
	 * @param \EMC\XmlHttpRequestBundle\Annotation\XmlHttpRequest $annotation
	 * @return \EMC\XmlHttpRequestBundle\Listener\XmlHttpRequestListener
	 */
    public function setAnnotation(XmlHttpRequest $annotation) {
        $this->annotation = $annotation;
		return $this;
    }
    
	/**
	 * Returns TRUE if the XmlHttpRequest annotation is set in the controller action. FALSE otherwise
	 * @return bool
	 */
    private function isHandled()
    {
        return $this->getAnnotation() instanceof XmlHttpRequest;
    }
    
	/**
	 * This method is called when the kernel.controller is trigged.
	 * It try to get the XmlHttpRequest annotation defined in the controller action.
	 * 
	 * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
	 */
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
    
	/**
	 * This method is called when the kernel.view is trigged.
	 * It sets the an instance of XmlHttpResponse with the controller result
	 * 
	 * @param \Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent $event
	 */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        if ( !$this->isHandled() ) {
            return;
        }
		
        $event->setResponse(
            new XmlHttpResponse(
                $event->getControllerResult(),
                $this->getAnnotation()->getType(),
                $this->getAnnotation()->getStreaming()
            )
        );
    }
    
    /**
	 * This method is called when the kernel.response is trigged.
	 * Its format the response content.
	 * 
	 * The response format is :
	 * array(
	 *		'code' => 0, // 0 is the success code value
	 *		'data' => mixed (controller data)
	 * )
	 * 
	 * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
	 */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ( !$this->isHandled() ) {
            return;
        }
		
        // Get response content
        $response = $event->getResponse();
        $content = $response->getContent();
		
		/**
		 * If the response content was already formated -> exit.
		 */
        if ( is_array( $content ) && isset( $content[ 'code' ] ) && isset( $content[ 'error' ] ) ) {
            return;
        }
        
		/**
		 * @var array formated response content
		 */
        $newContent = array(
            'code' => 0,
            'data' => $content
        );
        
        $response->setContent($newContent);
    }
    
	/**
	 * This method is called when the kernel.exception is trigged.
	 * It called if an error occured during the controller action.
	 * If the exception is an ErrorException and its severity is E_USER_ERROR, E_USER_WARNING or E_USER_NOTICE,
	 * the code and the error message are extracted from the exception.
	 * Otherwise, the code and the error message will be those default.
	 * Note: The ErrorException may be thrown by using "throw new ..." or "trigger_error"
	 * 
	 * The response content will be :
	 * 
	 * array(
	 *		'code' => integer
	 *		'error'=> string
	 * )
	 * 
	 * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
	 */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ( !$this->isHandled() ) {
            return;
        }
        $exception = $event->getException();
        
		/**
		 * Default response error code
		 */
        $code   = -1;
		
		/**
		 * Default response error message
		 */
        $error  = 'Erreur lors de l\'excecution du programme';
        
		/*
		 * Check if $exception is an ErrorException with severity E_USER_ERROR, E_USER_WARNING or E_USER_NOTICE
		 */
        if ( $exception instanceof \ErrorException ) {
        	if ( ($exception->getSeverity() & (E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE)) != 0 ) {
        		$code = $exception->getCode();
				$error = $exception->getMessage();
        	}
        }
        
		
		/**
		 * data response if an error occured during the process
		 */
        $response = array(
            'code' => $code,
            'error'=> $error
        );
        
		/*
		 * Set the formel response
		 */
        $event->setResponse(
            new XmlHttpResponse(
                $response,
                $this->getAnnotation()->getType(),
                $this->getAnnotation()->getStreaming()
            )
        );
    }
    
	/**
	 * This method is called when the emc.streaming.progress is trigged.
	 * It's used for sending data to the client or simple informations.
	 * 
	 * @param \EMC\XmlHttpRequestBundle\Event\StreamingProgress $event
	 */
    public function onStreamingProgress( StreamingProgress $event )
    {
        if ( !$this->isHandled() || !$this->getAnnotation()->getStreaming() ) 
        {
            return;
        }
        
        $data = array(
			'code'		=> 0,
			'stream'	=> array(
				'percent'   => $event->getPercent(),
				'message'   => $event->getMessage(),
				'data'		=> $event->getData()
			)
        );
		
		$response = new XmlHttpResponse(
			$data,
			$this->getAnnotation()->getType(),
			$this->getAnnotation()->getStreaming()
		);
		$response->send();
    }
}
