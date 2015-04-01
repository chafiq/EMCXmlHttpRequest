<?php

namespace EMC\XmlHttpRequestBundle\Listener;

interface XmlHttpRequestExceptionInterface {
	public function getMessage ();
	public function setMessage ($message);
	public function getCode ();
    public function getData();
}
