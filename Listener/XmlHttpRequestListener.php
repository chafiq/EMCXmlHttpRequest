<?php

namespace EMC\XmlHttpRequestBundle\Listener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use EMC\XmlHttpRequestBundle\Event\EventStream;
use EMC\XmlHttpRequestBundle\Event\EventInfo;
use Psr\Log\LoggerInterface;

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
     * Request Type : will be load from the request hearder X-EMC-XmlHttpRequest.
     * @var string|null
     */
    private $requestType;
    
    /**
     * @var array
     */
    private $info;
    
    /**
     *
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var boolean
     */
    private $displayError;

    /**
     * @var string
     */
    private $delemiter;
    
    const HEADER_TAG = 'X-EMC-XmlHttpRequest';

    const EVENT_STREAM = 'emc.xmlhttprequest.stream';
    const EVENT_INFO = 'emc.xmlhttprequest.info';
    
    /**
	 * XmlHttpRequestListener constructor.
	 */
    public function __construct(LoggerInterface $logger = null, $displayError) {
        $this->info = array();
        $this->requestType = null;
        $this->logger = $logger;
        $this->displayError = $displayError;
        $this->delemiter = '--' . substr(md5(rand(0, 100)), 0, 6) . '--';
    }
    
    public function setRequest(RequestStack $requestStack) {
        $request = $requestStack->getMasterRequest();
        
        if ( $request->isXmlHttpRequest() ) {
            $this->requestType = $request->headers->get(self::HEADER_TAG);
        }
        
        if ( $this->requestType !== null ) {
            $this->logger->info('Request is handled by XmlHttpRequestListener' );
        }
    }
    
	/**
	 * Returns TRUE if the XmlHttpRequest annotation is set in the controller action. FALSE otherwise
	 * @return bool
	 */
    private function isHandled($type=null) {
        return $type ? $this->requestType === $type : $this->requestType !== null;
    }
    
    /**
	 * This method is called when the kernel.response is trigged.
	 * Its format the response content.
	 * 
	 * The response format is :
	 * array(
	 *		'code' => 0, // 0 is the success code value
	 *		'data' => mixed (controller data),
     *      'info' => array
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
        if (    $response->headers->has(self::HEADER_TAG)
            ||  ($response->getStatusCode() >= 400 && $response->getStatusCode() !== 500)
        ) {
            return;
        }
        
        $content = $response->getContent();
        if ( $response instanceof JsonResponse ) {
            $content = json_decode($content);
        }
        
        /*
		 * Set the formel response
		 */
        $data = array(
            'code'  => 0,
            'data'  => $content
        );
        if (count($this->info) > 0) {
            $data['info'] = $this->info;
        }
        
        $event->setResponse(
            $this->getResponse($data)
        );
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
    public function onKernelException(GetResponseForExceptionEvent $event) {
        if ( !$this->isHandled() ) {
            return;
        }
        
        $exception = $event->getException();
		
        $log = $exception->getMessage();
        
        /**
		 * Default response error code
		 */
        $code   = -1;
		
		/**
		 * Default response error message
		 */
        $error  = 'Erreur lors de l\'excecution du programme';
        $data = null;
		/*
		 * Check if $exception implements XmlHttpRequestExceptionInterface
         * Only Exception who implements XmlHttpRequestExceptionInterface are send to te client
		 */
        if ( $this->displayError || $exception instanceof XmlHttpRequestExceptionInterface ) {
            if ($exception->getCode() > 0) {
                $code = $exception->getCode();
            }
            $error = $exception->getMessage();
            if ( $exception instanceof XmlHttpRequestExceptionInterface ) {
                $data = $exception->getData();
            }
        }
        
        $response = array(
            'code' => $code,
            'error'=> $error
        );
        
        if ( $data !== null ) {
            $response['data'] = $data;
            $log .= PHP_EOL . json_encode($data);
        }
        
        $this->logger->error( $log . PHP_EOL . $exception->getTraceAsString() );
        
		/*
		 * Set the formel response
		 */
        $event->setResponse(
            $this->getResponse($response)
        );
    }
    
    public function onInfo( EventInfo $event ) {
        if ( !$this->isHandled() ) {
            return;
        }
        
        $this->info = array_merge($this->info, $event->getData());
    }
    
	/**
	 * This method is called when the emc.streaming.progress is trigged.
	 * It's used for sending data to the client or simple informations.
	 * 
	 * @param \EMC\XmlHttpRequestBundle\Event\EventStream $event
	 */
    public function onStream( EventStream $event ) {
        if ( !$this->isHandled('STREAM') ) {
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
		
		$response = $this->getResponse( $data, $this->delemiter . PHP_EOL );
        
        if ( !headers_sent() ) {
            $response->send();
        } else {
            $response->sendContent();
        }
        
        flush();
    }
    
    private function getResponse(array $data, $delemiter='') {
        $response = new JsonResponse('', 200, array(self::HEADER_TAG => $this->delemiter));
        $response->setEncodingOptions(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT /*| JSON_FORCE_OBJECT*/ );
        $response->setData($data);
        $response->setContent($response->getContent() . $delemiter);
        return $response;
    }
}
