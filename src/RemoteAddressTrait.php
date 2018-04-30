<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2018 Daniyal Hamid (https://designcise.com)
 * @license   https://github.com/designcise/bitframe/blob/master/LICENSE.md MIT License
 *
 * @author    Zend Framework
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

namespace BitFrame\Locale;

use \Psr\Http\Message\ServerRequestInterface;

/**
 * Get user ip.
 */
trait RemoteAddressTrait 
{
    /** @var bool */
    private $useProxy = false;
	
    /** @var array */
    private $trustedProxies = [];
	
    /** @var array */
    private $proxyHeader = [
		'Forwarded',
		'Forwarded-For',
		'X-Forwarded',
		'X-Forwarded-For',
		'X-Cluster-Client-Ip',
		'Client-Ip'
	];
	
	/** @var string */
	private $remoteAddr = 'https://api.ipify.org/?format=text';
	
    /**
     * Changes proxy handling setting which determines whether to use proxy addresses or not.
     *
     * By default this setting is disabled - IP address is mostly needed to increase
     * security. HTTP_* are not reliable since can easily be spoofed. Enabling this setting
     * can provide more flexibility, but if user uses proxy to connect to trusted services
     * it's their own risk; the only reliable field for IP address is $_SERVER['REMOTE_ADDR'].
     *
     * @param bool $useProxy (optional) Check for proxied IP addresses?
	 *
	 * @return $this
     */
    public function setUseProxy(bool $useProxy = true): self
    {
        $this->useProxy = $useProxy;
		
		return $this;
    }
	
    /**
     * Checks proxy handling setting.
     *
     * @return bool
     */
    public function isUseProxy(): bool
    {
        return $this->useProxy;
    }
	
    /**
     * Set list of trusted proxy addresses.
     *
     * @param string[] $trustedProxies
	 *
	 * @return $this
     */
    public function setTrustedProxies(array $trustedProxies): self
    {
        $this->trustedProxies = $trustedProxies;
		
		return $this;
    }
	
	/**
	 * Get list of trusted proxy IP addresses.
	 *
	 * @return string[]
	 */
	public function getTrustedProxies(): array
	{
		return $this->trustedProxies;
	}
	
    /**
     * Set the header to introspect for proxy IPs.
     *
     * @param string[] $header (optional)
	 *
	 * @return $this
     */
    public function setProxyHeader(array $header = [
		'Forwarded',
		'Forwarded-For',
		'X-Forwarded',
		'X-Forwarded-For',
		'X-Cluster-Client-Ip',
		'Client-Ip'
	]): self
    {
        $this->proxyHeader = [];
		
		foreach ($header as $name) {
			$this->proxyHeader[] = $this->normalizeProxyHeader($name);
		}
		
		return $this;
    }
	
	/**
	 * Get HTTP headers to introspect for proxies.
	 *
	 * @return string[]
	 */
	public function getProxyHeader(): array
	{
		return $this->proxyHeader;
	}
	
	/**
     * Set the remote address from where the ip will be looked up.
     *
     * @param string $addr
	 *
	 * @return $this
     */
	public function setRemoteAddr(string $addr): self
	{
		$this->remoteAddr = $addr;
		
		return $this;
	}
	
	/**
     * Get the remote address from where the ip will be looked up.
     *
     * @return string
     */
	public function getRemoteAddr(): string
	{
		return $this->remoteAddr;
	}
	
    /**
     * Returns client IP address.
     *
	 * @param ServerRequestInterface $request
	 * @param bool $remoteLookup (optional)
	 *
     * @return string
     */
    public function getIpAddress(ServerRequestInterface $request, bool $remoteLookup = false): string
    {
		// case 1: get ip from a remote source
        if ($remoteLookup && ($remoteIp = $this->getRemoteIp()) !== null) {
            // found ip address via remote service.
            return $remoteIp;
        }
		
		// case 2: get local ip
		if (($localIp = $this->getLocalIp($request)) !== null && ! in_array($localIp, $this->trustedProxies)) {
            // local IP address does not point at a known proxy, do not attempt
            // to read proxied IP address.
            return $localIp;
        }
		
		// case 3: get ip from proxy
        if (($proxiedIp = $this->getProxiedIp($request)) !== null) {
            return $proxiedIp;
        }
		
        return ($localIp ?: '');
    }
	
	/**
     * Returns the IP address from remote service.
     *
     * @return string|null
     */
    private function getRemoteIp(): ?string
    {
		$ip = file_get_contents($this->remoteAddr);
		
		if ($this->isIpValid($ip)) {
			return $ip;
		}
		
		return null;
    }
	
    /**
     * Attempt to get the IP address for a proxied client
     *
	 * @param ServerRequestInterface $request
	 *
     * @return string|null
	 *
     * @see http://tools.ietf.org/html/draft-ietf-appsawg-http-forwarded-10#section-5.2
     */
    private function getProxiedIp(ServerRequestInterface $request): ?string
    {
        if (! $this->useProxy) {
            return null;
        }
		
        $header = $this->proxyHeader;
		
		// look for specified proxy headers
		foreach ($header as $name) {
			// does the proxy header exist in request?
            if ($request->hasHeader($name)) {
				// 1: extract ips
				$ips = explode(',', $request->getHeaderLine($name));
				// 2: trim, so we can compare against trusted proxies properly
				$ips = array_map('trim', $ips);
				// 3: remove trusted proxy ips
				$ips = array_diff($ips, $this->trustedProxies);
				
				// any left?
				if (empty($ips)) {
					return null;
				}
				
				// Since we've removed any known, trusted proxy servers, the right-most
				// address represents the first IP we do not know about -- i.e., we do
				// not know if it is a proxy server, or a client. As such, we treat it
				// as the originating IP.
				// @see http://en.wikipedia.org/wiki/X-Forwarded-For
				$ip = array_pop($ips);
				return $ip;
            }
        }
		
		return null;
    }
	
	/**
     * Returns the remote address of the request, if valid.
     *
	 * @param ServerRequestInterface $request
	 *
     * @return string|null
     */
    private function getLocalIp(ServerRequestInterface $request): ?string
    {
        $server = $request->getServerParams();

        if (! empty($server['REMOTE_ADDR']) && $this->isIpValid($server['REMOTE_ADDR'])) {
            return $server['REMOTE_ADDR'];
        }
		
		return null;
    }
	
    /**
     * Normalize a header string.
     *
     * Normalizes a header string to a format that is compatible with
     * $_SERVER.
     *
     * @param  string $header
	 *
     * @return string
     */
    private function normalizeProxyHeader(string $header): string
    {
        $header = strtoupper($header);
        $header = str_replace('-', '_', $header);
		
        if (0 !== strpos($header, 'HTTP_')) {
            $header = 'HTTP_' . $header;
        }
		
        return $header;
    }
	
	/**
     * Check that a given string is a valid IP address.
     *
     * @param string $ip
     *
     * @return bool
     */
    private function isIpValid($ip): bool
    {
        return (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false);
    }
}

?>