<?php
use Psr\Http\Message\RequestInterface;

class _DevblocksAwsService {
	static $instance = null;

	private function __construct() {}

	static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksAwsService();
		}

		return self::$instance;
	}

	/**
	 * Build a SigV4 request signer bound to a set of credentials.
	 */
	function signer(string $access_key, string $secret_key) : DevblocksAwsSigV4Signer {
		return new DevblocksAwsSigV4Signer($access_key, $secret_key);
	}

	/**
	 * Derive the AWS region from an S3 endpoint hostname (e.g. `s3.us-west-2.amazonaws.com`).
	 * Returns null for non-AWS / S3-compatible hosts that don't encode a region.
	 */
	function deriveRegionFromHost(string $host) : ?string {
		$matches = [];

		// e.g. s3.us-west-2.amazonaws.com, s3-website-us-west-2.amazonaws.com, s3.dualstack.eu-west-1.amazonaws.com
		if(preg_match('/s3[.-](?:website-|dualstack\.)?(.+)\.amazonaws\.com$/i', $host, $matches))
			return DevblocksPlatform::strLower($matches[1]);

		return null;
	}

	/**
	 * Derive the AWS service + region for SigV4 signing from a request hostname.
	 *
	 * AWS S3 endpoints (path-style, virtual-hosted, regional, legacy-dash, dualstack/accelerate)
	 * and other AWS services (`service.region.amazonaws.com`) are parsed directly. Non-AWS hosts
	 * (MinIO, ngrok, on-prem S3-compatible) are assumed to be S3 in `us-east-1` — unless $aws_only
	 * is set, in which case non-AWS hosts are rejected.
	 *
	 * @return bool false if the host is disallowed (strict mode) or unparseable
	 */
	function deriveServiceRegionFromHost(string $host, bool $aws_only=false, ?string &$service=null, ?string &$region=null) : bool {
		$service = $region = null;
		$host = DevblocksPlatform::strLower($host);
		$matches = [];

		// S3: path-style, virtual-hosted, regional, legacy-dash, dualstack/accelerate/website
		if(preg_match('/(?:^|\.)s3[.-](?:(?:dualstack|accelerate|website)[.-])?(?:([a-z0-9-]+)\.)?amazonaws\.com$/', $host, $matches)) {
			$service = 's3';
			$region = !empty($matches[1]) ? $matches[1] : 'us-east-1';

		// Other AWS services: service.region.amazonaws.com
		} else if(preg_match('#^(.*?)\.(.*?)\.amazonaws\.com$#', $host, $matches)) {
			$service = $matches[1];
			$region = $matches[2];

		// Other AWS services without a region: service.amazonaws.com
		} else if(preg_match('#^(.*?)\.amazonaws\.com$#', $host, $matches)) {
			$service = $matches[1];
			$region = 'us-east-1';

		// Non-AWS / S3-compatible (MinIO, ngrok, on-prem): assume S3 with a default region
		} else {
			if($aws_only)
				return false;

			$service = 's3';
			$region = 'us-east-1';
		}

		$service = match($service) {
			'bedrock-runtime' => 'bedrock',
			default => $service,
		};

		return !empty($service) && !empty($region);
	}
}

class DevblocksAwsSigV4Signer {
	private string $_access_key;
	private string $_secret_key;

	function __construct(string $access_key, string $secret_key) {
		$this->_access_key = $access_key;
		$this->_secret_key = $secret_key;
	}

