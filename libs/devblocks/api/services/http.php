<?php

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface as ResponseInterfaceAlias;

/**
 * Where an outbound HTTP request is allowed to land.
 *
 * Internal destinations are deliberately NOT blocked. Pointing an LLM endpoint at a
 * self-hosted Ollama on localhost, a webhook at a 10.x service, or an automation at an
 * internal API are all supported configurations -- Ollama and Omlx even ship loopback
 * defaults -- so a private-range filter would break working installs to little end. What IS
 * blocked is the small set of addresses that hand out CREDENTIALS to anything able to make
 * this server issue a request: cloud instance metadata.
 *
 * Two of those would be MISSED by a conventional private-range filter, which is why this is
 * a targeted list rather than a narrowed one: Azure's WireServer (168.63.129.16) and
 * Alibaba's metadata service (100.100.100.200) both sit in PUBLIC address space.
 *
 * ️ **Everything here matches on the RESOLVED address, never on the URL string.** That is
 * what collapses `127.1`-style shorthand, decimal (`http://2852039166/`), octal
 * (`0251.0376.0251.0376`), IPv4-inside-IPv6, and attacker-controlled DNS
 * (`evil.com IN A 169.254.169.254`) into a single test, instead of a string-pattern arms
 * race that loses to the next encoding.
 */
class DevblocksHttpEgressFilter {
	/**
	 * How many IPv6 prefixes carry an IPv4 address in their low 32 bits. Judged on the address
	 * packets actually reach, since each of these routes to the embedded v4 host while matching
	 * none of the v6 CIDRs in DEVBLOCKS_HTTP_BLOCK_CIDRS.
	 */
	const V4_IN_V6_PREFIXES = [
		'::ffff:0:0/96', // IPv4-mapped -- `http://[::ffff:169.254.169.254]/`
		'::/96',         // IPv4-compatible (deprecated, still routed by some stacks)
		'64:ff9b::/96',  // NAT64 / RFC 6052
	];
	
	const MAX_REDIRECTS = 10;

	/**
	 * A Guzzle request option. When true, the request (and every redirect hop) may only connect
	 * to public addresses. See NON_PUBLIC_CIDRS.
	 */
	const OPT_PUBLIC_ONLY = 'devblocks_public_only';

	/**
	 * Everything that isn't the public internet. NOT applied to outbound requests in general --
	 * see the class comment -- only where a caller opts in with isPublicIp() because the feature
	 * has no business reaching an internal host (e.g. a worker fetching an avatar image by URL).
	 */
	const NON_PUBLIC_CIDRS = [
		'0.0.0.0/8',          // "This" network
		'10.0.0.0/8',         // RFC1918
		'100.64.0.0/10',      // CGNAT (RFC 6598)
		'127.0.0.0/8',        // Loopback
		'169.254.0.0/16',     // Link-local
		'172.16.0.0/12',      // RFC1918
		'192.0.0.0/24',       // IETF protocol assignments
		'192.168.0.0/16',     // RFC1918
		'198.18.0.0/15',      // Benchmarking
		'224.0.0.0/4',        // Multicast
		'240.0.0.0/4',        // Reserved, and 255.255.255.255 broadcast
		'::/128',             // Unspecified
		'::1/128',            // Loopback
		'fc00::/7',           // Unique local
		'fe80::/10',          // Link-local
		'ff00::/8',           // Multicast
	];
	
	/**
	 * The blocklist from DEVBLOCKS_HTTP_BLOCK_CIDRS, whose default in framework.defaults.php
	 * is the cloud credential endpoints.
	 */
	static function getBlockedCidrs() : array {
		if(defined('DEVBLOCKS_HTTP_BLOCK_CIDRS') && is_array(DEVBLOCKS_HTTP_BLOCK_CIDRS))
			return DEVBLOCKS_HTTP_BLOCK_CIDRS;

		return [];
	}
	
	/**
	 * Is $ip inside $cidr? Compares the packed forms bit by bit, so it works for both
	 * families without any 32-bit integer math (ip2long() can't express IPv6 at all).
	 *
	 * @test DevblocksHttpEgressFilterTest
	 */
	static function isCidrMatch(string $ip, string $cidr) : bool {
		$parts = explode('/', $cidr, 2);
		
		if(false === ($net_bin = @inet_pton($parts[0])))
			return false;
		
		if(false === ($ip_bin = @inet_pton($ip)))
			return false;
		
		// Different families never match. inet_pton() returns 4 bytes for IPv4 and 16 for IPv6,
		// so the length IS the family -- comparing across them would read past the shorter one.
		if(strlen($net_bin) != strlen($ip_bin))
			return false;
		
		$width = strlen($net_bin) * 8;
		$bits = array_key_exists(1, $parts) ? intval($parts[1]) : $width;
		$bits = max(0, min($width, $bits));
		
		$whole_bytes = intdiv($bits, 8);
		$odd_bits = $bits % 8;
		
		if($whole_bytes && 0 !== strncmp($ip_bin, $net_bin, $whole_bytes))
			return false;
		
		// A prefix that doesn't land on a byte boundary (e.g. /10 for link-local v6) leaves a
		// partial byte to compare under a mask.
		if($odd_bits) {
			$mask = chr((0xFF << (8 - $odd_bits)) & 0xFF);
			
			if(($ip_bin[$whole_bytes] & $mask) !== ($net_bin[$whole_bytes] & $mask))
				return false;
		}
		
		return true;
	}
	
