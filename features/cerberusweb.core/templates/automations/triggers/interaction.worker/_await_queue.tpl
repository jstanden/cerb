{* An `await:queue:` render is an INVISIBLE control marker, not a screen. The interaction panel detects it and
   keeps the current transcript + its existing "dots" spinner in place, polling in the BACKGROUND (two-loop:
   worker sidecars drain the queue; a gated re-POST resumes the instant the turn lands) rather than clobbering
   the view with a "waiting" panel. All the poll logic lives in panel.tpl; this only carries its parameters. *}
<div data-cerb-await-queue
	data-poll-ms="{$poll_ms}"
	data-workers="{$workers}"
	data-needs-worker="{$needs_worker|default:1}"
	data-continuation-token="{$continuation_token}"></div>
