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

use \Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use \Psr\Http\Server\{RequestHandlerInterface, MiddlewareInterface};

use BitFrame\Delegate\CallableMiddlewareTrait;
use BitFrame\Locale\GeoLocationTrait;

/**
 * Geo Location wrapper class to fetch user geo 
 * data as a middleware.
 */
class GeoLocation implements MiddlewareInterface
{
    use CallableMiddlewareTrait;
    use GeoLocationTrait;
    
    /** @var bool */
    private $remoteIpLookup;
    
    /**
     * @param bool $remoteIpLookup (optional)
     */
    public function __construct($remoteIpLookup = false)
    {
        $this->remoteIpLookup = $remoteIpLookup;
    }
    
    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // retrieve ip
        $ip = $this->getIpAddress($request, $this->remoteIpLookup);
        
        // retrieve location data
        $loc = ($this->getGeoLocationData($request, $ip))->first();
        $country = $loc->getCountry();
        $coords = $loc->getCoordinates() ?: new class {
            public function getLongitude() {return null;}
            public function getLatitude() {return null;}
        };
        
        $data = new \BitFrame\Locale\GeoLocationData([
            'ip' => $ip,
            'geocoder' => $loc,
            
            'longitude' => $coords->getLongitude(),
            'latitude' => $coords->getLatitude(),
            
            'locality' => $loc->getLocality(),
            'country' => $country->getName(),
            'country_code' => $country->getCode(),
            
            'timezone' => $loc->getTimezone(),
            
            'date_checked' => date('c') // ISO 8601 date
        ]);
        
        return $handler->handle($request->withAttribute(GeoLocationData::class, $data));
    }
    
    /**
     * Set setting that determines whether IP will be looked up via an online service or not.
     *
     * This may especially be useful when using this on localhost.
     *
     * @return bool
     */
    public function setRemoteIpLookup(bool $remoteIpLookup): self
    {
        $this->remoteIpLookup = $remoteIpLookup;
        
        return $this;
    }
    
    /**
     * Get remote ip lookup setting.
     *
     * @return bool
     */
    public function getRemoteIpLookup(): bool
    {
        return $this->remoteIpLookup;
    }
}