<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmDatacenterDomain">
<input type="hidden" name="c" value="datacenter.domains">
<input type="hidden" name="a" value="saveDomainPeek">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name|escape}" style="width:98%;">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'cerberusweb.datacenter.common.server'|devblocks_translate}:</b></td>
		<td width="99%">
			<select name="server_id">
				<option value="0" {if empty($model->server_id)}selected="selected"{/if}>-- specify server --</option>
				{foreach from=$servers item=server key=server_id}
					<option value="{$server_id}" {if $model->server_id==$server_id}selected="selected"{/if}>{$server->name|escape}</option>
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
			<button type="button" class="chooser_addy"><span class="cerb-sprite sprite-add"></span></button>
			{if !empty($context_addresses)}
			<ul class="chooser-container bubbles">
				{foreach from=$context_addresses item=context_address key=context_address_id}
				<li>{$context_address.a_email|escape}<input type="hidden" name="contact_address_id[]" value="{$context_address_id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
				{/foreach}
			</ul>
			{/if}
		</td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}
<br>

<button type="button" onclick="genericAjaxPopupPostCloseReloadView('peek','frmDatacenterDomain','{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if $model->id && $active_worker->is_superuser}<button type="button" onclick="if(confirm('Permanently delete this domain?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView('peek','frmDatacenterDomain','{$view_id}'); } "><span class="cerb-sprite sprite-forbidden"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'cerberusweb.datacenter.domain'|devblocks_translate|escape:'quotes'}");
		
		$('#frmDatacenterDomain button.chooser_addy').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.address','contact_address_id');
		});
	} );
</script>
