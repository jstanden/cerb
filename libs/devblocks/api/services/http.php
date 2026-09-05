<?php

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface as ResponseInterfaceAlias;

class _DevblocksHttpService {
	static $instance = null;
	private static $_client = null;
	private static $_multi_client = null;
	private static $_multi_handler = null;
	
	/**
	 * The connect budget for a request running on the MULTIPLEXED transport, where the 10s default in
	 * _configure_defaults() measures the wrong thing.
	 *
	 * curl enforces CURLOPT_CONNECTTIMEOUT_MS in wall clock, but a handle on CurlMultiHandler only makes
	 * progress while the driver ticks, and every in-flight task's sink writes run serialized on that same
	 * stack. So a connect competes with the decode work of every turn beside it, and the budget stops
	 * measuring "can this host be reached" and starts measuring "how busy is the driver" -- which is how a
	 * healthy provider gets reported as a connect timeout while five siblings stream normally.
	 *
	 * Still a guardrail, not a surrender: an unroutable host fails here in half a minute rather than
	 * sitting on a fiber for the turn's full ceiling. Deliberately flat rather than derived from the
	 * concurrency cap -- that number lives behind a license check in llm.php, and importing it here would
	 * couple the transport to the caller for a figure that is a judgment either way.
	 */
	const int MULTIPLEX_CONNECT_TIMEOUT_SECS = 30;
	
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

				// Liveness for the queue's claim leases. curl calls its progress function roughly once a
				// second for the life of a transfer, whether or not bytes are moving, which is the only
				// signal that says "this consumer is still connected and waiting" during a single provider
				// round trip that runs for ten minutes. The queue service throttles the actual write and
				// no-ops entirely when this request holds no claim, so an ordinary web request pays a
				// function call. Guzzle maps this to CURLOPT_PROGRESSFUNCTION and will THROW if a caller
				// also passes CURLOPT_NOPROGRESS/PROGRESSFUNCTION/XFERINFOFUNCTION in `curl` options.
				if(!array_key_exists(RequestOptions::PROGRESS, $options))
					$options[RequestOptions::PROGRESS] = fn() => DevblocksPlatform::services()->queue()->heartbeat();

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
	 * The same client, on curl's MULTIPLEXING handler instead of the blocking one.
	 *
	 * ️ **It shares `_configure_defaults()` deliberately, and that is a SECURITY property, not tidiness.**
	 * That middleware is where `DEVBLOCKS_HTTP_PROXY` is applied -- so egress from a multiplexed request
	 * goes through the same proxy (squid) as every other request, by construction rather than by
	 * remembering to. It also carries the connect/total timeouts, `http_errors => false`, and the progress
	 * callback that keeps the queue's claim leases alive during a ten-minute provider call.
	 *
	 * This is why multiplexing is built on Guzzle rather than raw `curl_multi_*`: hand-rolled handles
	 * (whether from `curl_init()` or `DevblocksPlatform::curlInit()`, which sets only timeouts) would have
	 * to re-apply all of the above, and the failure mode of forgetting is a SILENT proxy bypass.
	 */
	function getMultiClient() : GuzzleHttp\Client {
		if(self::$_multi_client)
			return self::$_multi_client;

		self::$_multi_handler = new CurlMultiHandler();

		$stack = HandlerStack::create(self::$_multi_handler);
		$stack->push($this->_configure_defaults());

		self::$_multi_client = new GuzzleHttp\Client(['handler' => $stack]);

		return self::$_multi_client;
	}

	/**
	 * The multi handler backing getMultiClient(), so a driver can tick its event loop. Constructing the
	 * client first is what guarantees the handler exists.
	 */
	function getMultiHandler() : CurlMultiHandler {
		$this->getMultiClient();
		return self::$_multi_handler;
	}

