{$peek_context = CerberusContexts::CONTEXT_CUSTOM_FIELD}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="custom_field">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><abbr title="The label for this custom field."><b>{'common.name'|devblocks_translate|capitalize}:</b></abbr></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><abbr title="This custom field will be displayed on records of this type."><b>{'common.record'|devblocks_translate|capitalize}:</b></abbr></td>
		<td width="99%">
			{if $model->id}
				<input type="hidden" name="context" value="{$model->context}">
				{$context_mft = $context_mfts.{$model->context}}
				{if $context_mft}
				{$context_mft->name}
				{else}
				{$model->context}
				{/if}
			{else}
			<select name="context">
				<option value=""></option>
				{foreach from=$context_mfts item=ctx}
				<option value="{$ctx->id}" {if $ctx->id == $model->context}selected="selected"{/if}>{$ctx->name}</option>
				{/foreach}
			</select>
			{/if}
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><abbr title="The optional parent fieldset. If blank, this field is displayed on every record of this type.">{'common.fieldset'|devblocks_translate|capitalize}</abbr>:</td>
		<td width="99%">
			<button type="button" class="chooser-abstract" data-field-name="custom_fieldset_id" data-context="{CerberusContexts::CONTEXT_CUSTOM_FIELDSET}" data-single="true" data-query="context:{$model->context}"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $model}
					{$custom_fieldset = $model->getFieldset()}
					{if $custom_fieldset}
						<li><input type="hidden" name="custom_fieldset_id" value="{$custom_fieldset->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CUSTOM_FIELDSET}" data-context-id="{$custom_fieldset->id}">{$custom_fieldset->name}</a></li>
					{/if}
				{/if}
			</ul>
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><abbr title="The display order of this field on the record or fieldset: 0 (first) ... 100 (last). Fields with the same order sort alphabetically.">{'common.order'|devblocks_translate|capitalize}</abbr>:</td>
		<td width="99%">
			<input type="text" name="pos" value="{$model->pos|default:50}" maxlength="3" size="4" placeholder="0-100">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><abbr title="The data type of this custom field."><b>{'common.type'|devblocks_translate|capitalize}:</b></abbr></td>
		<td width="99%">
			{if $model->id}
			<input type="hidden" name="type" value="{$model->type}">
			{$type = $types.{$model->type}}
			{if $type}
				{$type}
			{else}
				{$model->type}
			{/if}
			{else}
			<select name="type">
				<option value=""></option>
				{foreach from=$types item=label key=key}
				<option value="{$key}" {if $key == $model->type}selected="selected"{/if}>{$label}</option>
				{/foreach}
			</select>
			{/if}
		</td>
	</tr>
</table>

<div class="params" style="margin-top:5px;">
{include file="devblocks:cerberusweb.core::internal/custom_fields/field_params.tpl" model=$model}
</div>

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this custom field?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Custom Field'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// When the context changes, update the chooser
		$popup.find('select[name=context]').on('change', function(e) {
			var $this = $(this);
			var $chooser = $popup.find('button[data-field-name="custom_fieldset_id"]');
			var val = $this.val();
			$chooser.attr('data-query', 'context:' + val);
			
			// Clear chooser
			$chooser.parent().find('ul.chooser-container').html('');
		});
		
		// When the type changes, draw new params
		$popup.find('select[name=type]').on('change', function(e) {
			var $this = $(this);
			var $params = $popup.find('div.params');
			genericAjaxGet($params, 'c=profiles&a=handleSectionAction&section=custom_field&action=getFieldParams&type=' + $this.val());
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
