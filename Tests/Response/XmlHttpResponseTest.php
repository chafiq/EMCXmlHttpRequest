<?php
namespace EMC\XmlHttpResquestBundle\Tests\Response;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use EMC\XmlHttpRequestBundle\Response\XmlHttpResponse;

class XmlHttpResponseTest extends WebTestCase
{
	public function testConstruct()
    {
        $response = new XmlHttpResponse();
		
		$this->assertTrue($response->getContent() === '');
		$this->assertTrue($response->getType() === 'json');
		$this->assertTrue($response->getStreaming() === false);
		$this->assertTrue($response->headers->get('Content-Type') === 'application/json');
    }
	
	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConstructException() {
		new XmlHttpResponse('', 'unexistantType');
	}
	
	
	public function testContent() {
		$response = new XmlHttpResponse();
		
		$obj = new \stdClass();
		$obj->myvar = 'simple test';
		
		$content = array('test' => 123, 1 => 'ici', 5 => 19.6, 'obj' => $obj);
		
		$response->setContent($content);
		
		$testContent = $response->getContent();
		
		$this->assertTrue(is_array($testContent));
		$this->assertTrue(count($testContent)===count($content));
		
		foreach( $content as $key => $value ) {
			$this->assertArrayHasKey($key, $testContent);
			$this->assertEquals($testContent[$key], $value);
		}
		
	}
	
	public static function providerTestPrepareContent() {
		
		$obj = new \stdClass();
		$obj->myvar = 'simple test';
		
		$content = array('test' => 123, 1 => 'ici', 5 => 19.6, 'obj' => $obj, 'empty' => array(), 'null' => null);
		
		return array(
			array('json', $content, '{"test":123,"1":"ici","5":19.6,"obj":{"myvar":"simple test"},"empty":{},"null":null}')
		);
	}
	
	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPrepareContentException() {
		
		$class = new \ReflectionClass ('EMC\XmlHttpRequestBundle\Response\XmlHttpResponse');
		$method = $class->getMethod ('prepareContent');
		$method->setAccessible(true);
		
		$method->invoke ('XmlHttpResponse', 'unexistantType', 'nothing');
	}
	
	
	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetContentTypeException() {
		
		$class = new \ReflectionClass ('EMC\XmlHttpRequestBundle\Response\XmlHttpResponse');
		$method = $class->getMethod ('getContentType');
		$method->setAccessible(true);
		
		$method->invoke ('XmlHttpResponse', 'unexistantType');
	}
	
	/**
	 * @dataProvider providerTestPrepareContent
	 */
	public function testPrepareContent($type, $content, $result) {
		
		$class = new \ReflectionClass ('EMC\XmlHttpRequestBundle\Response\XmlHttpResponse');
		$method = $class->getMethod ('prepareContent');
		$method->setAccessible(true);
		
		$this->assertEquals(
			$method->invoke ('XmlHttpResponse', $type, $content),
			$result
		);
	}
	
	public function testStreamContent() {
		
		$content = 'test this string :)';
		
		$formatedContent = str_pad( $content, 512) . PHP_EOL;
        $formatedContent = strlen($formatedContent) . ';' . $formatedContent . ';';
		
		ob_start();
		XmlHttpResponse::streamContent($content);
		$testContent = ob_get_contents();
		ob_clean();
		
		$this->assertEquals($formatedContent, $testContent);
		
	}
	
	public function testSendContent() 
	{
		$response = new XmlHttpResponse();
		
		$content = 'test this string :)';
		
		$response->setContent($content);
		
		$formatedContent = json_encode($content);
		
		ob_start();
		$response->sendContent();
		$testContent = ob_get_contents();
		ob_clean();
		
		$this->assertEquals($formatedContent, $testContent);
		
		$response->setStreaming(true);
		
		$response->sendContent();
		$testContent = ob_get_contents();
		ob_clean();
		
		$formatedContent = str_pad($formatedContent, 512) . PHP_EOL;
        $formatedContent = strlen($formatedContent) . ';' . $formatedContent . ';';
		$this->assertEquals( $formatedContent, $testContent);
	}
}