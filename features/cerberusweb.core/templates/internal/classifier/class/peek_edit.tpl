{$peek_context = CerberusContexts::CONTEXT_CLASSIFIER_CLASS}
{$peek_context_id = $model->id}
{$frm_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$frm_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="classifier_class">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.classifier'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if $model && $model->id}
				{$classifier = $model->getClassifier()}
				{if $classifier}
				<ul class="bubbles">
					<li><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CLASSIFIER}" data-context-id="{$classifier->id}">{$classifier->name}</a></li>
				</ul>
				{/if}
			{else}
			<button type="button" class="chooser-abstract" data-field-name="classifier_id" data-context="{CerberusContexts::CONTEXT_CLASSIFIER}" data-single="true" data-query=""><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $model}
					{$classifier = $model->getClassifier()}
					{if $classifier}
						<li><input type="hidden" name="classifier_id" value="{$classifier->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CLASSIFIER}" data-context-id="{$classifier->id}">{$classifier->name}</a></li>
					{/if}
				{/if}
			</ul>
			{/if}
		</td>
	</tr>
</table>
<br>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this classifier class and all of its training data?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$frm_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.classifier.classification'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Triggers
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		$popup.find('.chooser-abstract').cerbChooserTrigger();
		
	});
});
</script>
