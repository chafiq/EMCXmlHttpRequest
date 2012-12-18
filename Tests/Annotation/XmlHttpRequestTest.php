<?php
namespace EMC\XmlHttpResquestBundle\Tests\Annotation;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use EMC\XmlHttpRequestBundle\Annotation\XmlHttpRequest;

class XmlHttpRequestTest extends WebTestCase
{
	
	public static function providerTestType() {
		return array(
			array(array(), 'json'),
			array(array('type'=>'json'),'json'),
			array(array('type'=>'xml'),'xml')
		);
	}
	
	/**
	 * @dataProvider providerTestType
	 */
	public function testType($data, $type)
    {
        $annotation = new XmlHttpRequest($data);
		$this->assertTrue($annotation->getType() === $type);
    }
	
	
	public static function providerTestStreaming() {
		return array(
			array(array(), false),
			array(array('streaming'=>true),true),
			array(array('streaming'=>false),false)
		);
	}
	
	/**
	 * @dataProvider providerTestStreaming
	 */
	public function testStreaming($data, $type)
    {
        $annotation = new XmlHttpRequest($data);
		$this->assertTrue($annotation->getStreaming() === $type);
    }
	
}