	/**
	 * The address this one really reaches: an IPv6 address carrying an IPv4 one becomes that
	 * IPv4 address. Returns null if $ip isn't an address at all.
	 *
	 * @test DevblocksHttpEgressFilterTest
	 */
	static function normalizeIp(string $ip) : ?string {
		if(false === ($bin = @inet_pton($ip)))
			return null;
		
		if(4 == strlen($bin))
			return inet_ntop($bin);
		
		foreach(self::V4_IN_V6_PREFIXES as $prefix) {
			if(self::isCidrMatch($ip, $prefix))
				return inet_ntop(substr($bin, 12, 4));
		}
		
		// 6to4 carries the v4 address in bytes 2-5: 2002:V4V4:V4V4::/16
		if(self::isCidrMatch($ip, '2002::/16'))
			return inet_ntop(substr($bin, 2, 4));
		
		return inet_ntop($bin);
	}
	
	/**
	 * Is this address a cloud credential endpoint (or otherwise blocked by config)?
	 *
	 * Both the address as given and its unwrapped form are checked, so an IPv4-mapped address
	 * can't slip past a v4 rule and a native v6 address can't slip past a v6 one.
	 *
	 * @test DevblocksHttpEgressFilterTest
	 */
	static function isBlockedIp(string $ip) : bool {
		$candidates = [];
		
		if(false !== @inet_pton($ip))
			$candidates[$ip] = true;
		
		if(!is_null($normalized = self::normalizeIp($ip)))
			$candidates[$normalized] = true;
		
		foreach(self::getBlockedCidrs() as $cidr) {
			foreach(array_keys($candidates) as $candidate) {
				if(self::isCidrMatch($candidate, $cidr))
					return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Is $ip on the public internet? Like isBlockedIp(), both the address as given and its
	 * unwrapped form are checked, so `::ffff:10.0.0.1` and `64:ff9b::a00:1` are as private as
	 * `10.0.0.1`. Anything that isn't an address at all is not public.
	 *
	 * @test DevblocksHttpEgressFilterTest
	 */
	static function isPublicIp(string $ip) : bool {
		if(false === @inet_pton($ip))
			return false;
		
		$candidates = [$ip => true];
		
		if(!is_null($normalized = self::normalizeIp($ip)))
			$candidates[$normalized] = true;
		
		foreach(self::NON_PUBLIC_CIDRS as $cidr) {
			foreach(array_keys($candidates) as $candidate) {
				if(self::isCidrMatch($candidate, $cidr))
					return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Every address a hostname resolves to, or the host itself when it's already a literal.
	 *
	 * A literal is returned as-is rather than handed to a resolver on purpose: glibc accepts
	 * `2130706433` and `0177.0.0.1` through inet_aton() and would answer with an address we
	 * never vetted. Anything inet_pton() rejects goes to DNS, which is also what turns those
	 * same legacy forms into the real address when curl would have accepted them.
	 */
	static function resolveHost(string $host) : array {
		if(false !== @inet_pton($host))
			return [$host];
		
		$ips = [];
		
		if(is_array($v4 = @gethostbynamel($host)))
			$ips = array_merge($ips, $v4);
		
		// gethostbynamel() is IPv4-only, so AAAA needs its own lookup -- an IPv6-only host would
		// otherwise come back empty and skip the check entirely.
		if(is_array($records = @dns_get_record($host, DNS_AAAA))) {
			foreach($records as $record) {
				if($record['ipv6'] ?? null)
					$ips[] = $record['ipv6'];
			}
		}
		
		return array_values(array_unique($ips));
	}
	
	/**
	 * Check a URL's destination before anything connects to it.
	 *
	 * @param string $url
	 * @param string|null $error Why it was refused
	 * @return array|null ['scheme','host','port','ips'] when allowed, null when refused
	 * @test DevblocksHttpEgressFilterTest
	 */
	static function vetUrl(string $url, ?string &$error=null) : ?array {
		$error = null;
		
		if(!is_array($parts = @parse_url($url))) {
			$error = 'the URL could not be parsed';
			return null;
		}
		
		// Scheme before host: a `file:///etc/passwd` has no host at all, and reporting that as an
		// unparseable URL would hide the reason it was really refused.
		$scheme = strtolower(strval($parts['scheme'] ?? ''));
		
		if(!in_array($scheme, ['http', 'https'])) {
			$error = sprintf('the `%s` scheme is not allowed (http and https only)', $scheme);
			return null;
		}
		
		if(!($host = $parts['host'] ?? null)) {
			$error = 'the URL has no host';
			return null;
		}
		
		// parse_url() keeps the brackets on an IPv6 literal, which inet_pton() won't take.
		$host = trim($host, '[]');
		$port = intval($parts['port'] ?? 0) ?: ('https' == $scheme ? 443 : 80);
		$ips = self::resolveHost($host);
		
		foreach($ips as $ip) {
			// Refuse if ANY address is blocked rather than picking a permitted one. A host whose
			// record set mixes a public address with 169.254.169.254 would otherwise be a free
			// bypass, since nothing says which one curl picks.
			if(self::isBlockedIp($ip)) {
				$error = sprintf('%s resolves to %s, a cloud credential endpoint', $host, $ip);
				return null;
			}
		}
		
		// An unresolvable host is NOT refused. Let curl fail on its own: this filter exists to
		// keep credentials in, not to second-guess DNS, and failing closed on a lookup hiccup
		// would turn a transient resolver problem into a broken integration.
		return [
			'scheme' => $scheme,
			'host' => $host,
			'port' => $port,
			'ips' => $ips,
		];
	}
}

class _DevblocksHttpService {
	static $instance = null;
	private static $_client = null;
	
	private function __construct() {}
	
	static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksHttpService();
		}
		
		return self::$instance;
	}
	
	private function _configure_defaults() {
		return function(callable $handler) {
			return function (RequestInterface $request, array $options) use ($handler) {
				if(!array_key_exists(RequestOptions::HTTP_ERRORS, $options))
					$options[RequestOptions::HTTP_ERRORS] = false;
				
				if(!array_key_exists(RequestOptions::CONNECT_TIMEOUT, $options))
					$options[RequestOptions::CONNECT_TIMEOUT] = 10;
				
				if(!array_key_exists(RequestOptions::TIMEOUT, $options))
					$options[RequestOptions::TIMEOUT] = 30;
				
				$is_proxied = defined('DEVBLOCKS_HTTP_PROXY') && DEVBLOCKS_HTTP_PROXY;
				
				if($is_proxied) {
					$options[RequestOptions::PROXY] = [
						'http' => DEVBLOCKS_HTTP_PROXY,
						'https' => DEVBLOCKS_HTTP_PROXY,
					];
				}
				
				// Refuse cloud credential endpoints, and keep redirect handling where this check can
				// see it.
				//
				// ️ **This runs on EVERY redirect hop, and that is why it lives here.** HandlerStack
				// composes with array_reverse(), so the last middleware pushed is the INNERMOST one --
				// this sits below `allow_redirects`, and RedirectMiddleware re-invokes its nextHandler
				// per hop. Redirects are followed by default on every path through this service and
				// nothing else re-validates the target, so a check made only at the call site would be
				// one 302 away from useless. Anything moved above `allow_redirects` loses that.
				if(!($vetted = DevblocksHttpEgressFilter::vetUrl(strval($request->getUri()), $error))) {
					// A rejected promise rather than a throw: a synchronous throw inside middleware
					// escapes sendAsync() instead of rejecting its promise, which would bypass the
					// multiplexer's error handling entirely. RequestException keeps this catchable by
					// the handlers callers already have.
					return \GuzzleHttp\Promise\Create::rejectionFor(
						new RequestException(
							sprintf('Refused to request %s: %s',
								$request->getUri(),
								$error
							),
							$request
						)
					);
				}

				// Callers opting in to public-only destinations get the same per-hop check. Unlike the
				// check above, an unresolvable host is refused, since it has no address to vet or pin.
				if($options[DevblocksHttpEgressFilter::OPT_PUBLIC_ONLY] ?? false) {
					$is_public = !empty($vetted['ips']);

					foreach($vetted['ips'] as $ip) {
						if(!DevblocksHttpEgressFilter::isPublicIp($ip))
							$is_public = false;
					}

					if(!$is_public) {
						return \GuzzleHttp\Promise\Create::rejectionFor(
							new RequestException(
								sprintf('Refused to request %s: only public hosts are allowed',
									$request->getUri()
								),
								$request
							)
						);
					}
				}

				$options['curl'] = $options['curl'] ?? [];

				// ️ **ASSIGNED, not unioned -- the opposite of the keepalive defaults above.** That block
				// deliberately lets a caller's value win, which is right for a timeout and wrong for the
				// egress control itself: with `+` the caller's key survives and silently disables this.
				// Both of these are exploitable that way, not theoretical.
				//
				// Nothing restricted the protocol on this path before, so a scheme that slipped past
				// validation reached file://, gopher:// and dict://.
				// PHP 8.2 lacks CURLOPT_PROTOCOLS_STR (8.3+), so fall back to the bitmask there
				if(defined('CURLOPT_PROTOCOLS_STR')) {
					$options['curl'][constant('CURLOPT_PROTOCOLS_STR')] = 'http,https';
				} else {
					$options['curl'][CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
				}

				// Redirects stay Guzzle's job. A caller passing CURLOPT_FOLLOWLOCATION => true would
				// have curl follow hops itself, BELOW this middleware, where nothing re-checks them --
				// exactly the hole described above.
				$options['curl'][CURLOPT_FOLLOWLOCATION] = false;

				// Pin the address we vetted, closing the window between our lookup and curl's own in
				// which DNS can be re-pointed at the metadata service. Host and SNI stay the hostname,
				// so this is invisible to the server.
				//
				// Skipped when proxied: the proxy resolves and connects, so CURLOPT_RESOLVE is inert
				// and the proxy's own ACL is the real control there. The check above still runs, and
				// the proxy's address is never itself filtered -- a proxy on an RFC1918 IP is normal.
				if(!$is_proxied && $vetted['ips']) {
					// Every vetted address, not just the first: curl accepts a comma-separated list and
					// falls through them in order, so a load-balanced host keeps its failover instead of
					// being pinned to one machine that might be down.
					$addresses = array_map(
						fn($ip) => str_contains($ip, ':') ? sprintf('[%s]', $ip) : $ip,
						$vetted['ips']
					);

					// Assigned for the same reason: a caller-supplied CURLOPT_RESOLVE would otherwise map
					// a hostname that passed the check straight onto 169.254.169.254, making the vetting
					// above decorative.
					$options['curl'][CURLOPT_RESOLVE] = [
						sprintf('%s:%d:%s',
							$vetted['host'],
							$vetted['port'],
							implode(',', $addresses)
						),
					];
					
				} else if(!$is_proxied) {
					// Host didn't resolve, so there is nothing of ours to pin. Drop any mapping the
					// caller supplied rather than letting it stand unchecked -- vetUrl() passes an
					// unresolvable host through, so a caller's entry would be the only thing deciding
					// where this connects.
					unset($options['curl'][CURLOPT_RESOLVE]);
				}
				
				return $handler($request, $options);
			};
		};
	}
	
	function getClient() : GuzzleHttp\Client {
		if(self::$_client)
			return self::$_client;
		
		$handler = new CurlHandler();
		$stack = HandlerStack::create($handler);
		
		// Register proxy middleware
		$stack->push($this->_configure_defaults());
		
		self::$_client = new GuzzleHttp\Client(['handler' => $stack]);
		
		return self::$_client;
	}
	
	/**
	 *
	 * @param RequestInterface $request
	 * @param array $options
	 * @param null $error
	 * @param null $error_response
	 * @return ResponseInterfaceAlias|false
	 */
	function sendRequest(RequestInterface $request, array $options=[], &$error=null, &$error_response=null) {
		$client = $this->getClient();
		
		$error = '';
		$error_response = null;
		
		try {
			return $client->send($request, $options);
			
		} catch (RequestException $e) {
			$error = $e->getMessage();
			
			if($e->hasResponse())
				$error_response = $e->getResponse();
			
			return false;
			
		} catch (GuzzleException $e) {
			$error = get_class($e);
			DevblocksPlatform::logException($e);
			return false;
		}
	}
	
	/**
	 * 
	 * @param ResponseInterface $response
	 * @param string $error
	 * @return mixed|false
	 */
	function getResponseAsJson(ResponseInterface $response, &$error=null) {
		$contents = $response->getBody()->getContents();
		
		if(false === ($json = @json_decode($contents, true))) {
			$error = json_last_error();
			return false;
		}
		
		return $json;
	}
	
	public function setHeader(string $name, string $value, $replace=true) : _DevblocksHttpService {
		header(
			sprintf("%s: %s",
				DevblocksPlatform::services()->string()->strStripCrlf($name),
				DevblocksPlatform::services()->string()->strStripCrlf($value)
			),
			$replace
		);
		return $this;
	}
}