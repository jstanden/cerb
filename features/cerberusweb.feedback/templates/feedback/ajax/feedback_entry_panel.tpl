<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmFeedbackEntry">
<input type="hidden" name="c" value="feedback">
<input type="hidden" name="a" value="saveEntry">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>Properties</legend>
	
	<b>{'feedback_entry.quote_address'|devblocks_translate|capitalize}:</b> ({'feedback.peek.quote.tooltip'|devblocks_translate})<br>
	<input type="text" name="email" size="45" maxlength="255" style="width:98%;" value="{$address->email|escape}"><br>
	<br>
	
	<b>{'feedback_entry.quote_text'|devblocks_translate|capitalize}:</b><br>
	<textarea name="quote" cols="45" rows="4" style="width:98%;">{$model->quote_text|escape}</textarea><br>
	<br>
	
	<b>{'feedback_entry.quote_mood'|devblocks_translate|capitalize}:</b> 
	<label><input type="radio" name="mood" value="1" {if 1==$model->quote_mood}checked{/if}> <span style="background-color:rgb(235, 255, 235);color:rgb(0, 180, 0);font-weight:bold;">{'feedback.mood.praise'|devblocks_translate|capitalize}</span></label>
	<label><input type="radio" name="mood" value="0" {if empty($model->quote_mood)}checked{/if}>{'feedback.mood.neutral'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="mood" value="2" {if 2==$model->quote_mood}checked{/if}> <span style="background-color: rgb(255, 235, 235);color: rgb(180, 0, 0);font-weight:bold;">{'feedback.mood.criticism'|devblocks_translate|capitalize}</span></label>
	<br>
	<br>
	
	<b>{'feedback_entry.source_url'|devblocks_translate|capitalize}:</b> ({'common.optional'|devblocks_translate|lower})<br>
	<input type="text" name="url" size="45" maxlength="255" style="width:98%;" value="{$model->source_url|escape}"><br>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

<input type="hidden" name="source_extension_id" value="{$source_extension_id}">
<input type="hidden" name="source_id" value="{$source_id}">
<br>

<button type="button" onclick="genericAjaxPopupPostCloseReloadView('peek','frmFeedbackEntry', '{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if !empty($model->id) && $active_worker->id == $model->worker_id || $active_worker->hasPriv('feedback.actions.delete_all')}<button type="button" onclick="if(confirm('Permanently delete this feedback?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView('peek','frmFeedbackEntry', '{$view_id}'); } "><span class="cerb-sprite sprite-forbidden"></span> {$translate->_('common.delete')|capitalize}</button>{/if}

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'feedback.button.capture'|devblocks_translate|capitalize|escape:'quotes'}");
	} );
</script>
