<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmFeedbackEntry">
<input type="hidden" name="c" value="feedback">
<input type="hidden" name="a" value="saveEntry">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
	<legend>Properties</legend>
	
	<b>{'feedback_entry.quote_address'|devblocks_translate|capitalize}:</b> ({'feedback.peek.quote.tooltip'|devblocks_translate})<br>
	<input type="text" name="email" size="45" maxlength="255" style="width:98%;" value="{$address->email}"><br>
	<br>
	
	<b>{'feedback_entry.quote_text'|devblocks_translate|capitalize}:</b><br>
	<textarea name="quote" cols="45" rows="4" style="width:98%;">{$model->quote_text}</textarea><br>
	<br>
	
	<b>{'feedback_entry.quote_mood'|devblocks_translate|capitalize}:</b> 
	<label><input type="radio" name="mood" value="1" {if 1==$model->quote_mood}checked{/if}> <span class="tag tag-green" style="vertical-align:middle;">{'feedback.mood.praise'|devblocks_translate|capitalize}</span></label>
	<label><input type="radio" name="mood" value="0" {if empty($model->quote_mood)}checked{/if}> <span class="tag tag-gray" style="vertical-align:middle;">{'feedback.mood.neutral'|devblocks_translate|capitalize}</span></label>
	<label><input type="radio" name="mood" value="2" {if 2==$model->quote_mood}checked{/if}> <span class="tag tag-red" style="vertical-align:middle;">{'feedback.mood.criticism'|devblocks_translate|capitalize}</span></label>
	<br>
	<br>
	
	<b>{'feedback_entry.source_url'|devblocks_translate|capitalize}:</b> ({'common.optional'|devblocks_translate|lower})<br>
	<input type="text" name="url" size="45" maxlength="255" style="width:98%;" value="{$model->source_url}"><br>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

<input type="hidden" name="source_extension_id" value="{$source_extension_id}">
<input type="hidden" name="source_id" value="{$source_id}">
<br>

<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmFeedbackEntry', '{$view_id}');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if !empty($model->id) && $active_worker->id == $model->worker_id || $active_worker->hasPriv('feedback.actions.delete_all')}<button type="button" onclick="if(confirm('Permanently delete this feedback?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView(null,'frmFeedbackEntry', '{$view_id}'); } "><span class="cerb-sprite2 sprite-minus-circle"></span> {$translate->_('common.delete')|capitalize}</button>{/if}

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'feedback.button.capture'|devblocks_translate|capitalize}");
		ajax.emailAutoComplete('#frmFeedbackEntry input:text[name=email]', { multiple: false } );
	});
</script>
