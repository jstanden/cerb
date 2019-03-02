{$peek_context = CerberusContexts::CONTEXT_CURRENCY}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="currency">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize} ({'common.singular'|devblocks_translate|capitalize}):</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus" placeholder="US Dollar">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize} ({'common.plural'|devblocks_translate|capitalize}):</b></td>
		<td width="99%">
			<input type="text" name="name_plural" value="{$model->name_plural}" style="width:98%;" placeholder="US Dollars">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'dao.currency.symbol'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="symbol" size="4" maxlength="2" value="{$model->symbol}" style="width:4em;" placeholder="$">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'dao.currency.code'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="code" size="4" maxlength="3" value="{$model->code}" style="width:4em;" placeholder="USD">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'dao.currency.decimal_at'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="decimal_at" size="3" maxlength="2" value="{$model->decimal_at}" style="width:4em;" placeholder="2">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.default'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<label>
				<input type="radio" name="is_default" value="1" {if $model->is_default}checked="checked"{/if}> 
				{'common.yes'|devblocks_translate|capitalize}
			</label>
			<label>
				<input type="radio" name="is_default" value="0" {if !$model->is_default}checked="checked"{/if}> 
				{'common.no'|devblocks_translate|capitalize}
			</label>
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" tbody=true bulk=false}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this currency?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.currency'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
