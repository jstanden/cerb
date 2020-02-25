<fieldset>
	<legend>Modify Job '{$job->manifest->name}'</legend>
	
	{assign var=enabled value=$job->getParam('enabled')}
	{assign var=locked value=$job->getParam('locked')}
	{assign var=lastrun value=$job->getParam('lastrun',0)}
	{assign var=duration value=$job->getParam('duration',5)}
	{assign var=term value=$job->getParam('term','m')}
	
	{$extid = $job->manifest->id|replace:'.':'_'}
	<form id="frmJob{$extid}" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="scheduler">
	<input type="hidden" name="action" value="saveJobJson">
	<input type="hidden" name="id" value="{$job->manifest->id}">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	
	<label><input type="checkbox" name="enabled" value="1" {if $enabled}checked{/if}> <b>Enabled</b></label>
	
	{if $locked}
	<label><input type="checkbox" name="locked" value="1" {if $locked}checked{/if}> <b>Locked</b></label>
	{/if}

	<br>
	<br>
	
	<b>Run every:</b><br>
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
	
	{if $job}
		{$job->configure($job)}
	{/if}
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="javascript:toggleDiv('jobedit_{$extid}');"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
	</form>
</fieldset>

<script type="text/javascript">
	$('#frmJob{$extid} BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmJob{$extid}','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmJob{$extid} div.status',$o.error);
				} else {
					genericAjaxGet('job_{$extid}','c=config&a=invoke&module=scheduler&action=getJob&id={$job->manifest->id}');
				}
			});
		})
	;
</script>
