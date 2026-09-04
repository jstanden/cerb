{* One row per lane over the concurrency pool, a block per slot. Shared by Setup > Subscription and
   Setup > Queues so the two pages cannot drift apart on what the split looks like.

   There is no `Shared` row: the commons is where the two rows OVERLAP, which is the actual
   relationship -- either kind of work may take those slots. A pool under three slots has no lanes at
   all, so both rows cover every slot and the whole width overlaps. That is the correct reading, and
   it needs no branch of its own.

   Params: $id (element id), $lane_spans_fast_json, $lane_spans_slow_json *}
<div class="cerb-ui-gantt" id="{$id}"
	data-spans-fast="{$lane_spans_fast_json}"
	data-spans-slow="{$lane_spans_slow_json}"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const el = document.getElementById('{$id}');

	if(!el || !(window.CerbUI && CerbUI.Gantt))
		return;

	// `step: 1` makes the axis discrete, so a span's end slot is INCLUSIVE and each slot is one cell.
	// Without it, [1,6] would draw five slots instead of six.
	//
	// `segment: true` draws one block per slot rather than one bar per run, because a slot is a unit of
	// capacity and not a duration: the row shows the blocks it holds and leaves the rest as empty cells.
	//
	// No axis: an admin here is reading the SHAPE of the split, and numbering individual slots invites
	// the question of which slot is which -- a question these pages cannot answer and do not need to.
	new CerbUI.Gantt(el, {
		xScale: 'linear',
		step: 1,
		segment: true,
		axis: false,
		rowHeight: 26,
		barHeight: 14,
		labelWidth: 100,
		rows: [
			{ label: 'Bulk jobs', color: '#0088e6', spans: JSON.parse(el.dataset.spansFast) },
			{ label: 'Agent turns', color: '#9467bd', spans: JSON.parse(el.dataset.spansSlow) }
		]
	});
});
</script>
