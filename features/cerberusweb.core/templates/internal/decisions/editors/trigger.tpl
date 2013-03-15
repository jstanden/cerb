<form id="frmDecisionBehavior{$trigger->id}" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="trigger_id" value="{if isset($trigger->id)}{$trigger->id}{else}0{/if}">
{if empty($trigger->id)}
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">
{/if}

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="title" value="{$trigger->title}" style="width:100%;"><br>
<br>

<b>{'common.event'|devblocks_translate|capitalize}:</b><br>
{if empty($ext)}
	<select name="event_point">
		{foreach from=$events item=event key=event_id}
		<option value="{$event_id}">{$event->name}</option>
		{/foreach}
	</select>
	<br>
{else}
	{$ext->name}<br>
{/if}
<br>

<b>{'common.status'|devblocks_translate|capitalize}:</b><br>
<label><input type="radio" name="is_disabled" value="0" {if empty($trigger->is_disabled)}checked="checked"{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
<label><input type="radio" name="is_disabled" value="1" {if !empty($trigger->is_disabled)}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
<br>
<br>

<fieldset class="vars">
<legend>Variables</legend>

<table cellpadding="0" cellspacing="0" border="0" width="100%">
{foreach from=$trigger->variables key=k item=var}
<tr>
	<td valign="top" nowrap="nowrap" width="1%">
		<a href="javascript:;" onclick="$(this).closest('tr').remove();"><span class="cerb-sprite2 sprite-minus-circle" style="vertical-align:middle;"></span></a>
		<select name="var_is_private[]">
			<option value="1" {if $var.is_private}selected="selected"{/if}>private</option>
			<option value="0" {if empty($var.is_private)}selected="selected"{/if}>public</option>
		</select>  
		<input type="hidden" name="var_key[]" value="{$var.key}">
		<input type="hidden" name="var_type[]" value="{$var.type}">
	</td>
	<td valign="top" width="99%">
		<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:10px;">
			<tr>
				<td>
					<input type="text" name="var_label[]" value="{$var.label}" size="45" style="width:100%;"><br><!--
					-->
					{if $var.type == 'S'}
					Text
					{elseif $var.type == 'N'}
					Number
					{elseif $var.type == 'E'}
					Date
					{elseif $var.type == 'C'}
					True/False
					{elseif $var.type == 'W'}
					Worker
					{elseif substr($var.type,0,4)=='ctx_'}
						{$list_context_ext = substr($var.type,4)}
						{$list_context = $list_contexts.$list_context_ext}
						(List) {$list_context->name}
					{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
{/foreach}

<tr class="template" style="display:none;">
	<td valign="top" width="1%" nowrap="nowrap">
		<a href="javascript:;" onclick="$(this).closest('tr').remove();"><span class="cerb-sprite2 sprite-minus-circle" style="vertical-align:middle;"></span></a>
		<select name="var_is_private[]">
			<option value="1" selected="selected">private</option>
			<option value="0">public</option>
		</select>	
		<input type="hidden" name="var_key[]" value="">
	</td>
	<td valign="top" width="99%">
		<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:10px;">
			<tr>
				<td>
					<input type="text" name="var_label[]" value="" size="45" style="width:100%;"><br><!--
					--><select name="var_type[]">
						<option value="S">Text</option>
						<option value="N">Number</option>
						<option value="E">Date</option>
						<option value="C">True/False</option>
						<option value="W">Worker</option>
						{foreach from=$list_contexts item=list_context key=list_context_id}
						<option value="ctx_{$list_context_id}">(List) {$list_context->name}</option>
						{/foreach}
					</select>
				</td>
			</tr>
		</table>
	</td>
</tr>

</table>

<div style="margin-top:2px;">
	<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle" style="verical-align:middle;"></span></button>
</div>

</fieldset>
</form>

{if isset($trigger->id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this trigger?</legend>
	<p>Are you sure you want to permanently delete this behavior and all its effects?</p>
	<button type="button" class="green" onclick="genericAjaxPost('frmDecisionBehavior{$trigger->id}','','c=internal&a=saveDecisionDeletePopup',function() { genericAjaxPopupDestroy('node_trigger{$trigger->id}'); genericAjaxGet('decisionTree{$trigger->id}','c=internal&a=showDecisionTree&id={$trigger->id}'); });"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('form.toolbar').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<form class="toolbar">
	{if !empty($trigger->id)}
		<button type="button" onclick="genericAjaxPost('frmDecisionBehavior{$trigger->id}','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_trigger{$trigger->id}'); genericAjaxGet('decisionTree{$trigger->id}','c=internal&a=showDecisionTree&id={$trigger->id}'); });"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{else}
		<button type="button" onclick="genericAjaxPost('frmDecisionBehavior','','c=internal&a=saveDecisionPopup&json=1',function(json) { $popup = genericAjaxPopupFetch('node_trigger'); event = jQuery.Event('trigger_create'); event.trigger_id = json.trigger_id; event.event_point = json.event_point; $popup.trigger(event); genericAjaxPopupDestroy('node_trigger');  });"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{/if}
	{if isset($trigger->id)}<button type="button" onclick="$(this).closest('form').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('node_trigger{$trigger->id}');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if empty($trigger->id)}New {/if}Behavior");
		$(this).find('input:text').first().focus();
		
		$(this).find('fieldset.vars button.add').click(function() {
			$template = $(this).closest('fieldset').find('table tr.template');
			$tr = $template.clone().removeClass('template').show();
			$tr.insertBefore($template);
		});
	});
</script>
