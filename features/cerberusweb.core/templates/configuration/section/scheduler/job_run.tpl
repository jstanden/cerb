{* Run-now popup: runs the job server-side (bypassing /cron) and shows the captured debug log *}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmJobRun">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="scheduler">
<input type="hidden" name="action" value="runJob">
<input type="hidden" name="id" value="{$job->manifest->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div id="jobRunBody" style="min-width:480px;">
	<div id="jobRunSpinner" class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-py-3" style="color:var(--cerb-color-background-contrast-150);">
		<span>Running &lsquo;{$job->manifest->name}&rsquo;&hellip;</span>
	</div>
	<div id="jobRunResult" style="display:none;">
		<div id="jobRunMeta" style="font-size:0.85em;color:var(--cerb-color-background-contrast-150);margin-bottom:0.5em;"></div>
		<div id="jobRunLog" class="cerb-u-rounded-2" style="max-height:340px;overflow:auto;font-family:monospace;font-size:0.82em;line-height:1.5;background:var(--cerb-color-background-contrast-245);border:1px solid var(--cerb-color-background-contrast-225);padding:0.7em 0.9em;white-space:pre-wrap;word-break:break-word;"></div>
	</div>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmJobRun');
	const $popup = genericAjaxPopupFind($frm);
	const jobId = "{$job->manifest->id|escape:'javascript' nofilter}";

	$popup.one('popup_open', function() {
		$popup.dialog('option', 'title', "{$job->manifest->name|escape:'javascript' nofilter} — Run now");

		// Spinner while the job runs
		const $spinner = $('#jobRunSpinner');
		if(window.CerbUI && CerbUI.Spinner) {
			const sp = CerbUI.Spinner.create();
			sp.style.width = sp.style.height = '18px';
			$spinner.prepend(sp);
		}

		genericAjaxPost('frmJobRun', '', null, function(json) {
			if('object' != typeof json || false == json.status) {
				$spinner.html('<span style="color:var(--cerb-color-tag-red);">' + ((json && json.error) ? json.error : 'The job failed to run.') + '</span>');
				return;
			}

			$spinner.hide();
			$('#jobRunResult').show();

			const elapsed = json.elapsed_ms ? (json.elapsed_ms >= 1000 ? (json.elapsed_ms/1000).toFixed(1) + 's' : json.elapsed_ms + 'ms') : '';
			$('#jobRunMeta').text('Completed' + (elapsed ? (' in ' + elapsed) : ''));

			const $log = $('#jobRunLog');
			$log.html(json.log ? json.log : '<i>(no log output)</i>');
			$log.scrollTop($log.prop('scrollHeight'));

			// Refresh the originating row in place (sparkline + next-fire ring + "ran X ago")
			if(typeof window.cerbSchedAfterRun === 'function')
				window.cerbSchedAfterRun(jobId, parseInt(json.lastrun, 10) || 0);
		});
	});
});
</script>
