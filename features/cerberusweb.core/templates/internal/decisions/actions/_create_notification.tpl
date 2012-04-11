{if !empty($values_to_contexts)}
<b>When the notification is clicked, go to:</b>
<div style="margin-left:10px;">
<select name="{$namePrefix}[on]">
	<option value="" {if empty($params.on)}selected="selected"{/if}> - a specific URL - </option>
	{foreach from=$values_to_contexts item=context_data key=val_key}
	<option value="{$val_key}" context="{$context_data.context}" {if $params.on == $val_key}selected="selected"{/if}>{$context_data.label}</option>
	{/foreach}
</select>
<div style="{if !empty($params.on)}display:none;{/if}">
	<input type="text" name="{$namePrefix}[url]" size="45" class="placeholders" value="{if empty($params.on)}{$params.url}{/if}" style="width:100%;">
</div>
</div>
{/if}

<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<div style="margin-top:5px;">
	<b>{'common.notify_workers'|devblocks_translate|capitalize}:</b>  
	<div style="margin-left:10px;">
		{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
	</div>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
$action.find('select:first').change(function(e) {
	$this = $(this);
	$input = $this.next('div');

	if($this.val().length == 0) {
		$input.show();
	} else {
		$input.hide().find('input');
	}
});
</script>