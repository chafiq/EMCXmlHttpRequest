<?php

namespace EMC\XmlHttpRequestBundle\Twig;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Routing\RouterInterface;
use EMC\XmlHttpRequestBundle\Annotation\XmlHttpRequest;

/**
 * XmlHttpRequestExtension is a twig extension.
 * It define the twig function named "xml_http_request" who can be called from twig templates.
 *
 * @author Chafiq El Mechrafi <chafiq.elmechrafi@gmail.com>
 */
class XmlHttpRequestExtension extends \Twig_Extension
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     *
     * @var RouterInterface
     */
    protected $router;

	/**
	 * XmlHttpRequestExtension constructor.
	 * @param \Doctrine\Common\Annotations\Reader $reader
	 * @param \Symfony\Component\Routing\RouterInterface $router
	 */
    function __construct(Reader $reader, RouterInterface $router) {
        $this->reader = $reader;
        $this->router = $router;
    }

	/**
	 * this function define the twig function named "xml_http_request".
	 * @return array
	 */
    public function getFunctions()
    {
        return array(
            'xml_http_request'     => new \Twig_Function_Method($this, 'getXmlHttpRequest', array('is_safe' => array('all')))
        );
    }

	/**
	 * This function format the JS call for the XmlHttpRequest passing by EMCXmlHttpRequest.
	 * Depends of @EMCXmlHttpRequestBundle/Resources/public/js/EMCXmlHttpRequest.js
	 * It construct the request by getting the parameter from the route annotation (XmlHttpRequest).
	 * There are two kind of calling : "post" and "stream"
	 * @param string $route
	 * @param array $parameters
	 * @param string $jsDataVar JS data var name
	 * @param string $jsSuccessCallback  JS success callback name (note that,
	 *					if null the request will be synchronous)
	 * @param string $jsStreamCallback JS stream callback name (used only if 
	 *					the controller annotation XmlHttpRequest defines
	 *					streaming as true)
	 * @return string
	 * @throws \Exception
	 */
    public function getXmlHttpRequest(	/*string*/ $route, array $parameters,
										/*string*/ $jsDataVar,
										/*string*/ $jsSuccessCallback=null, 
										/*string*/ $jsStreamCallback=null)
    {
        $url = $this->router->generate($route, $parameters, true);
        $route = $this->router->getRouteCollection()->get($route);
        $defaults = $route->getDefaults();
        
        $method = new \ReflectionMethod($defaults['_controller']);
        
		if (!$annotations = $this->reader->getMethodAnnotations($method)) {
            throw new \Exception('Any XmlHttpRequest annotation found for the method.');
        }
        
        /**
         * @var XmlHttpRequest
         */
        $annotation = null;
        foreach($annotations as $_annotation){
            if($_annotation instanceof XmlHttpRequest) {
                $annotation = $_annotation;
                break;
            }
        }
        
        if ( !$annotation instanceof XmlHttpRequest )
        {
            throw new \Exception('Any XmlHttpRequest annotation found for the method.');
        }
        
		/**
		 * formating JS XmlHttpRequest call
		 */
		if ( !$annotation->getStreaming() ) {
			return sprintf(
				'EMCXmlHttpRequest.getInstance().post("%s", %s, "POST", "%s", %s);',
				$url,
				$jsDataVar,
				$annotation->getType(),
				strlen($jsSuccessCallback) > 0 ? $jsSuccessCallback : "null"
			);
		} else {
			return sprintf(
				'EMCXmlHttpRequest.getInstance().stream("%s", %s, "POST", "%s", %s, %s);',
				$url,
				$jsDataVar,
				$annotation->getType(),
				strlen($jsSuccessCallback) > 0 ? $jsSuccessCallback : "null",
				strlen($jsStreamCallback) > 0 ? $jsStreamCallback : "null"
			);
		}
    }
    
    public function getName() {
        return 'xml_http_request_extension';
    }
}