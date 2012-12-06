# XmlHttpRequest bundle.

This bundle gives a unified way to handle and control AJAX requests.

## Installation

1. Download EMCXmlHttpRequest
2. Configure the Autoloader
3. Enable the Bundle

### Step 1: Download EMCXmlHttpRequest

Ultimately, the EMCXmlHttpRequest files should be downloaded to the `vendor/bundles/EMC/Bundle/XmlHttpRequest` directory.

This can be done in several ways, depending on your preference. The first method is the standard Symfony2 method.

**Using Composer**

Add EMCXmlHttpRequest in your composer.json:

```
{
    "require": {
        "emc/xmlhttprequest-bundle": "*"
    }
}
```

Now tell composer to download the bundle by running the command:

``` bash
$ php composer.phar update emc/xmlhttprequest-bundle
```

**Using submodules**

If you prefer instead to use git submodules, then run the following:

``` bash
$ git submodule add git://github.com/chafiq/EMCXmlHttpRequest.git vendor/emc/xmlhttprequest-bundle/EMC/XmlHttpRequestBundle/
$ git submodule update --init
```

Note that using submodules requires manually registering the `EMC` namespace to your autoloader:

``` php
<?php
// app/autoload.php

$loader->registerNamespaces(array(
    // ...
    'EMC' => __DIR__.'/../vendor/bundles',
));
```

### Step 2: Enable the bundle

Finally, enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new EMC\Bundle\XmlHttpRequestBundle\EMCXmlHttpRequestBundle(),
    );
}
```

## Dependances

* jQuery >= v1.4.2 : http://jquery.com/download/
* jquery-stream v1.2 : http://code.google.com/p/jquery-stream/downloads/detail?name=jquery.stream-1.2.min.js&can=2&q=

## Exemples

### Simple Post

Controller Code
``` php
    use EMC\XmlHttpRequestBundle\Annotation\XmlHttpRequest;

    /**
     * @Route("/ajax/", name="_ajax_call")
     * @XmlHttpRequest(type="json") // You can add streaming=true to get the streaming mode
     */
    public function ajaxAction()
    {
    	/**
    	 * Data result
    	 * mixed
    	 * The response will be "{code:0,data:{1:'My result',2:'My fancy bundle',5:'My reponse'}}"
    	 */
        return array(
        	1 => 'My result',
        	2 => 'My fancy bundle',
        	5 => 'My reponse'
        );
    }
```

Template Code

``` twig			
...
	<script type="text/javascript">
		$(document).ready(function(){
		
			var params = {test : 123};
			
			/* "xml_http_request" is a twig extension.
			   It creates the JavaScript call using the route annotation "XmlHttpRequest".
			   The first and second parameters are used for creating the route path.
			   The third parameter is the name of data source parameters for the ajax call.
			   You can add a fourth parameter which is the name of the JS callback.
			   
			   @see @EMCXmlHttpRequestBundle\Twig\XmlHttpRequestExtension for more information
			   
			   The generated code of "{% xml_http_request('_ajax_call', {}, 'params') %}" is :
			   
			   EMCXmlHttpRequest.getInstance().stream("http://www.dev.local/app_dev.php/demo/ajax/", params, "json", null);
			*/
			var result = {% xml_http_request('_ajax_call', {}, 'params') %}
			
			console.log( result );
		});
	</script>
...
```

### Streaming Informations


Controller Code
``` php

    use EMC\XmlHttpRequestBundle\Annotation\XmlHttpRequest;
    use EMC\XmlHttpRequestBundle\Event\StreamingProgress;

    /**
     * @Route("/ajax/", name="_ajax_call")
     * @XmlHttpRequest(type="json", streaming=true)
     */
    public function ajaxAction()
    {
    
    	for($i=0; $i<5; $i++) {
    		
    		$event = new StreamingProgress($i*20, 'Execution message info ' . ($i*20) . '% ...');
    		
    		$this->get('event_dispatcher')->dispatch( 'emc.streaming.progress', $event);
    		
    		sleep(1);
    	}
    	
        return array(
        	1 => 'My result',
        	2 => 'My fancy bundle',
        	5 => 'My reponse'
        );
    }
```
Template Code

Same then the first exemple.



