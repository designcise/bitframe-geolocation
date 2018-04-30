<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2018 Daniyal Hamid (https://designcise.com)
 *
 * @license   https://github.com/designcise/bitframe/blob/master/LICENSE.md MIT License
 */

namespace BitFrame\Test;

use \PHPUnit\Framework\TestCase;

use \Psr\Http\Message\ResponseInterface;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Server\RequestHandlerInterface;

use \Geocoder\Collection;

use \BitFrame\Locale\GeoLocationData;
use \BitFrame\Factory\HttpMessageFactory;

/**
 * @covers \BitFrame\Locale\GeoLocation
 */
class GeoLocationTest extends TestCase
{
	/** @var \Psr\Http\Message\ServerRequestInterface */
	private $request;
	
    /** @var \Psr\Http\Message\ResponseInterface */
    private $response;
	
	/** @var \BitFrame\Locale\GeoLocation */
	private $location;
	
    public function setUp()
    {
		$this->request = HttpMessageFactory::createServerRequest();
		$this->location = new \BitFrame\Locale\GeoLocation();
    }
	
	public function testGeoLocationDataSet()
	{
		$location = $this->getMockBuilder('\BitFrame\Locale\GeoLocation')->setMethods(['getIpAddress'])->getMock();
		$location->method('getIpAddress')->willReturn('127.0.0.1');
		
        $response = $location->process($this->request, new class($this) implements RequestHandlerInterface {
			/** @var \PHPUnit\Framework\TestCase */
			private $test;
			
			public function __construct($testCaseInstance)
			{
				$this->test = $testCaseInstance;
			}
			
			public function handle(ServerRequestInterface $request): ResponseInterface 
			{
				$data = $request->getAttribute(GeoLocationData::class, false);
				
				$this->test->expectException(\BadMethodCallException::class);
				$data['test'] = 'test';
				
				return HttpMessageFactory::createResponse(200);
			}
		});
	}
	
	public function testGeoLocationDataUnset()
	{
		$location = $this->getMockBuilder('\BitFrame\Locale\GeoLocation')->setMethods(['getIpAddress'])->getMock();
		$location->method('getIpAddress')->willReturn('127.0.0.1');
		
        $response = $location->process($this->request, new class($this) implements RequestHandlerInterface {
			/** @var \PHPUnit\Framework\TestCase */
			private $test;
			
			public function __construct($testCaseInstance)
			{
				$this->test = $testCaseInstance;
			}
			
			public function handle(ServerRequestInterface $request): ResponseInterface 
			{
				$data = $request->getAttribute(GeoLocationData::class, false);
				
				$this->test->expectException(\BadMethodCallException::class);
				unset($data['ip']);
				
				return HttpMessageFactory::createResponse(200);
			}
		});
	}
	
	public function testGetLocationDataWithLocalhostIp()
    {
		// returns collection object
        $data = $this->location->getGeoLocationData($this->request, '127.0.0.1');
		
		$this->assertInstanceOf(Collection::class, $data);
		
		$loc = $data->first();
		$country = $loc->getCountry();
		
		$this->assertSame('localhost', $country->getName());
    }
	
	public function testGetLocationDataWithInvalidIp()
    {
		$this->expectException(\InvalidArgumentException::class);
		
        $this->location->getGeoLocationData($this->request, 'invalid');
    }
	
	public function testGetLocationDataWithNoIp()
    {
		$this->expectException(\BitFrame\Locale\Exception\IpAddressNotFoundException::class);
		
        $this->location->getGeoLocationData($this->request, '');
    }
	
	/*public function testGetLocationDataWithCountryIp()
    {
		// returns collection object
        $data = $this->location->getGeoLocationData($this->request, '8.8.8.8');
		
		$this->assertInstanceOf(Collection::class, $data);
		
		$loc = $data->first();
		$country = $loc->getCountry();
		
		$this->assertEquals('United States', $country->getName());
		$this->assertEquals('US', $country->getCode());
    }*/
	
	public function testGetIpAddressWithoutRemoteLookup() 
	{
		$ip = $this->location->getIpAddress($this->request, false);
		
		$this->assertEmpty($ip);
	}
	
	/*public function testGetIpAddressWithRemoteLookup() 
	{
		$ip = $this->location->getIpAddress($this->request, true);
		
		$this->assertNotFalse(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6));
	}*/
	
	public function testProcessMiddleware()
    {
		$location = $this->getMockBuilder('\BitFrame\Locale\GeoLocation')->setMethods(['getIpAddress'])->getMock();
		$location->method('getIpAddress')->willReturn('127.0.0.1');
		
        $response = $location->process($this->request, new class($this) implements RequestHandlerInterface {
			/** @var \PHPUnit\Framework\TestCase */
			private $test;
			
			public function __construct($testCaseInstance)
			{
				$this->test = $testCaseInstance;
			}
			
			public function handle(ServerRequestInterface $request): ResponseInterface 
			{
				$data = $request->getAttribute(GeoLocationData::class, false);
				
				// location data was set?
				$this->test->assertNotFalse($data);
				
				// does the data array have all the required keys?
				$this->test->assertTrue(isset(
					$data, 
					$data['ip'],
					$data['geocoder'],
			
					$data['longitude'],
					$data['latitude'],

					$data['locality'],
					$data['country'],
					$data['country_code'],

					$data['timezone'],

					$data['date_checked']
				));

				$this->test->assertEquals('localhost', $data['country']);
				
				return HttpMessageFactory::createResponse(200);
			}
		});

        $this->assertEquals(200, $response->getStatusCode());
    }
	
	public function testGeoLocationCanSetAndGetAllProperties()
    {
		$provider = $this->createMock('\Geocoder\Provider\Provider');
        $this->assertSame($provider, $this->location->setProvider($provider)->getProvider());
		
		$remoteIpLookup = false;
        $this->assertSame($remoteIpLookup, $this->location->setRemoteIpLookup($remoteIpLookup)->getRemoteIpLookup());
		
        $useProxy = false;
		$this->assertSame($useProxy, $this->location->setUseProxy($useProxy)->isUseProxy());
		
        $trustedProxies = ['192.168.0.1', '192.168.0.2'];
		$this->assertSame($trustedProxies, $this->location->setTrustedProxies($trustedProxies)->getTrustedProxies());
		
		// proxy headers are normalized internally
        $proxyHeader = ['HTTP_X_Forwarded', 'X-Forwarded-For'];
		$this->assertSame(['HTTP_X_FORWARDED', 'HTTP_X_FORWARDED_FOR'], $this->location->setProxyHeader($proxyHeader)->getProxyHeader());
		
		$remoteAddr = 'http://ipecho.net/plain';
		$this->assertSame($remoteAddr, $this->location->setRemoteAddr($remoteAddr)->getRemoteAddr());
    }
}