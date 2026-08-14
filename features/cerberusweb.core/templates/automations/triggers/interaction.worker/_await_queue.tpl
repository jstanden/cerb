{* An `await:queue:` render is an INVISIBLE control marker, not a screen. The interaction panel detects it and
   keeps the current transcript + its existing "dots" spinner in place, polling in the BACKGROUND (two-loop:
   worker sidecars drain the queue; a gated re-POST resumes the instant the turn lands) rather than clobbering
   the view with a "waiting" panel. All the poll logic lives in panel.tpl; this only carries its parameters.

   `data-notice` is the ONE thing the marker can put on screen: a turn requeued after a rate limit is otherwise a
   silent gap that reads as a slow model. Empty on an ordinary wait, so the spinner stays the whole story. *}
<div data-cerb-await-queue
	data-poll-ms="{$poll_ms}"
	data-workers="{$workers}"
	data-needs-worker="{$needs_worker|default:1}"
	data-notice="{$notice|default:''}"
	data-continuation-token="{$continuation_token}"></div>
