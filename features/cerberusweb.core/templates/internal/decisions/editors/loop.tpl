<form id="frmDecisionLoop{$id}" onsubmit="return false;">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="">
	{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
	{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
	{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
	{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	
	<fieldset>
		<legend>Repeat this branch for each object in a list</legend>
		A <b>loop</b> branch will repeat its decisions and actions for each object in a list.
	</fieldset>
	
	<b>{'common.title'|devblocks_translate|capitalize}:</b>
	<div style="margin:0px 0px 10px 10px;">
		<input type="text" name="title" value="{$model->title}" style="width:100%;" autofocus="autofocus" autocomplete="off" spellcheck="false">
	</div>
	
	<b>{'common.status'|devblocks_translate|capitalize}:</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="radio" name="status_id" value="0" {if !$model->status_id}checked="checked"{/if}> Live</label>
		<label><input type="radio" name="status_id" value="2" {if 2 == $model->status_id}checked="checked"{/if}> Simulator only</label>
		<label><input type="radio" name="status_id" value="1" {if 1 == $model->status_id}checked="checked"{/if}> Disabled</label>
	</div>
	
	<b>For each object in this JSON array:</b>
	<div style="margin:0px 0px 10px 10px;">
		<textarea name="params[foreach_json]" data-editor-mode="ace/mode/twig" style="width:100%;height:200px;">{$model->params.foreach_json}</textarea>
	</div>
	
	<b>Set this object placeholder:</b>
	<div style="margin:0px 0px 10px 10px;">
		{literal}{{{/literal}<input type="text" name="params[as_placeholder]" value="{$model->params.as_placeholder}" size="32">{literal}}}{/literal}
	</div>
</form>

{if isset($id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this loop?</legend>
	<p>Are you sure you want to permanently delete this loop and its children?</p>
	<button type="button" class="green" onclick="genericAjaxPost('frmDecisionLoop{$id}','','c=internal&a=saveDecisionDeletePopup',function() { genericAjaxPopupDestroy('node_switch{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('form.toolbar').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<form class="toolbar">
	<button type="button" onclick="genericAjaxPost('frmDecisionLoop{$id}','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_loop{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if isset($id)}<button type="button" onclick="$(this).closest('form').hide().prev('fieldset.delete').show();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('node_loop{$id}');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Loop");
		
		$popup.find('textarea').cerbCodeEditor();
	});
});
</script>