<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmDatacenterDomain">
<input type="hidden" name="c" value="datacenter.domains">
<input type="hidden" name="a" value="saveDomainPeek">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;">
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'cerberusweb.datacenter.common.server'|devblocks_translate}:</b></td>
			<td width="99%">
				<select name="server_id">
					<option value="0" {if empty($model->server_id)}selected="selected"{/if}>-- specify server --</option>
					{foreach from=$servers item=server key=server_id}
						<option value="{$server_id}" {if $model->server_id==$server_id}selected="selected"{/if}>{$server->name}</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.created'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<input type="text" name="created" value="{if empty($model->created)}now{else}{$model->created|devblocks_date}{/if}" style="width:98%;">
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>Contacts:</b></td>
			<td width="99%">
				<button type="button" class="chooser_addy"><span class="cerb-sprite sprite-view"></span></button>
				{if !empty($context_addresses)}
				<ul class="chooser-container bubbles">
					{foreach from=$context_addresses item=context_address key=context_address_id}
					<li>{$context_address.a_first_name} {$context_address.a_last_name} &lt;{$context_address.a_email}&gt;<input type="hidden" name="contact_address_id[]" value="{$context_address_id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
					{/foreach}
				</ul>
				{/if}
			</td>
		</tr>
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="middle" align="right">{$translate->_('common.watchers')|capitalize}: </td>
			<td width="100%">
				{if empty($model->id)}
					<label><input type="checkbox" name="is_watcher" value="1"> {'common.watchers.add_me'|devblocks_translate}</label>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks('cerberusweb.contexts.datacenter.domain', array($model->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context='cerberusweb.contexts.datacenter.domain' context_id=$model->id full=true}
				{/if}
			</td>
		</tr>
		
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{* Comment *}
{if !empty($last_comment)}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
{/if}

<fieldset>
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="5" cols="45" style="width:98%;"></textarea>
	<div class="notify" style="display:none;">
		<b>{'common.notify_watchers_and'|devblocks_translate}:</b>
		<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
		<ul class="chooser-container bubbles" style="display:block;"></ul>
	</div>
</fieldset>

<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmDatacenterDomain','{$view_id}', false, 'datacenter_domain_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if $model->id && $active_worker->is_superuser}<button type="button" onclick="if(confirm('Permanently delete this domain?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView(null,'frmDatacenterDomain','{$view_id}'); } "><span class="cerb-sprite2 sprite-minus-circle-frame"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'cerberusweb.datacenter.domain'|devblocks_translate}");
		$(this).find('textarea[name=comment]').keyup(function() {
			if($(this).val().length > 0) {
				$(this).next('DIV.notify').show();
			} else {
				$(this).next('DIV.notify').hide();
			}
		});
		
		$('#frmDatacenterDomain button.chooser_addy').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.address','contact_address_id', { autocomplete:true });
		});
		$('#frmDatacenterDomain button.chooser_notify_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
		});
	} );
</script>
