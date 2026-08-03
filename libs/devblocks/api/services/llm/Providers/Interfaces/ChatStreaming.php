<?php
namespace Cerb\LLM\Providers\Interfaces;

/**
 * Implemented by chat providers that can run a turn as a Server-Sent Event stream instead of one
 * blocking round-trip.
 *
 * This exists as its OWN interface rather than as methods on Chat so providers opt in: a caller that
 * needs streaming tests `instanceof ChatStreaming` and falls back to the ordinary blocking call
 * otherwise, so a provider that hasn't implemented it keeps working untouched.
 *
 * Why a turn would want this: a total request timeout is a wall-clock guillotine that cannot tell a
 * LONG turn from a STUCK one, so a healthy generation gets killed mid-flight and its tokens are billed
 * and discarded. Streaming replaces that with an inactivity cutoff, and makes the partial response
 * observable while it is still being produced.
 */
interface ChatStreaming {
	/**
	 * Run the NEXT chatCompletion() on this provider instance as a stream.
	 *
	 * @param ?callable $on_progress fn(array $message, array $usage) : bool
	 *   Called as content accumulates, with the message in the same `['role'=>…, 'content'=>[…]]` shape
	 *   that gets persisted. Returning false aborts the transfer mid-stream, keeping whatever has
	 *   arrived so far. THROTTLING AND INTERRUPT POLLING ARE THE CALLER'S POLICY, not the provider's —
	 *   this fires per event, so a handler that writes to storage must rate-limit itself.
	 */
	function enableStreaming(?callable $on_progress = null) : void;

	/**
	 * The content accumulated by the most recent streamed turn, in the same shape chatCompletion()
	 * would have returned, or null if nothing has streamed. Readable after a failure or an abort so the
	 * caller can salvage and sanitize a partial turn rather than losing it.
	 */
	function getStreamedPartial() : ?array;

	/**
	 * Strip content blocks that a turn cut short cannot legally carry, returning the message in the same shape
	 * with only replayable blocks left. An empty `content` means nothing survived and the turn should be
	 * dropped rather than persisted.
	 *
	 * This has to be **purely structural** — decided from the message alone, with no memory of the stream that
	 * produced it. A turn can be finalized by a completely different process than the one that generated it
	 * (a worker killed mid-stream, recovered on the session's next turn), and that process has no idea which
	 * blocks closed cleanly.
	 *
	 * The cost of getting it wrong is not cosmetic: an incomplete block replayed on the next request is
	 * rejected by the API, which strands the session permanently rather than failing one turn.
	 */
	function sanitizePartialContent(array $message) : array;
}
