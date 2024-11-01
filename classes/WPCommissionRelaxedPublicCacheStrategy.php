<?php

use Kevinrob\GuzzleCache\Strategy\PublicCacheStrategy;

class WPCommissionRelaxedPublicCacheStrategy extends PublicCacheStrategy
{
	/**
	 * @var int[]
	 */
	protected $statusAccepted = [
		200 => 200,
		203 => 203,
		204 => 204,
		300 => 300,
		301 => 301,
		401 => 401,
		404 => 404,
		405 => 405,
		410 => 410,
		414 => 414,
		418 => 418,
		501 => 501,
	];
}