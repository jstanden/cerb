<?php

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface as ResponseInterfaceAlias;

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
				
				if(defined('DEVBLOCKS_HTTP_PROXY') && DEVBLOCKS_HTTP_PROXY) {
					$options[RequestOptions::PROXY] = [
						'http' => DEVBLOCKS_HTTP_PROXY,
						'https' => DEVBLOCKS_HTTP_PROXY,
					];
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
	
	/**
	 * Send a request whose response body is Server-Sent Events, handing each event to $on_event AS IT
	 * ARRIVES rather than buffering the whole body. Separate from sendRequest() on purpose: every other
	 * caller depends on that method's buffer-then-return semantics.
	 *
	 * THE TIMEOUT MODEL IS THE POINT. A normal request gets a TOTAL cap, which is a wall-clock guillotine
	 * that kills a healthy-but-slow response (this is exactly how a 5-minute LLM turn died mid-generation
	 * after we'd already paid for its tokens). A stream instead gets:
	 *   - `timeout`: an absolute backstop, deliberately generous. It MUST be passed explicitly — omitting
	 *     it does NOT mean "no limit", it means _configure_defaults() injects 30s.
	 *   - CURLOPT_LOW_SPEED_LIMIT/_TIME: an INACTIVITY cutoff, which is the real control. It separates
	 *     "long" from "stuck" — the distinction a total timeout cannot express.
	 * Note LOW_SPEED_TIME is an AVERAGE window, not a silence timer: bytes already received keep the
	 * trailing average up for a while after the stream goes quiet, so the effective cutoff lands well
	 * after the configured value. Treat it as a floor.
	 *
	 * @param callable $on_event fn(string $event, string $data) : bool — return false to abort the transfer
	 * @param bool $aborted set true only when WE stopped the transfer, false for any other failure
	 * @return ResponseInterfaceAlias|false
	 */
	function sendStreamingRequest(RequestInterface $request, array $options, callable $on_event, &$error=null, &$aborted=null) {
		$error = '';
		$aborted = false;

		$sink = new DevblocksHttpSseSink($on_event);

		$options['sink'] = $sink;

		// A non-2xx doesn't return SSE, it returns an error document. Without this the sink would try to
		// parse it as events, find none, and hand the caller an empty stream instead of the reason why.
		$options['on_headers'] = function(ResponseInterface $response) use ($sink) {
			$sink->setPassthrough(200 != $response->getStatusCode());
		};

		try {
			return $this->getClient()->send($request, $options);

		} catch (RequestException $e) {
			// A deliberate abort surfaces here too (curl 23, "Failure writing output to destination"),
			// indistinguishable from a real write failure by class. The sink's own flag is the signal.
			$aborted = $sink->isAborted();
			$error = $e->getMessage();
			return false;

		} catch (GuzzleException $e) {
			$aborted = $sink->isAborted();
			$error = get_class($e) . ': ' . $e->getMessage();
			return false;
		}
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

/**
 * A write-only PSR-7 stream that Guzzle's CurlHandler writes response bytes into AS THEY ARRIVE
 * (CurlFactory sets CURLOPT_WRITEFUNCTION => $sink->write()), which is what makes incremental
 * Server-Sent Event parsing possible without swapping handlers or adding a dependency.
 *
 * Two behaviors carry the weight:
 *
 *   - A frame is consumed only when COMPLETE. curl chunk boundaries fall wherever they like, so an SSE
 *     frame routinely arrives split mid-JSON; anything not yet terminated by a blank line stays
 *     buffered for the next write. Parsing per-write instead would silently corrupt events.
 *
 *   - Returning fewer bytes than we were handed ABORTS the transfer (curl CURLE_WRITE_ERROR). That's how
 *     a caller stops a stream mid-flight. `_aborted` records that *we* did it, because a deliberate abort
 *     and a dropped connection both surface as exceptions and the curl error code alone is too brittle
 *     to tell them apart — and the difference decides whether the work is reported as done or failed.
 *
 * In passthrough mode (a non-2xx response, which returns an error document rather than events) it stops
 * parsing and just buffers, so `$response->getBody()->getContents()` and getResponseAsJson() behave
 * exactly as they do for an ordinary request.
 */
class DevblocksHttpSseSink implements StreamInterface {
	private $_on_event;
	private string $_buffer = '';
	private string $_raw = '';
	private int $_read_pos = 0;
	private bool $_passthrough = false;
	private bool $_aborted = false;

	function __construct(callable $on_event) {
		$this->_on_event = $on_event;
	}

	public function setPassthrough(bool $passthrough) : void {
		$this->_passthrough = $passthrough;
	}

	public function isAborted() : bool {
		return $this->_aborted;
	}

	public function write(string $string): int {
		$len = strlen($string);

		// Never short-return on an empty write; curl would read it as an abort.
		if(0 == $len)
			return 0;

		if($this->_passthrough || $this->_aborted) {
			$this->_raw .= $string;
			return $len;
		}

		// Normalize CRLF so a server using \r\n\r\n frame terminators parses the same. Safe for JSON
		// payloads, where a literal CR is escaped rather than raw.
		$this->_buffer .= str_replace("\r\n", "\n", $string);

		while(false !== ($pos = strpos($this->_buffer, "\n\n"))) {
			$frame = substr($this->_buffer, 0, $pos);
			$this->_buffer = substr($this->_buffer, $pos + 2);

			$event = '';
			$data = [];

			foreach(explode("\n", $frame) as $line) {
				// Comment/heartbeat line
				if('' === $line || str_starts_with($line, ':'))
					continue;

				if(str_starts_with($line, 'event:')) {
					$event = trim(substr($line, 6));
				} elseif(str_starts_with($line, 'data:')) {
					// Per the SSE spec, repeated `data:` lines in one frame join with a newline.
					$data[] = ltrim(substr($line, 5), ' ');
				}
			}

			if('' === $event && !$data)
				continue;

			if(false === ($this->_on_event)($event, implode("\n", $data))) {
				$this->_aborted = true;
				return 0;
			}
		}

		return $len;
	}

	/**
	 * In passthrough (a non-2xx error document) this is the buffered body. Otherwise it's whatever arrived
	 * that could NOT be consumed as complete SSE frames — which, when nothing parsed at all, is the entire
	 * body. That's the seam a caller uses to recover when it asked for a stream and got an ordinary response:
	 * an endpoint that ignores `stream`, a proxy that buffers, a gateway that answers in its own format.
	 * Without it a non-SSE 200 reads as a successful, silently empty turn.
	 */
	private function _readable() : string {
		return $this->_passthrough ? $this->_raw : $this->_buffer;
	}

	public function __toString(): string {
		return $this->_readable();
	}

	public function getContents(): string {
		$contents = substr($this->_readable(), $this->_read_pos);
		$this->_read_pos = strlen($this->_readable());
		return $contents;
	}

	public function read(int $length): string {
		$chunk = substr($this->_readable(), $this->_read_pos, $length);
		$this->_read_pos += strlen($chunk);
		return $chunk;
	}

	public function eof(): bool {
		return $this->_read_pos >= strlen($this->_readable());
	}

	public function getSize(): ?int {
		return strlen($this->_readable());
	}

	public function tell(): int {
		return $this->_read_pos;
	}

	public function rewind(): void {
		$this->_read_pos = 0;
	}

	public function seek(int $offset, int $whence = SEEK_SET): void {
		// Only the rewind case is meaningful for a sink; anything else is a no-op by design.
		if(SEEK_SET === $whence)
			$this->_read_pos = max(0, $offset);
	}

	public function close(): void {}

	public function detach() {
		return null;
	}

	public function isSeekable(): bool {
		return false;
	}

	public function isWritable(): bool {
		return true;
	}

	public function isReadable(): bool {
		return true;
	}

	public function getMetadata(?string $key = null) {
		return is_null($key) ? [] : null;
	}
}