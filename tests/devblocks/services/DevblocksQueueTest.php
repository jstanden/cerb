<?php
use PHPUnit\Framework\TestCase;

class DevblocksQueueTest extends TestCase {
	// retry_max <= 0 means the queue never retries -> no backoff (caller writes terminal FAILED).
	function testRetryBackoffNoRetries() {
		$this->assertSame(0, _DevblocksQueueService::getRetryBackoffSecs(0, 0, 86400));
		$this->assertSame(0, _DevblocksQueueService::getRetryBackoffSecs(3, 0, 86400));
		// Negative retry_max is treated the same as 0
		$this->assertSame(0, _DevblocksQueueService::getRetryBackoffSecs(0, -5, 86400));
	}

	// Exact geometric series for retry_max=5, window=86400 (1 day): floor(86400 * 2^k / (2^5 - 1)).
	function testRetryBackoffKnownSequence() {
		$w = 86400;
		$n = 5;
		$this->assertSame(2787, _DevblocksQueueService::getRetryBackoffSecs(0, $n, $w));  // ~46m
		$this->assertSame(5574, _DevblocksQueueService::getRetryBackoffSecs(1, $n, $w));  // ~1.5h
		$this->assertSame(11148, _DevblocksQueueService::getRetryBackoffSecs(2, $n, $w)); // ~3.1h
		$this->assertSame(22296, _DevblocksQueueService::getRetryBackoffSecs(3, $n, $w)); // ~6.2h
		$this->assertSame(44593, _DevblocksQueueService::getRetryBackoffSecs(4, $n, $w)); // ~12.4h

		// Each step roughly doubles the previous (the 2^k factor)
		for($k = 1; $k < $n; $k++) {
			$prev = _DevblocksQueueService::getRetryBackoffSecs($k - 1, $n, $w);
			$cur = _DevblocksQueueService::getRetryBackoffSecs($k, $n, $w);
			$this->assertEqualsWithDelta(2.0, $cur / $prev, 0.001);
		}
	}

	// The intervals k=0..retry_max-1 form a geometric series that sums to ~the window, so the
	// final retry lands about one window after the first failure. Floor loses < 1s per term, so
	// the sum lands in (W - N, W]. Windows here are large enough that the min-floor never kicks in.
	function testRetryBackoffFillsWindow() {
		foreach([[5, 86400], [8, 86400], [4, 3600], [10, 604800]] as [$n, $w]) {
			$sum = 0;
			for($k = 0; $k < $n; $k++)
				$sum += _DevblocksQueueService::getRetryBackoffSecs($k, $n, $w);

			$this->assertLessThanOrEqual($w, $sum, "N=$n W=$w sum overshot window");
			$this->assertGreaterThan($w - $n, $sum, "N=$n W=$w sum undershot window by more than rounding");
		}
	}

	// A tiny (or zero) window must never schedule a sub-floor retry; the delay is clamped to
	// RETRY_BACKOFF_MIN_SECS so we don't hammer the backend.
	function testRetryBackoffMinFloor() {
		$min = _DevblocksQueueService::RETRY_BACKOFF_MIN_SECS;

		// raw delay = floor(0 * ...) = 0 -> floored to the minimum
		$this->assertSame($min, _DevblocksQueueService::getRetryBackoffSecs(0, 5, 0));
		// raw delay = floor(31 * 1 / 31) = 1 -> floored to the minimum
		$this->assertSame($min, _DevblocksQueueService::getRetryBackoffSecs(0, 5, 31));
	}

	// retry_count is clamped to [0, retry_max - 1]: a stale/over-count never escapes the schedule
	// or underflows the exponent.
	function testRetryBackoffClampsRetryCount() {
		$w = 86400;
		$n = 5;
		$first = _DevblocksQueueService::getRetryBackoffSecs(0, $n, $w);
		$last = _DevblocksQueueService::getRetryBackoffSecs($n - 1, $n, $w);

		// Negative retry_count clamps to the first interval
		$this->assertSame($first, _DevblocksQueueService::getRetryBackoffSecs(-3, $n, $w));
		// retry_count at/beyond retry_max clamps to the last interval
		$this->assertSame($last, _DevblocksQueueService::getRetryBackoffSecs($n, $n, $w));
		$this->assertSame($last, _DevblocksQueueService::getRetryBackoffSecs(99, $n, $w));
	}

	// retry_max is capped at 16 so 2^retry_max can't overflow; a larger cap behaves identically to
	// 16 and always yields a sane positive delay.
	function testRetryBackoffCapsRetryMax() {
		$w = 86400;

		// Beyond the cap, behavior matches retry_max = 16 at every clamped step
		foreach([0, 5, 15, 50] as $k) {
			$this->assertSame(
				_DevblocksQueueService::getRetryBackoffSecs($k, 16, $w),
				_DevblocksQueueService::getRetryBackoffSecs($k, 1000, $w),
				"cap mismatch at k=$k"
			);
		}

		// No overflow / negative wrap at the top of the capped range
		$top = _DevblocksQueueService::getRetryBackoffSecs(15, 16, $w);
		$this->assertGreaterThan(0, $top);
		$this->assertLessThanOrEqual($w, $top);
	}

	// Within the active range the schedule is non-decreasing (monotonic backoff).
	function testRetryBackoffMonotonic() {
		$w = 86400;
		$n = 8;
		$prev = -1;
		for($k = 0; $k < $n; $k++) {
			$cur = _DevblocksQueueService::getRetryBackoffSecs($k, $n, $w);
			$this->assertGreaterThanOrEqual($prev, $cur, "backoff decreased at k=$k");
			$prev = $cur;
		}
	}
}
