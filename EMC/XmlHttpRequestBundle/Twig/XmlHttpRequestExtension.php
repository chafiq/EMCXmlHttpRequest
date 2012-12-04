<?php

namespace EMC\XmlHttpRequestBundle\Twig;

/**
 * Description of XmlHttpRequestExtension
 *
 * @author Chafiq El Mechrafi <chafiq.elmechrafi@gmail.com>
 */
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Routing\RouterInterface;
use EMC\XmlHttpRequestBundle\Annotation\XmlHttpRequest;

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


    function __construct(Reader $reader, RouterInterface $router) {
        $this->reader = $reader;
        $this->router = $router;
    }

    public function getFunctions()
    {
        return array(
            'xml_http_request'     => new \Twig_Function_Method($this, 'getXmlHttpRequest', array('is_safe' => array('all')))
        );
    }

    public function getXmlHttpRequest(/*string*/ $route, array $parameters, /*string*/ $jsDataVar, /*string*/ $jsCallbackSuccess=null)
    {
        $url = $this->router->generate($route, $parameters, true);
        $route = $this->router->getRouteCollection()->get($route);
        $defaults = $route->getDefaults();
        
        $method = new \ReflectionMethod($defaults['_controller']);
        
        if (!$annotations = $this->reader->getMethodAnnotations($method)) {
            return;
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
            return;
        }
        
        $action = $annotation->getStreaming() ? 'stream' : 'post';
        
        return sprintf(
            'EMCXmlHttpRequest.getInstance().%s("%s", %s, "%s", %s);',
            $action,
            $url,
            $jsDataVar,
            $annotation->getType(),
            strlen($jsCallbackSuccess) > 0 ? $jsCallbackSuccess : "null"
        );
    }
    
    public function getName() {
        return 'xml_http_request_extension';
    }
}