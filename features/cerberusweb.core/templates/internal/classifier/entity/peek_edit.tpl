{$peek_context = CerberusContexts::CONTEXT_CLASSIFIER_ENTITY}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" class="cerb-form" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="classifier_entity">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.description'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="description" value="{$model->description}" style="width:98%;" placeholder="">
		</td>
	</tr>
</table>

<fieldset class="peek">
	<legend><label><input type="radio" name="type" value="list" {if $model->type =='list'}checked="checked"{/if}> List</label></legend>
	<div>
		<textarea name="params[list][labels]" style="width:100%;height:150px;">{if $model->type == 'list'}{$model->params.labels}{/if}</textarea>
	</div>
	<div class="cerb-list-examples" style="display:none;margin-top:5px;">
		<tt>&lt;label&gt;, &lt;alias&gt;</tt> &nbsp; e.g.:
		<pre style="margin:0px 0px 0px 20px;"><code>mobile, cell
mobile, cellphone
mobile, mobile
website, homepage
website, url
website, website</code></pre>
	</div>
</fieldset>

<fieldset class="peek">
	<legend><label><input type="radio" name="type" value="regexp" {if $model->type == 'regexp'}checked="checked"{/if}> Token Regexp</label></legend>
	<textarea name="params[regexp][pattern]" style="width:100%;height:50px;">{if $model->type == 'regexp'}{$model->params.pattern}{/if}</textarea>
</fieldset>

{*
<fieldset class="peek">
	<legend><label><input type="radio" name="type" value="text" {if $model->type == 'text'}checked="checked"{/if}> Unstructured Text</label></legend>
	
</fieldset>
*}

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
		Are you sure you want to permanently delete this classifier entity?
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
		$popup.dialog('option','title',"{'common.classifier.entity'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		var $textarea_labels = $popup.find('textarea[name="params[text][labels]"]');

		var $textarea_labels_hints = $popup.find('div.cerb-list-examples');
		var $radio_type = $frm.find('input:radio[name=type]');
		
		$radio_type.on('click', function(e) {
			if('list' == $(e.target).val()) {
				$textarea_labels_hints.fadeIn();
			} else {
				$textarea_labels_hints.hide();
			}
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
