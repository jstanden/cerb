<div class="block">
<h2>Modify Job '{$job->manifest->name}'</h2>
<br>

{assign var=enabled value=$job->getParam('enabled')}
{assign var=locked value=$job->getParam('locked')}
{assign var=lastrun value=$job->getParam('lastrun',0)}
{assign var=duration value=$job->getParam('duration',5)}
{assign var=term value=$job->getParam('term','m')}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveJob">
<input type="hidden" name="id" value="{$job->manifest->id}">

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

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="javascript:toggleDiv('job_{$job->manifest->id}');"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.cancel')|capitalize}</button>

</form>
</div>