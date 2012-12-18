<?php
namespace EMC\XmlHttpResquestBundle\Tests\Listener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use EMC\XmlHttpRequestBundle\Annotation\XmlHttpRequest;
use EMC\XmlHttpRequestBundle\Response\XmlHttpResponse;
use EMC\XmlHttpRequestBundle\Listener\XmlHttpRequestListener;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use EMC\XmlHttpRequestBundle\Event\StreamingProgress;

class XmlHttpRequestListenerTest extends WebTestCase
{

    /**
	* @var TestSessionListener
	*/
    private $listener;

    /**
	* @var SessionInterface
	*/
    private $session;

    protected function setUp()
    {
        $this->listener = new XmlHttpRequestListener($this->getMock('Doctrine\Common\Annotations\Reader'));
        $this->session = $this->getSession();
    }

    protected function tearDown()
    {
        $this->listener = null;
        $this->session = null;
    }

    public function testOnKernelView()
    {
		$request = new Request();
		$request->setSession($this->session);
        
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        
		$controllerResult = array('mydata' => array(1,2,3), 'otherdata' => 'Just Test Me !', 13 => null);
		
		$event = new GetResponseForControllerResultEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $controllerResult);

		$annotation = new XmlHttpRequest(array());
		$this->listener->setAnnotation($annotation);
        $this->listener->onKernelView($event);
		
		$response = new XmlHttpResponse(
			$controllerResult,
			$annotation->getType(),
			$annotation->getStreaming()
		);
		
        $this->assertEquals($response, $event->getResponse());
    }
	
    public function testOnKernelResponse()
    {
		$request = new Request();
		$request->setSession($this->session);
        
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        
		$controllerResult = array('mydata' => array(1,2,3), 'otherdata' => 'Just Test Me !', 13 => null);
		
		$eventKernelView = new GetResponseForControllerResultEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $controllerResult);

		$annotation = new XmlHttpRequest(array());
		$this->listener->setAnnotation($annotation);
        $this->listener->onKernelView($eventKernelView);
		
		$response = $eventKernelView->getResponse();
		
		$eventKernelResponse = new FilterResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
		
        $this->listener->onKernelResponse($eventKernelResponse);
		
        $this->assertSame($eventKernelResponse->getResponse(), $eventKernelView->getResponse());
		
		$content = array('code'=>0,'data'=>$controllerResult);
		
		$this->assertEquals($content, $response->getContent());
    }
	
	public function testOnKernelException()
    {
		
		$defaultMessage =  'Erreur lors de l\'excecution du programme';
		
		$request = new Request();
		$request->setSession($this->session);
        
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        
		$exception = new \Exception('My Test Exception.', 0);
		
		$event = new GetResponseForExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $exception);

		$annotation = new XmlHttpRequest(array());
		$this->listener->setAnnotation($annotation);
        $this->listener->onKernelException($event);
		
		$response = new XmlHttpResponse(
			array(
				'code' => -1,
				'error'=> $defaultMessage
			),
			$annotation->getType(),
			$annotation->getStreaming()
		);
		
        $this->assertEquals($response, $event->getResponse());
		
		$severities = array(
			E_ERROR,
            E_WARNING,
            E_PARSE,
            E_NOTICE,
            E_CORE_ERROR,
            E_CORE_WARNING,
            E_COMPILE_ERROR,
            E_COMPILE_WARNING,
            E_USER_ERROR,
            E_USER_WARNING,
            E_USER_NOTICE,
            E_STRICT,
            E_RECOVERABLE_ERROR,
            E_DEPRECATED,
            E_USER_DEPRECATED
		);
		
		foreach( $severities as $severity ) {
			$code = rand();
			$message = 'My Test ErrorException.';
			$exception = new \ErrorException($message, $code, $severity, __FILE__, __LINE__, null);

			$event->setException( $exception );

			$this->listener->onKernelException($event);

			$response = new XmlHttpResponse(
				array(
					'code' => ($severity & (E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE)) != 0 ? $code : -1,
					'error'=> ($severity & (E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE)) != 0 ? $message : $defaultMessage
				),
				$annotation->getType(),
				$annotation->getStreaming()
			);

			$this->assertEquals($response, $event->getResponse());
		}
    }
	
	
	public function testOnStreamingProgress()
	{
		$request = new Request();
		$request->setSession($this->session);
        
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        
		
		$percent = rand(0,100);
		$message = 'test program execution ' . $percent . '% ...';
		$data = array('mydata' => array(1,2,3), 'otherdata' => 'Just Test Me !', 13 => null);
		
		$event = new StreamingProgress($percent, $message , $data);

		$annotation = new XmlHttpRequest(array('streaming'=>true));
		$this->listener->setAnnotation($annotation);
		
		$response = new XmlHttpResponse(
			array(
				'code'		=> 0,
				'stream'	=> array(
					'percent'   => $percent,
					'message'   => $message,
					'data'		=> $data
				)
			),
			$annotation->getType(),
			$annotation->getStreaming()
		);
		
		ob_start();
        $this->listener->onStreamingProgress($event);
		$testContent = ob_get_contents();
		ob_clean();
		$response->send();
		$formatedContent = ob_get_contents();
		ob_clean();
		ob_end_clean();
		
		$this->assertEquals($formatedContent, $testContent);
	}


	private function getSession()
    {
        $mock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        // set return value for getName()
        $mock->expects($this->any())->method('getName')->will($this->returnValue('MOCKSESSID'));

        return $mock;
    }
}