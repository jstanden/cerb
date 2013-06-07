{$div_uniqid = uniqid()}
<fieldset id="{$div_uniqid}" class="peek">
<legend>Configure</legend>

{foreach from=$import_fields item=import_field key=import_seq}
<b>{$import_field.label}</b>

<div style="margin-left:10px;margin-bottom:5px;">

{if $import_field.type == 'S'}
<input name="configure[{$import_seq}]" type="text">
{elseif $import_field.type == 'N'}
<input name="configure[{$import_seq}]" type="text">
{elseif $import_field.type == 'E'}
<input name="configure[{$import_seq}]" type="text">
{elseif $import_field.type == 'C'}
<label><input name="configure[{$import_seq}]" type="radio" value="1"> {'common.yes'|devblocks_translate|capitalize}</label>
<label><input name="configure[{$import_seq}]" type="radio" value="0"> {'common.no'|devblocks_translate|capitalize}</label>
{elseif $import_field.type == 'W'}
<select name="configure[{$import_seq}]">
	{if !isset($workers)}{$workers = DAO_Worker::getAllActive()}{/if}
	{foreach from=$workers item=worker key=worker_id}
	<option value="{$worker_id}">{$worker->getName()}</option>
	{/foreach}
</select>
{else}
	{if substr($import_field.type,0,4) == 'ctx_'}
		{$context = substr($import_field.type, 4)}
		<button type="button" class="chooser" context="{$context}" field="configure[{$import_seq}]"><span class="cerb-sprite sprite-view"></span></button>
	{/if}
{/if}

</div>

{/foreach}
</fieldset>

<script type="text/javascript">
$('#{$div_uniqid} button.chooser').each(function() {
	var $this = $(this);
	ajax.chooser(this,$(this).attr('context'),$(this).attr('field'), { autocomplete:false });
});
</script>
