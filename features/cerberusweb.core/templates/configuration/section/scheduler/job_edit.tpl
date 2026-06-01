<fieldset>
	<legend>Modify Job '{$job->manifest->name}'</legend>
	
	{$is_concurrent = array_key_exists('parallel', $job->manifest->params)}
	{$enabled = $job->getParam('enabled')}
	{$locked = $job->getParam('locked')}
	{$lastrun = $job->getParam('lastrun',0)}
	{$duration = $job->getParam('duration',5)}
	{$term = $job->getParam('term','m')}

	{$extid = $job->manifest->id|replace:'.':'_'}
	<form id="frmJob{$extid}" action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="scheduler">
	<input type="hidden" name="action" value="saveJobJson">
	<input type="hidden" name="id" value="{$job->manifest->id}">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	
	<label><input type="checkbox" name="enabled" value="1" {if $enabled}checked{/if}> <b>Enabled</b></label>
	
	{if $locked && !$is_concurrent}
	<label><input type="checkbox" name="locked" value="1" {if $locked}checked{/if}> <b>Locked</b></label>
	{/if}
	<br>
	<br>

	{if $is_concurrent}
		<b>Runs in parallel up to:</b><br>
		<input type="number" name="concurrency" value="{$concurrency|default:$max_parallel}" min="0" max="{$max_parallel}"> instances
		(max {$max_parallel})
		<br>
		<br>
	{else}
		<b>Run once every:</b><br>
		<input type="text" name="duration" maxlength="5" size="3" value="{$duration}">
		<select name="term">
			<option value="m" {if $term=='m'}selected{/if}>minute(s)
			<option value="h" {if $term=='h'}selected{/if}>hour(s)
			<option value="d" {if $term=='d'}selected{/if}>day(s)
		</select><br>
		<br>

		<b>Starting at date:</b> (leave blank for unchanged)<br>
		<input type="text" name="starting" size="45" value=""><br>
		{if !empty($lastrun)}<i>({$lastrun|devblocks_date})</i><br>{/if}
		<br>
	{/if}
	
	{if $job}
		{$job->configure($job)}
	{/if}
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	<button type="button" class="cancel" data-cerb-job-id="jobedit_{$extid}"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
	</form>
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmJob{$extid}');

	Devblocks.formDisableSubmit($frm);
	 
	$frm.find('BUTTON.submit')
		.on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost('frmJob{$extid}','',null,function(json) {
				if('object' != typeof json || false == json.status) {
					Devblocks.showError('#frmJob{$extid} div.status',json.error);
				} else {
					genericAjaxGet('job_{$extid}','c=config&a=invoke&module=scheduler&action=getJob&id={$job->manifest->id}');
				}
			});
		})
	;

	$frm.find('[data-cerb-job-id]').on('click', function(e) {
		e.stopPropagation();
		let job_id = $(this).attr('data-cerb-job-id');
		toggleDiv(job_id);
	});
});
</script>
