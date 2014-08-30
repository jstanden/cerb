<form action="#" method="POST" id="formContactPeek" name="formContactPeek" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveContactPeek">
<input type="hidden" name="id" value="{$contact->id}">
<input type="hidden" name="view_id" value="{$view_id}">
{if empty($contact->id) && !empty($context)}
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">
{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.email'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				{if !empty($contact)}{$primary = $contact->getPrimaryAddress()}{/if}
				{if !empty($primary)}
					{$primary->email}
				{else}
					<input type="text" name="email" value="" style="width:98%;" class="required" required="required">
				{/if}
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.password'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<input type="text" name="password" value="" style="width:98%;">
				{if !empty($contact)}
				<div><i>(leave blank to remain unchanged)</i></div>
				{/if}
			</td>
		</tr>
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="middle" align="right">{'common.watchers'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				{if empty($contact->id)}
					<button type="button" class="chooser_watcher"><span class="cerb-sprite sprite-view"></span></button>
					<ul class="chooser-container bubbles" style="display:block;"></ul>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_CONTACT_PERSON, array($contact->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_CONTACT_PERSON context_id=$contact->id full=true}
				{/if}
			</td>
		</tr>
		
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_CONTACT_PERSON context_id=$contact->id}

{* Comments *}
{include file="devblocks:cerberusweb.core::internal/peek/peek_comments_pager.tpl" comments=$comments}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="5" cols="45" style="width:98%;"></textarea>
	<div style="float:right;color:rgb(120,120,120);">{'comment.notify.at_mention'|devblocks_translate}</div>
</fieldset>

{if $active_worker->hasPriv('core.addybook.person.actions.update')}
	<button type="button" onclick="if($('#formContactPeek').validate().form()) { genericAjaxPopupPostCloseReloadView(null,'formContactPeek', '{$view_id}', false, 'contact_save'); }"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate}</button>
	{if $active_worker->hasPriv('core.addybook.person.actions.delete') && !empty($contact)}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this contact person?')) { $('#formContactPeek input[name=do_delete]').val('1'); genericAjaxPopupPostCloseReloadView(null,'formContactPeek','{$view_id}',false,'contact_delete'); } "><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>
{/if}

{if !empty($contact)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=contact_person&id={$contact->id}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open',function(event,ui) {
		var $this = $(this);
		var $textarea = $this.find('textarea[name=comment]');
		
		// Title
		$this.dialog('option','title', 'Contact');

		// Autocomplete
		ajax.emailAutoComplete('#formContactPeek input[name=email]:text');
		
		// Worker chooser
		$this.find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		// Form validation
		$("#formContactPeek").validate();
		$('#formContactPeek :input:text:first').focus();
		
		// @mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

		$textarea.atwho({
			at: '@',
			{literal}tpl: '<li data-value="@${at_mention}">${name} <small style="margin-left:10px;">${title}</small></li>',{/literal}
			data: atwho_workers,
			limit: 10
		});
	});
</script>