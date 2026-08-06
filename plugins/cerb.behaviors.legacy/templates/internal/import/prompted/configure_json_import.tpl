{$div_uniqid = uniqid()}
<fieldset id="{$div_uniqid}" class="peek">
<legend>Configure</legend>

{foreach from=$import_fields item=import_field key=import_seq}
<b>{$import_field.label}</b>

<div style="margin-left:10px;margin-bottom:5px;">

{if $import_field.type == 'S'}
<input name="configure[{$import_seq}]" type="text" style="width:90%">
{elseif $import_field.type == 'N'}
<input name="configure[{$import_seq}]" type="text" style="width:90%">
{elseif $import_field.type == 'E'}
<input name="configure[{$import_seq}]" type="text" style="width:90%">
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
{elseif $import_field.type == 'chooser'}
<div class="cerb-ui-record-chooser" data-context="{$import_field.params.context}" data-name="configure[{$import_seq}]"{if $import_field.params.single} data-single="true"{/if}{if $import_field.params.query} data-query="{$import_field.params.query}"{/if}></div>
{else}
	{if substr($import_field.type,0,4) == 'ctx_'}
		{$context = substr($import_field.type, 4)}
		<div class="cerb-ui-record-chooser" data-context="{$context}" data-name="configure[{$import_seq}]"></div>
	{/if}
{/if}

</div>

{/foreach}
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $fieldset = $('#{$div_uniqid}');

	if(window.CerbUI && CerbUI.RecordChooser)
		$fieldset.find('.cerb-ui-record-chooser').each(function() {
			new CerbUI.RecordChooser(this, {
				context: this.getAttribute('data-context'),
				name: this.getAttribute('data-name'),
				multiple: !this.hasAttribute('data-single'),
				query: this.getAttribute('data-query') || ''
			});
		});
});
</script>
