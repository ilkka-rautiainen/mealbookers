<?php
use Luracast\Restler\RestException;

class Restaurants
{
	/**
	 * Listens to /restaurants
	 */
	function get()
	{
		Logger::debug(__METHOD__ . " /restaurants called");
		$mock_data = array(
			array(
				'name' => 'Alvari',
				'id' => 1,
				'menu' => array(
					array(
						'Lihapullia',
						'Puhalillia',
					),
					array(
						'Spakettia',
						'Ketsuppia',
					),
					array(
						'Pizzaa',
						'Oreganoa',
					),
					array(
						'Eipä juuri mitään',
						'Vähän sitä sun tätä',
					),
					array(
						'Omenaa',
						'Kaalia',
					),
				)
			),
			array(
				'name' => 'Täffä',
				'id' => 2,
				'menu' => array(
					array(
						'Jäätelöä',
						'Ben & Jerrys',
					),
					array(
						'Fis än kips',
						'Soosi',
					),
					array(
						'Polonesea',
						'Kastiketta',
					),
					array(
						'Eksoottista sapuskaa',
						'Ja vähän muutakin',
					),
					array(
						'Limua',
						'Ranskalaisia',
					),
				)
			),
		);
		return $mock_data;
	}

	/**
	 * Listens to /restaurants/:restaurantId
	 * @url GET {restaurantId}
	 */
	function getRestaurant($restaurantId)
	{
		Logger::debug(__METHOD__ . " /restaurants/:restaurantId called");
		if ($restaurantId == 1)
			return array(
				'id' => 1,
				'name' => "Alvari",
				'opening_hours' => "On jo kiinni, pahvi!",
			);
		else if ($restaurantId == 2)
			return array(
				'id' => 2,
				'name' => "Täffä",
				'opening_hours' => "Kauan",
			);
		else
			throw new RestException(404, "Restaurant not found");
	}
}