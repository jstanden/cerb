{$is_concurrent = array_key_exists('parallel', $job->manifest->params)}
{$enabled = $job->getParam('enabled')}
{$locked = $job->getParam('locked')}
{$lastrun = $job->getParam('lastrun',0)}
{$duration = $job->getParam('duration',5)}
{$term = $job->getParam('term','m')}
{$concurrency = $job->getParam('concurrency', $max_parallel)}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmJobPeek" class="cerb-ui-form" style="min-width:340px;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="scheduler">
<input type="hidden" name="action" value="saveJobJson">
<input type="hidden" name="id" value="{$job->manifest->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-form--section">
	<div class="cerb-ui-form--section-head">Schedule</div>
	<div class="cerb-ui-form--section-body">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{{'common.enabled'|devblocks_translate|capitalize}}</label>
				<div>
					<label class="cerb-ui-toggle"><input type="checkbox" name="enabled" value="1" {if $enabled}checked{/if}><span class="cerb-ui-toggle--slider"></span></label>
				</div>
			</div>

			{if $locked && !$is_concurrent}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Locked</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-toggle"><input type="checkbox" name="locked" value="1" checked><span class="cerb-ui-toggle--slider"></span></label>
					<span class="cerb-ui-form--hint">since {$locked|devblocks_date}</span>
				</div>
			</div>
			{/if}
		</div>

		<div class="cerb-ui-form--row">
			{if $is_concurrent}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Runs in parallel up to</label>
					<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
						<input type="number" name="concurrency" value="{$concurrency|default:$max_parallel}" min="0" max="{$max_parallel}"> instances
						<span class="cerb-ui-form--hint">(max {$max_parallel})</span>
					</div>
				</div>
			{else}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Run once every</label>
					<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
						<input type="text" name="duration" maxlength="5" size="3" value="{$duration}">
						<select name="term">
							<option value="m" {if $term=='m'}selected{/if}>minute(s)</option>
							<option value="h" {if $term=='h'}selected{/if}>hour(s)</option>
							<option value="d" {if $term=='d'}selected{/if}>day(s)</option>
						</select>
					</div>
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Starting at date <span class="cerb-ui-form--hint">(leave blank for unchanged)</span></label>
					<input type="text" name="starting" size="40" value="">
					{if !empty($lastrun)}<div class="cerb-ui-form--help">Last run: {$lastrun|devblocks_date}</div>{/if}
				</div>
			{/if}
		</div>
	</div>
</div>

{if $job}
	{$job->configure($job)}
{/if}

<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmJobPeek');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	// Wire the on/off switches (the checkboxes still carry the POST value)
	if(window.CerbUI && CerbUI.Toggle)
		$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"Scheduler: {$job->manifest->name|escape:'javascript' nofilter}");

		$popup.find('button.submit').on('click', function() {
			genericAjaxPost('frmJobPeek','',null,function(json) {
				if('object' != typeof json) {
					Devblocks.createAlertError('An unexpected error occurred');
				} else if (json.hasOwnProperty('error')) {
					Devblocks.createAlertError(json.error);
				} else {
					genericAjaxPopupClose($popup);
					// Swap just this job's row in place (state, interval, ring, chart) instead of a full reload
					if(window.cerbSchedAfterSave && json.html)
						window.cerbSchedAfterSave("{$job->id|escape:'javascript' nofilter}", json.html);
					else
						document.location.reload(); // fallback if opened outside the scheduler page
				}
			});
		});
	});
});
</script>
