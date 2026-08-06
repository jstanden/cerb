{if !empty($values_to_contexts)}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">When the notification is clicked, go to</label>
	<select name="{$namePrefix}[on]">
		<option value="" {if empty($params.on)}selected="selected"{/if}> - a specific URL - </option>
		{foreach from=$values_to_contexts item=context_data key=val_key}
		{if $context_data.label}<option value="{$val_key}" context="{$context_data.context}" {if $params.on == $val_key}selected="selected"{/if}>{$context_data.label}</option>{/if}
		{/foreach}
	</select>
	<div style="{if !empty($params.on)}display:none;{/if}">
		<input type="text" name="{$namePrefix}[url]" size="45" class="placeholders" value="{if empty($params.on)}{$params.url}{/if}" style="width:100%;">
	</div>
</div>
{/if}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.content'|devblocks_translate|capitalize}</label>
	<textarea name="{$namePrefix}[content]" rows="3" class="placeholders">{$params.content}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.notify_workers'|devblocks_translate|capitalize}</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
var $action = $('#{$namePrefix}_{$nonce}');
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