	/**
	 * Compute the AWS SigV4 signature for a PSR-7 request.
	 *
	 * The caller is responsible for setting any headers it wants signed (e.g. `Host`,
	 * `x-amz-date`, `x-amz-content-sha256`) on the request before calling. The payload
	 * hash is passed explicitly as `$content_sha256` (a lowercase hex hash, or the literal
	 * `UNSIGNED-PAYLOAD`) so large request bodies don't have to be buffered into memory.
	 *
	 * @return array{access_key:string,authorization:string,credential_scope:string,date:string,signature:string,signed_headers:string}
	 */
	function calculate(RequestInterface $request, string $region, string $service, string $content_sha256) : array {
		if($request->hasHeader('x-amz-date')) {
			$date_iso_8601 = $request->getHeaderLine('x-amz-date');
		} else {
			$date_iso_8601 = gmdate('Ymd\THis\Z');
		}

		$canonical_path = $this->_createCanonicalPath($request->getUri()->getPath());
		$canonical_query = $this->_createCanonicalQueryString($request->getUri()->getQuery());
		$canonical_headers = $this->_createCanonicalHeaders($request->getHeaders());
		$signed_headers = $this->signedHeaders($request->getHeaders());

		$canonical_string =
			DevblocksPlatform::strUpper($request->getMethod()) . "\n" .
			$canonical_path . "\n" .
			$canonical_query . "\n" .
			$canonical_headers . "\n" .
			$signed_headers . "\n" .
			$content_sha256
			;

		$credential_scope = sprintf("%s/%s/%s/aws4_request",
			substr($date_iso_8601, 0, 8),
			$region,
			$service
		);

		$string_to_sign =
			'AWS4-HMAC-SHA256' . "\n" .
			$date_iso_8601 . "\n" .
			$credential_scope . "\n" .
			DevblocksPlatform::strLower(hash('sha256', $canonical_string))
			;

		$hash_date = hash_hmac('sha256', substr($date_iso_8601, 0, 8), 'AWS4' . $this->_secret_key, true);
		$hash_region = hash_hmac('sha256', $region, $hash_date, true);
		$hash_service = hash_hmac('sha256', $service, $hash_region, true);
		$hash_signing = hash_hmac('sha256', 'aws4_request', $hash_service, true);

		$signature = hash_hmac('sha256', $string_to_sign, $hash_signing, false);

		$auth_header = sprintf('%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
			'AWS4-HMAC-SHA256',
			$this->_access_key,
			$credential_scope,
			$signed_headers,
			$signature
		);

		return [
			'access_key' => $this->_access_key,
			'authorization' => $auth_header,
			'credential_scope' => $credential_scope,
			'date' => $date_iso_8601,
			'signature' => $signature,
			'signed_headers' => $signed_headers,
		];
	}

	/**
	 * Sign a PSR-7 request, returning a new request with the `Authorization` (and, if missing,
	 * `X-Amz-Date`) headers added.
	 */
	function sign(RequestInterface $request, string $region, string $service, string $content_sha256) : RequestInterface {
		$result = $this->calculate($request, $region, $service, $content_sha256);

		$request = $request->withHeader('Authorization', $result['authorization']);

		if(!$request->hasHeader('x-amz-date'))
			$request = $request->withHeader('X-Amz-Date', $result['date']);

		return $request;
	}

	function signedHeaders(array $headers) : string {
		$signed_headers = [];

		foreach(array_keys($headers) as $key) {
			$signed_headers[] = DevblocksPlatform::strLower(trim($key));
		}

		sort($signed_headers, SORT_STRING | SORT_FLAG_CASE);

		return implode(';', $signed_headers);
	}

	private function _createCanonicalPath($path=null) {
		$path = $path ?: '/';
		$path_parts = explode('/', $path);

		foreach($path_parts as &$segment)
			$segment = rawurlencode($segment);
		unset($segment);

		return implode('/', $path_parts);
	}

	private function _createCanonicalQueryString($query=null) {
		$query = $query ?: '';
		$query_parts = DevblocksPlatform::strParseQueryString($query);

		ksort($query_parts, SORT_STRING);

		return http_build_query($query_parts, '', '&', PHP_QUERY_RFC3986);
	}

	private function _createCanonicalHeaders($headers) {
		$canonical_headers = '';

		ksort($headers, SORT_STRING | SORT_FLAG_CASE);

		foreach($headers as $key => $vals) {
			$canonical_headers .= DevblocksPlatform::strLower(trim($key)) . ':' . trim(implode(',', $vals)) . "\n";
		}

		return $canonical_headers;
	}
}
