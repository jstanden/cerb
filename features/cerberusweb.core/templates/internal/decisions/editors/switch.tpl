<form id="frmDecisionSwitch{$id}" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Determine an outcome based on multiple choices</legend>
	
	A <b>decision</b> will evaluate multiple choices and choose the first outcome that satisfies all conditions. 
	Each outcome may use different conditions.  For example, you can use a decision to choose from a list: language, 
	time of day, day of week, service level, etc.
</fieldset>

<b>{'common.title'|devblocks_translate|capitalize}:</b>
<div style="margin:0px 0px 10px 10px;">
	<input type="text" name="title" value="{$model->title}" style="width:100%;" autocomplete="off" spellcheck="false">
</div>

<b>{'common.status'|devblocks_translate|capitalize}:</b>
<div style="margin:0px 0px 10px 10px;">
	<label><input type="radio" name="status_id" value="0" {if !$model->status_id}checked="checked"{/if}> Live</label>
	<label><input type="radio" name="status_id" value="2" {if 2 == $model->status_id}checked="checked"{/if}> Simulator only</label>
	<label><input type="radio" name="status_id" value="1" {if 1 == $model->status_id}checked="checked"{/if}> Disabled</label>
</div>

</form>

{if isset($id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this decision?</legend>
	<p>Are you sure you want to permanently delete this decision and its children?</p>
	<button type="button" class="green" onclick="genericAjaxPost('frmDecisionSwitch{$id}','','c=internal&a=saveDecisionDeletePopup',function() { genericAjaxPopupDestroy('node_switch{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('form.toolbar').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<form class="toolbar">
	<button type="button" onclick="genericAjaxPost('frmDecisionSwitch{$id}','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_switch{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if isset($id)}<button type="button" onclick="$(this).closest('form').hide().prev('fieldset.delete').show();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('node_switch{$id}');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Decision");
		$popup.find('input:text').first().focus();
		
		// Close confirmation
		
		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode == 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
	});
});
</script>