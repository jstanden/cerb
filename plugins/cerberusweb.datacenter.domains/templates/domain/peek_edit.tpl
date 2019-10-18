{$peek_context = CerberusContexts::CONTEXT_DOMAIN}
{$peek_context_id = $model->id}
{$form_id = "frmDatacenterDomain{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="domain">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'cerberusweb.datacenter.common.server'|devblocks_translate}:</b></td>
		<td width="99%">
			{$server = $model->getServer()}
			
			<button type="button" class="chooser-abstract" data-field-name="server_id" data-context="{CerberusContexts::CONTEXT_SERVER}" data-single="true" data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $server}
					<li><input type="hidden" name="server_id" value="{$server->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_SERVER}" data-context-id="{$server->id}">{$server->name}</a></li>
				{/if}
			</ul>
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.created'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="created" class="input_date" size="45" value="{if empty($model->created)}now{else}{$model->created|devblocks_date}{/if}">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>Contacts:</b></td>
		<td width="99%">
			<ul class="bubbles chooser-container" style="display:block;">
				{if !empty($contact_addresses)}
					{foreach from=$contact_addresses item=contact_address key=contact_address_id}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$contact_address->id}{/devblocks_url}?v={$contact_address->updated}"><input type="hidden" name="contact_address_id[]" value="{$contact_address->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$contact_address->id}">{$contact_address->getNameWithEmail()}</a></li>
					{/foreach}
				{/if}
			</ul>
			
			<button type="button" class="chooser-abstract" data-field-name="contact_address_id[]" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-autocomplete="" data-create="true"><span class="glyphicons glyphicons-search"></span></button>
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="2" cols="45" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"></textarea>
</fieldset>

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<p>
		Are you sure you want to permanently delete this domain?
	</p>
	
	<button type="button" class="delete"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $model->id && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$form_id}');
	
	$popup.one('popup_open', function(event,ui) {
		var $textarea = $(this).find('textarea[name=comment]');
		
		$popup.dialog('option','title',"{'common.edit'|devblocks_translate|capitalize}: {'cerberusweb.datacenter.domain'|devblocks_translate|escape:'javascript' nofilter}");
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		$popup.find('.chooser-abstract').cerbChooserTrigger();
		
		$popup.find('input.input_date').cerbDateInputHelper();
		
		// @mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

		$textarea.atwho({
			at: '@',
			{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
			{literal}insertTpl: '@${at_mention}',{/literal}
			data: atwho_workers,
			searchKey: '_index',
			limit: 10
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
