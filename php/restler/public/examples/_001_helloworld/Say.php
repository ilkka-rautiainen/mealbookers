<?php
require_once '../../restler/vendor/Luracast/Restler/RestException.php';
class Say {
	function hello($to='world') {
		throw new RestException(404, "joo");
		return "Hello $to!";
	}
	function hi($to) {
		return  "Hi $to!";
	}
}