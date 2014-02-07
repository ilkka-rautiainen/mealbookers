<?php
use Luracast\Restler\RestException;

class App
{
	/**
     * @url GET log/{passphrase}
     * @url GET log/{passphrase}/{rows}
	 */
	function getLog($passphrase, $rows = 1000)
	{
		global $config;
		Logger::debug(__METHOD__ . " /app/log called");
		$rows = (int)$rows;

		if ($passphrase != "mealilogi")
			throw new RestException(404);
		

		$filename = "../../app/" . $config['log']['file'];

		return array_reverse(array_slice(array_reverse(file($filename)), 0, $rows));
	}
}