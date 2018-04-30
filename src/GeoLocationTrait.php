<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2018 Daniyal Hamid (https://designcise.com)
 *
 * @license   https://github.com/designcise/bitframe/blob/master/LICENSE.md MIT License
 */

namespace BitFrame\Locale;

use \Psr\Http\Message\ServerRequestInterface;

use \Geocoder\Query\GeocodeQuery;
use \Geocoder\Provider\Provider;
use \Geocoder\Provider\Chain\Chain;
use \Geocoder\Provider\FreeGeoIp\FreeGeoIp;
use \Geocoder\Provider\GeoPlugin\GeoPlugin;
use \Geocoder\Collection;

use BitFrame\Locale\RemoteAddressTrait;

/**
 * Common wrapper for the Geocoder plugin.
 */
trait GeoLocationTrait
{
	use RemoteAddressTrait;
	
	/** @var Provider */
    private $provider;
	
	/**
	 * Set Geocoder Provider.
	 *
	 * @param Provider|array $provider
	 *
	 * @return $this
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setProvider($provider): self
	{
		$isEmpty = empty($provider);
		$isArray = is_array($provider);
		$isProvider = ($provider instanceof Provider);
		
		// is provider valid?
		if ($isEmpty || (! $isArray && ! $isProvider)) {
			throw new \InvalidArgumentException(sprintf(
				'"%s" is not a valid Provider; must be an array, or single instance, of "%s"',
				($isEmpty) ? "Empty array" : gettype($provider),
				Provider::class
			));
		}
		
		// validate that all providers are instances of 'Provider'
		if ($isArray && empty(
			array_filter($provider, function($p) {
				return ($p instanceof Provider);
			})
		)) {
			throw new \InvalidArgumentException(sprintf(
				'Array must contain valid instance(s) of "%s"',
				Provider::class
			));
		}
		
		$this->provider = ($isProvider) ? $provider : new Chain($provider);
		
		return $this;
	}
	
	/**
	 * Get Geocoder Provider (sets a default provider if no provider available).
	 *
	 * @return Provider
	 */
	public function getProvider(): Provider
	{
		$client = new \Http\Adapter\Guzzle6\Client();
		
		$this->provider = $this->provider ?: new Chain([
			new FreeGeoIp($client),
			new GeoPlugin($client)
		]);
		
		return $this->provider;
	}

    /**
     * Get location data.
	 *
	 * @param ServerRequestInterface $request
	 * @param string $ip
	 *
	 * @return Collection
	 *
	 * @throws \BitFrame\Locale\Exception\IpAddressNotFoundException
	 * @throws \InvalidArgumentException
     */
    public function getGeoLocationData(ServerRequestInterface $request, string $ip): Collection
    {
		if (empty($ip)) {
			throw new \BitFrame\Locale\Exception\IpAddressNotFoundException();
		} else if (! $this->isIpValid($ip)) {
			throw new \InvalidArgumentException(sprintf(
				'IP Address "%s" is not valid',
				$ip
			));
		}
        
		return ($this->getProvider())->geocodeQuery(GeocodeQuery::create($ip));
    }
}