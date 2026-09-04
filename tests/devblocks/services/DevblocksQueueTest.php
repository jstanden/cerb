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

	// --- Concurrency lanes -----------------------------------------------------------------------
	// Pure by design: getLaneSlots() returns the ORDER to try and the caller shuffles, so the split
	// itself is pinnable here. CI has no MySQL, so anything that acquires a lock is untestable.

	function testLaneWidthBelowThreeHasNoLanes() {
		// Nothing to divide: a slot per lane would leave no commons, so each lane could only ever
		// use its own single slot and an idle pool would read as full.
		$this->assertSame(0, _DevblocksQueueService::getLaneWidth(0));
		$this->assertSame(0, _DevblocksQueueService::getLaneWidth(1));
		$this->assertSame(0, _DevblocksQueueService::getLaneWidth(2));
	}

	function testLaneWidthFloorsAtOne() {
		// intdiv(3, 4) is 0, which would leave the COMMUNITY pool with no lanes at all.
		$this->assertSame(1, _DevblocksQueueService::getLaneWidth(3));
		$this->assertSame(1, _DevblocksQueueService::getLaneWidth(4));
		$this->assertSame(1, _DevblocksQueueService::getLaneWidth(7));
		$this->assertSame(2, _DevblocksQueueService::getLaneWidth(8));
		$this->assertSame(3, _DevblocksQueueService::getLaneWidth(12));
		$this->assertSame(10, _DevblocksQueueService::getLaneWidth(40));
	}

	function testLaneSlotsExactVectors() {
		$this->assertSame([], _DevblocksQueueService::getLaneSlots(0, QueueLane::Fast));

		// Below three: no lanes, so both see the whole pool.
		$this->assertSame([1, 2], _DevblocksQueueService::getLaneSlots(2, QueueLane::Fast));
		$this->assertSame([1, 2], _DevblocksQueueService::getLaneSlots(2, QueueLane::Slow));

		// The community pool: one each, one shared. Fast takes the head, slow the tail, and they
		// overlap on the middle slot.
		$this->assertSame([1, 2], _DevblocksQueueService::getLaneSlots(3, QueueLane::Fast));
		$this->assertSame([2, 3], _DevblocksQueueService::getLaneSlots(3, QueueLane::Slow));

		$this->assertSame([1, 2, 3, 4, 5, 6], _DevblocksQueueService::getLaneSlots(8, QueueLane::Fast));
		$this->assertSame([3, 4, 5, 6, 7, 8], _DevblocksQueueService::getLaneSlots(8, QueueLane::Slow));
	}

	function testLaneSlotsAreContiguous() {
		// A fragmented lane offers the same capacity, so nothing here is about throughput -- it is about
		// the split being one idea instead of two. A lane split around the other lane's block cannot be
		// stated, drawn, or reasoned about as "the head, plus what we share".
		foreach([1, 2, 3, 4, 5, 7, 8, 12, 16, 25, 40] as $n) {
			foreach([QueueLane::Fast, QueueLane::Slow] as $lane) {
				$slots = _DevblocksQueueService::getLaneSlots($n, $lane);

				$this->assertSame(
					range(min($slots), max($slots)),
					$slots,
					sprintf("n=%d %s is fragmented", $n, $lane->value)
				);
			}
		}
	}

	function testLaneSlotsNullIsTheWholePool() {
		// An unclassified drain is not confined to a lane.
		foreach([0, 1, 3, 8, 40] as $n) {
			$this->assertSame(
				$n ? range(1, $n) : [],
				_DevblocksQueueService::getLaneSlots($n, null)
			);
		}
	}

	function testLaneSlotsNeverOverlapAndAlwaysCoverThePool() {
		foreach([0, 1, 2, 3, 4, 7, 8, 12, 16, 40] as $n) {
			$fast = _DevblocksQueueService::getLaneSlots($n, QueueLane::Fast);
			$slow = _DevblocksQueueService::getLaneSlots($n, QueueLane::Slow);
			$width = _DevblocksQueueService::getLaneWidth($n);

			// Slot 0 is the scheduler's and is never in the pool.
			$this->assertNotContains(0, $fast, "n=$n");

			// Every slot offered is real.
            foreach(array_merge($fast, $slow) as $slot)
                $this->assertLessThanOrEqual($n, $slot, "n=$n");

			// Union is the whole pool: with lanes the commons bridge them, without lanes each IS the pool.
			// Sorted, because each lane lists its OWN slots first -- the order is per-lane, the set is not.
			$union = array_unique(array_merge($fast, $slow), SORT_NUMERIC);
			sort($union);

			$this->assertSame($n ? range(1, $n) : [], $union, "n=$n union");

			// What each lane holds ALONE is its dedicated block, and the two are equal and disjoint by
			// construction -- fast owns the head, slow owns the tail.
			$fast_only = array_values(array_diff($fast, $slow));
			$slow_only = array_values(array_diff($slow, $fast));

			$this->assertSame([], array_intersect($fast_only, $slow_only), "n=$n dedicated overlap");
			$this->assertSame($width, count($fast_only), "n=$n fast dedicated");
			$this->assertSame($width, count($slow_only), "n=$n slow dedicated");

			if($width) {
				$this->assertSame(range(1, $width), $fast_only, "n=$n fast head");
				$this->assertSame(range($n - $width + 1, $n), $slow_only, "n=$n slow tail");
			}

			// The commons is exactly what both lanes can reach -- not a third region either owns.
			$commons = array_values(array_intersect($fast, $slow));

			$this->assertSame(max(0, $n - 2 * $width), count($commons), "n=$n commons size");

			if($commons)
				$this->assertSame(range($width + 1, $n - $width), $commons, "n=$n commons");
		}
	}

	function testLaneReservesLessThanHalfThePool() {
		// Lanes exist to stop one kind of work EXCLUDING the other, not to partition the pool. The
		// commons must stay the majority or a lane's dedicated slots become the whole story.
		foreach([3, 4, 7, 8, 12, 16, 40] as $n) {
			$reserved = 2 * _DevblocksQueueService::getLaneWidth($n);
			$this->assertLessThanOrEqual($n, $reserved, "n=$n");

			if($n >= 4)
				$this->assertLessThan($n, $reserved, "n=$n leaves no commons");
		}
	}

	function testLaneWidthIsMonotonicInPoolSize() {
		// A bigger pool must never shrink a lane -- an operator raising slots should not lose capacity.
		// The steps are where the formula changes: the cutoff at 3, the floor holding to 7, then each
		// intdiv boundary.
		$prev = 0;

		foreach([0, 2, 3, 7, 8, 11, 12, 40] as $n) {
			$width = _DevblocksQueueService::getLaneWidth($n);
			$this->assertGreaterThanOrEqual($prev, $width, "n=$n");
			$prev = $width;
		}
	}

	function testLaneSpansCompressToRuns() {
		// One run per lane at every size that has lanes at all -- what the Subscription and Queues pages
		// draw. Read off getLaneSlots(), so a split that fragmented again would show up here first.
		$this->assertSame([], _DevblocksQueueService::getLaneSpans(0, QueueLane::Fast));
		$this->assertSame([[1, 1]], _DevblocksQueueService::getLaneSpans(1, QueueLane::Fast));
		$this->assertSame([[1, 2]], _DevblocksQueueService::getLaneSpans(3, QueueLane::Fast));
		$this->assertSame([[2, 3]], _DevblocksQueueService::getLaneSpans(3, QueueLane::Slow));
		$this->assertSame([[1, 4]], _DevblocksQueueService::getLaneSpans(5, QueueLane::Fast));
		$this->assertSame([[2, 5]], _DevblocksQueueService::getLaneSpans(5, QueueLane::Slow));
		$this->assertSame([[1, 19]], _DevblocksQueueService::getLaneSpans(25, QueueLane::Fast));
		$this->assertSame([[7, 25]], _DevblocksQueueService::getLaneSpans(25, QueueLane::Slow));
	}

	function testLaneSpansCoverExactlyTheLaneSlots() {
		foreach([0, 1, 2, 3, 4, 5, 7, 8, 12, 16, 25, 40, 200] as $n) {
			foreach([QueueLane::Fast, QueueLane::Slow, null] as $lane) {
				$slots = _DevblocksQueueService::getLaneSlots($n, $lane);
				$covered = [];

				foreach(_DevblocksQueueService::getLaneSpans($n, $lane) as $span)
					$covered = array_merge($covered, range($span[0], $span[1]));

				$this->assertSame($slots, $covered, sprintf("n=%d %s", $n, $lane?->value ?? 'pool'));
			}
		}
	}
}
