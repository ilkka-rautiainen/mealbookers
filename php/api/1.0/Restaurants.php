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
				'name' => 'Täffä',
				'id' => 2,
				'menu' => array(
					array(
                        array(
                            'id' => 1,
                            'name' => 'Jäätelöä',
                        ),
                        array(
                            'id' => 2,
                            'name' => 'Ben & Jerrys',
                        ),
                        array(
                            'id' => 3,
                            'name' => 'Ingman',
                        ),
                        array(
                            'id' => 4,
                            'name' => 'Valio',
                        ),
					),
					array(
                        array(
                            'id' => 5,
                            'name' => 'Fis än kips',
                        ),
                        array(
                            'id' => 6,
                            'name' => 'Soosi',
                        ),
                        array(
                            'id' => 7,
                            'name' => 'Sallaatti',
                        ),
                        array(
                            'id' => 8,
                            'name' => 'Salaatinkastike',
                        ),
					),
					array(
                        array(
                            'id' => 9,
                            'name' => 'Polonesea',
                        ),
                        array(
                            'id' => 10,
                            'name' => 'Kastiketta',
                        ),
                        array(
                            'id' => 11,
                            'name' => 'Juupajuu',
                        ),
                        array(
                            'id' => 12,
                            'name' => 'Eippä ei',
                        ),
					),
					array(
                        array(
                            'id' => 13,
                            'name' => 'Eksoottista sapuskaa',
                        ),
                        array(
                            'id' => 14,
                            'name' => 'Ja vähän muutakin',
                        ),
                        array(
                            'id' => 15,
                            'name' => 'Sitä sun tätä',
                        ),
                        array(
                            'id' => 16,
                            'name' => 'Makkispekkis',
                        ),
                        array(
                            'id' => 20,
                            'name' => 'Pekkismakkis',
                        ),
                        array(
                            'id' => 21,
                            'name' => 'Pekkismakkis 2',
                        ),
					),
					array(
                        array(
                            'id' => 17,
                            'name' => 'Limua',
                        ),
                        array(
                            'id' => 18,
                            'name' => 'Ranskalaisia',
                        ),
                        array(
                            'id' => 19,
                            'name' => 'Pihviä',
                        ),
                        array(
                            'id' => 20,
                            'name' => 'Lohkoperunoita',
                        ),
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