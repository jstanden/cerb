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
			// Keep the message. A ConnectException isn't a RequestException, so it lands here -- and the class
			// name alone ("GuzzleHttp\Exception\ConnectException") tells a caller nothing about which host or
			// why. Matches _sendWithSink() below.
			$error = get_class($e) . ': ' . $e->getMessage();
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
		return $this->_sendWithSink($request, $options, new DevblocksHttpSseSink($on_event), $error, $aborted);
	}

	/**
	 * The same contract as sendStreamingRequest(), for a response body in AWS's binary
	 * `application/vnd.amazon.eventstream` framing rather than Server-Sent Events. AWS Bedrock's
	 * ConverseStream speaks it.
	 *
	 * Separate entry point rather than a flag: the two differ ONLY in the decoder, and both hand the same
	 * `fn(string $event, string $data) : bool` upward, so a provider's accumulator does not care which
	 * transport delivered it.
	 *
	 * @param callable $on_event fn(string $event, string $data) : bool — return false to abort the transfer
	 * @param bool $aborted set true only when WE stopped the transfer, false for any other failure
	 * @return ResponseInterfaceAlias|false
	 */
	function sendEventStreamRequest(RequestInterface $request, array $options, callable $on_event, &$error=null, &$aborted=null) {
		return $this->_sendWithSink($request, $options, new DevblocksHttpAwsEventStreamSink($on_event), $error, $aborted);
	}

	/**
	 * The same contract again, for newline-delimited JSON (NDJSON) — one complete JSON object per line,
	 * with no event names and no frame header. Ollama's `/api/chat` speaks it.
	 *
	 * There is no event name to report, so every frame arrives as `('', $json)`. That is the same shape the
	 * OpenAI family's accumulator already handles, since its SSE frames carry no `event:` line either.
	 *
	 * @param callable $on_event fn(string $event, string $data) : bool — return false to abort the transfer
	 * @param bool $aborted set true only when WE stopped the transfer, false for any other failure
	 * @return ResponseInterfaceAlias|false
	 */
	function sendNdjsonStreamRequest(RequestInterface $request, array $options, callable $on_event, &$error=null, &$aborted=null) {
		return $this->_sendWithSink($request, $options, new DevblocksHttpNdjsonSink($on_event), $error, $aborted);
	}

	/**
	 * Shared transport for the streaming sinks. Everything except the decoder is identical, and the two
	 * safety behaviours below are the reason this isn't inlined per sink.
	 *
	 * @param DevblocksHttpSseSink|DevblocksHttpAwsEventStreamSink|DevblocksHttpNdjsonSink $sink
	 * @return ResponseInterfaceAlias|false
	 */
	private function _sendWithSink(RequestInterface $request, array $options, $sink, &$error=null, &$aborted=null) {
		$error = '';
		$aborted = false;

		$options['sink'] = $sink;

		// A non-2xx doesn't return a stream, it returns an error document. Without this the sink would try
		// to decode it as frames, find none, and hand the caller an empty stream instead of the reason why.
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
/**
 * A write-only PSR-7 stream that decodes AWS's binary `application/vnd.amazon.eventstream` framing
 * incrementally, handing each frame upward as `(event, payload_json)` -- the SAME callback contract
 * DevblocksHttpSseSink uses, so a provider's accumulator is transport-agnostic.
 *
 * Frame layout, all integers BIG-ENDIAN:
 *
 *   [total_len:u32][headers_len:u32][prelude_crc:u32][headers][payload][message_crc:u32]
 *
 * `total_len` counts the WHOLE frame including both CRCs, so payload_len = total_len - headers_len - 16.
 * A header entry is `[name_len:u8][name][value_type:u8][value]`, and while streaming only needs the
 * string type (7), every type has to be SKIPPABLE by length or one unexpected header desynchronizes the
 * rest of the buffer.
 *
 * **CRCs are deliberately NOT validated.** curl already guarantees TCP integrity, and a mismatch here
 * would leave us no better recovery than the length-framing already provides. Stated rather than silently
 * skipped, because "the CRC is ignored" is exactly the kind of thing that should be a decision on the
 * record.
 */
class DevblocksHttpAwsEventStreamSink implements StreamInterface {
	// The fixed bytes around a frame: 3 prelude u32s + the trailing message CRC u32.
	const FRAME_OVERHEAD = 16;
	const PRELUDE_LEN = 12;

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

		$this->_buffer .= $string;

		while(true) {
			// Not even a prelude yet -- wait for more bytes rather than guessing at a length.
			if(strlen($this->_buffer) < self::PRELUDE_LEN)
				break;

			$prelude = unpack('Ntotal/Nheaders', substr($this->_buffer, 0, 8));
			$total_len = intval($prelude['total'] ?? 0);
			$headers_len = intval($prelude['headers'] ?? 0);

			// A frame that can't be described by its own prelude means we've lost sync, and no amount of
			// further buffering recovers it. Stop decoding and let the caller's no-events path handle it;
			// what's left stays readable as unconsumed bytes.
			if(
				$total_len < self::FRAME_OVERHEAD
				|| $headers_len < 0
				|| $headers_len > $total_len - self::FRAME_OVERHEAD
			) break;

			// INCOMPLETE FRAME -- hold everything. curl chunk boundaries fall wherever they like, so a
			// frame routinely arrives split mid-header or mid-payload; decoding early corrupts it.
			if(strlen($this->_buffer) < $total_len)
				break;

			$frame = substr($this->_buffer, 0, $total_len);
			$this->_buffer = substr($this->_buffer, $total_len);

			$headers = $this->_parseHeaders(substr($frame, self::PRELUDE_LEN, $headers_len));
			$payload = substr($frame, self::PRELUDE_LEN + $headers_len, $total_len - $headers_len - self::FRAME_OVERHEAD);

			// An `exception` message-type carries the fault in `:exception-type` and must NOT read as a
			// clean end of stream. Emitting that name as the event keeps one callback shape; Bedrock's
			// exception names are distinctive (`modelStreamErrorException`, `throttlingException`), which
			// is what lets the accumulator tell them from ordinary events.
			$event = strval($headers[':event-type'] ?? $headers[':exception-type'] ?? $headers[':error-code'] ?? '');

			if('' === $event && '' === $payload)
				continue;

			if(false === ($this->_on_event)($event, $payload)) {
				$this->_aborted = true;
				return 0;
			}
		}

		return $len;
	}

	/**
	 * Decode the header block. Only string values are used, but every type must be skipped by its correct
	 * width -- a single mis-skipped header would misread every header after it.
	 *
	 * @return array<string,string> only the string-valued headers
	 */
	private function _parseHeaders(string $bytes) : array {
		$out = [];
		$at = 0;
		$len = strlen($bytes);

		while($at < $len) {
			$name_len = ord($bytes[$at]);
			$at++;

			if($at + $name_len + 1 > $len)
				break;

			$name = substr($bytes, $at, $name_len);
			$at += $name_len;

			$type = ord($bytes[$at]);
			$at++;

			switch($type) {
				case 0: // bool true
				case 1: // bool false
					break;
				case 2: // byte
					$at += 1;
					break;
				case 3: // short
					$at += 2;
					break;
				case 4: // integer
					$at += 4;
					break;
				case 5: // long
				case 8: // timestamp
					$at += 8;
					break;
				case 6: // byte array
				case 7: // string
					if($at + 2 > $len)
						return $out;

					$value_len = unpack('n', substr($bytes, $at, 2))[1] ?? 0;
					$at += 2;

					if(7 === $type)
						$out[$name] = substr($bytes, $at, $value_len);

					$at += $value_len;
					break;
				case 9: // uuid
					$at += 16;
					break;
				default:
					// An unknown type has no length we can trust, so skipping would desync everything
					// after it. Return what we have.
					return $out;
			}
		}

		return $out;
	}

	/**
	 * In passthrough (a non-2xx error document) this is the buffered body. Otherwise it's whatever arrived
	 * that could NOT be consumed as complete frames -- which, when nothing decoded at all, is the entire
	 * body. That's the seam a caller uses to recover when it asked for a stream and got an ordinary
	 * response instead.
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

/**
 * A write-only PSR-7 stream that decodes newline-delimited JSON (NDJSON) incrementally, handing each
 * object upward as `('', $json)` -- the SAME callback contract DevblocksHttpSseSink uses, so a provider's
 * accumulator does not care which transport delivered it. Ollama's `/api/chat` streams in this format.
 *
 * There is no framing here beyond the newline, which makes WELL-FORMEDNESS the completeness test: a line
 * is consumed only once it parses as a JSON object. That single rule covers the three ways bytes arrive:
 *
 *   - A line split across curl chunk boundaries doesn't parse yet, so it stays buffered (the NDJSON
 *     equivalent of the SSE sink refusing to emit a frame before its blank-line terminator).
 *
 *   - A FINAL object with no trailing newline is still emitted, because the leftover buffer is decode-tested
 *     after the line loop. That matters more here than it looks: Ollama carries `done_reason` and the whole
 *     token accounting on the last chunk alone, so dropping it would silently cost every streamed turn its
 *     usage and finish reason.
 *
 *   - A line that never parses is retained as unconsumed bytes rather than emitted as a broken frame, which
 *     is what lets a NON-streamed answer (an endpoint ignoring `stream`, a proxy that buffers and
 *     pretty-prints) surface intact through getContents() for the caller's degrade-to-blocking fallback.
 */
class DevblocksHttpNdjsonSink implements StreamInterface {
	private $_on_event;
	private string $_buffer = '';
	private string $_unconsumed = '';
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

		$this->_buffer .= $string;

		while(false !== ($pos = strpos($this->_buffer, "\n"))) {
			$line = substr($this->_buffer, 0, $pos);
			$this->_buffer = substr($this->_buffer, $pos + 1);

			if(!$this->_emit($line))
				return 0;
		}

		// A complete trailing object that hasn't been newline-terminated yet. Emitting it early is harmless
		// (it is the same object, just sooner) and it is the only way the last chunk of a stream that ends
		// without a newline is ever seen. Incomplete JSON can't parse, so it stays buffered.
		if('' !== trim($this->_buffer)) {
			if(is_array(json_decode($this->_buffer, true))) {
				$line = $this->_buffer;
				$this->_buffer = '';

				if(!$this->_emit($line))
					return 0;
			}
		}

		return $len;
	}

	/**
	 * Hand one line upward if it is a complete JSON object; otherwise keep it as unconsumed bytes.
	 *
	 * @return bool false when the caller aborted the transfer
	 */
	private function _emit(string $line) : bool {
		// CRLF is stripped HERE rather than by normalizing each write, because a curl chunk boundary can
		// fall between the \r and the \n -- which leaves that one pair un-normalized and welds a stray \r
		// onto the end of the frame. Both call sites below route through here for the same reason.
		$line = rtrim($line, "\r");

		if('' === trim($line))
			return true;

		// Not an object (yet) -- retain it, newline included, so an entire non-streamed body reassembles
		// for the caller's fallback.
		if(!is_array(json_decode($line, true))) {
			$this->_unconsumed .= $line . "\n";
			return true;
		}

		if(false === ($this->_on_event)('', $line)) {
			$this->_aborted = true;
			return false;
		}

		return true;
	}

	/**
	 * In passthrough (a non-2xx error document) this is the buffered body. Otherwise it's whatever arrived
	 * that could NOT be consumed as JSON objects -- which, when nothing parsed at all, is the entire body.
	 * That's the seam a caller uses to recover when it asked for a stream and got an ordinary response.
	 */
	private function _readable() : string {
		return $this->_passthrough ? $this->_raw : ($this->_unconsumed . $this->_buffer);
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
