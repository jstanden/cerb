{$peek_context = 'cerberusweb.contexts.jira.project'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="jira_project">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'dao.jira_project.jira_key'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="jira_key" value="{$model->jira_key}" style="width:98%;">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.url'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="url" value="{$model->url}" style="width:98%;">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.account'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<button type="button" class="chooser-abstract" data-field-name="connected_account_id" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-single="true" data-query="jira"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $model->connected_account_id}
					{$connected_account = $model->getConnectedAccount()}
					<li><input type="hidden" name="connected_account_id" value="{$model->connected_account_id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-context-id="{$connected_account->id}">{$connected_account->name}</a></li>
				{/if}
			</ul>
		</td>
	</tr>

	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this JIRA project and all of its issues?
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
		$popup.dialog('option','title',"{'Jira Project'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		$popup.find('.chooser-abstract').cerbChooserTrigger();
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