	/**
	 * Build a driver for running several HTTP-bound tasks concurrently.
	 *
	 * A factory rather than `new DevblocksHttpMultiplexer()` at the call site, because this file is loaded
	 * LAZILY as a service -- a caller that reaches for the class without having touched the http service
	 * first gets "Class not found". Going through the service is what guarantees the class exists.
	 */
	function createMultiplexer() : DevblocksHttpMultiplexer {
		return new DevblocksHttpMultiplexer();
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
	 * safety behaviors below are the reason this isn't inlined per sink.
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
			// Multiplexed when a driver is running AND we are on a fiber it can suspend. Both halves matter:
			// no driver means nothing would tick the event loop, and no fiber means there is no stack to
			// park, so either way the blocking client below is the only correct choice. Every caller of
			// sendStreamingRequest()/sendEventStreamRequest()/sendNdjsonStreamRequest() gets this for free,
			// which is why neither llm.php nor any provider changes.
			if(($mux = DevblocksHttpMultiplexer::getActive()) && Fiber::getCurrent()) {
				// Set HERE rather than in _configure_defaults() so the raise follows the transport that
				// needs it. That middleware runs for both clients and cannot tell them apart, so widening
				// it there would also widen every blocking call -- including the request-bound ones (the
				// simulator, a one-off llm.chat) whose whole reason for a short connect budget is that a
				// person is waiting on the other end of an FPM worker.
				if(!array_key_exists(RequestOptions::CONNECT_TIMEOUT, $options))
					$options[RequestOptions::CONNECT_TIMEOUT] = self::MULTIPLEX_CONNECT_TIMEOUT_SECS;

				return $mux->await($this->getMultiClient()->sendAsync($request, $options));
			}

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
 * Runs several HTTP-bound tasks concurrently in ONE process, on ONE curl_multi event loop.
 *
 * The premise: a drain worker burns approximately zero CPU while it sits in `curl_exec` waiting on a
 * provider -- that wait is the entire hold, and it is shareable. But a BLOCKING process cannot exploit
 * its own idleness: `curl_exec` owns the process for the length of the call. Fibers are what let a
 * synchronous-looking turn park its stack mid-request so the loop can drive the others.
 *
 * **Why fibers rather than promises:** every caller stays written the way it is. `_streamTurn()`, the
 * by-reference accumulator closures, `_salvageStreamedTurn()`, the interrupt check on each throttled
 * flush -- none of it changes, because the only thing that becomes asynchronous is the transport, and
 * that is exactly where the waiting was. A promise-based rewrite would have to restructure each
 * provider's whole turn lifecycle into `then()` chains for the same benefit.
 *
 * **It is opt-in by construction.** `getActive()` is null unless a driver is running, so every existing
 * call site keeps the blocking client and behaves byte-identically. There is no flag to leave on.
 *
 * ️ **Sink writes run on the DRIVER's stack, not the fiber's.** curl invokes CURLOPT_WRITEFUNCTION
 * during `tick()`, so a task's per-chunk work -- decoding, `updateStreamingMessage()`, the interrupt
 * read -- executes inline in the loop and is therefore SERIALIZED across every task. Nothing corrupts
 * (there is one DB connection and one thread of control), but a slow query in one task's write path
 * stalls every other task's progress callbacks, and with them their claim heartbeats. That is the
 * head-of-line risk to measure before raising the task count far.
 */
class DevblocksHttpMultiplexer {
	private static ?DevblocksHttpMultiplexer $_active = null;

	/** @var Fiber[] */
	private array $_fibers = [];

	/**
	 * The driver for the current call stack, or null when nothing is multiplexing. Read by
	 * _sendWithSink() to decide between the async and blocking transports.
	 */
	static function getActive() : ?DevblocksHttpMultiplexer {
		return self::$_active;
	}

	/**
	 * Park the current fiber until $promise settles. The driver resumes us after each tick; we re-check
	 * rather than trusting the resume, because one tick can settle several tasks at once.
	 *
	 * @throws Throwable whatever the request threw, rethrown on the CALLER's stack so a provider's own
	 *                   try/catch (which is what salvages a partial turn) still sees it.
	 */
	function await(PromiseInterface $promise) {
		$state = ['done' => false, 'value' => null, 'error' => null];

		$promise->then(
			function($value) use (&$state) { $state['done'] = true; $state['value'] = $value; },
			function($reason) use (&$state) { $state['done'] = true; $state['error'] = $reason; }
		);

		while(!$state['done'])
			Fiber::suspend();

		if($state['error']) {
			if($state['error'] instanceof Throwable)
				throw $state['error'];

			throw new Exception(strval($state['error']));
		}

		return $state['value'];
	}

	/**
	 * Run tasks concurrently, optionally REFILLING as they finish, and return their results.
	 *
	 * With no `$producer` this is a plain batch: start N, wait for all N. That is the conservative shape
	 * and it is what a caller wanting "one sweep, then yield" asks for.
	 *
	 * With a `$producer` it becomes CONTINUOUS: whenever a task finishes and capacity frees, the producer
	 * is asked for more. That matters because provider turns skew LONG -- waiting for an entire batch to
	 * drain before starting another means a second batch essentially never starts, so the capacity freed
	 * by the quick turns (a short tool call, a brief thinking turn) sits idle for the length of the
	 * slowest one. The producer owns the admission deadline: it returns an empty array once it should
	 * stop, and the run then winds down as the last in-flight tasks finish rather than cutting them off.
	 *
	 * A task that throws yields its Throwable in place of a result rather than tearing down its
	 * siblings -- one provider failing must not lose the turns running beside it.
	 *
	 * @param array<string|int,callable> $tasks   initial tasks; may be empty when a producer is supplied
	 * @param callable|null $producer             fn(int $free) : callable[] -- [] means "no more"
	 * @param int $cap                            max in flight; defaults to the initial task count
	 * @return array<string|int,mixed>
	 */
	function run(array $tasks, ?callable $producer = null, int $cap = 0) : array {
		if(!$tasks && !$producer)
			return [];

		if($cap < 1)
			$cap = max(1, count($tasks));

		$http = DevblocksPlatform::services()->http();
		$handler = $http->getMultiHandler();

		// Nesting would give two drivers one event loop and each would resume the other's fibers.
		$previous = self::$_active;
		self::$_active = $this;

		$results = [];
		$this->_fibers = [];
		$next_key = 0;

		// Starting a fiber runs it until its first suspend, so a task that never touches the network
		// simply completes here.
		$start = function(array $batch) use (&$results, &$next_key) : void {
			foreach($batch as $task) {
				$key = $next_key++;
				$fiber = new Fiber($task);
				$this->_fibers[$key] = $fiber;

				try {
					$fiber->start();
				} catch(Throwable $e) {
					$results[$key] = $e;
					unset($this->_fibers[$key]);
				}
			}
		};

		try {
			$start(array_values($tasks));

			while(true) {
				// Refill BEFORE the emptiness test, so a run that started with nothing (producer-only)
				// gets its first batch, and so the loop ends only when the producer is done AND nothing
				// is still in flight.
				if($producer && ($free = $cap - count($this->_fibers)) > 0)
					$start($producer($free));

				if(!$this->_fibers)
					break;

				// Drives curl_multi. Blocks in curl_multi_select for up to its select timeout when
				// transfers are in flight, so an idle loop costs a syscall rather than a spin -- which is
				// the whole point, since the tasks are waiting on the network and not on us.
				$handler->tick();

				// Guzzle defers promise callbacks onto a task queue; without draining it the `then()`
				// above never runs and every fiber waits forever on a request that already finished.
				\GuzzleHttp\Promise\Utils::queue()->run();

				foreach($this->_fibers as $key => $fiber) {
					if($fiber->isTerminated()) {
						$results[$key] = $fiber->getReturn();
						unset($this->_fibers[$key]);
						continue;
					}

					if(!$fiber->isSuspended())
						continue;

					try {
						$fiber->resume();

						if($fiber->isTerminated()) {
							$results[$key] = $fiber->getReturn();
							unset($this->_fibers[$key]);
						}

					} catch(Throwable $e) {
						$results[$key] = $e;
						unset($this->_fibers[$key]);
					}
				}
			}

		} finally {
			self::$_active = $previous;
			$this->_fibers = [];
		}

		return $results;
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
