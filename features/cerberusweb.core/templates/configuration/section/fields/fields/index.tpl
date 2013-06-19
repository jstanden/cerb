<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<fieldset>
				<legend>Record Type</legend>
				
				<ul style="margin:0;padding:0;list-style:none;">
				{if !empty($context_manifests)}
					{foreach from=$context_manifests item=manifest key=manifest_id}
					<li style="line-height:150%;"><a href="javascript:;" onclick="genericAjaxGet('frmConfigFieldSource','c=config&a=handleSectionAction&section=fields&action=getRecordType&ext_id={$manifest_id}');">{$manifest->name}</a></li>
					{/foreach}
				{/if}
				</ul>
			</fieldset>
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmConfigFieldSource" onsubmit="return false;">
				{if !empty($ext_id)}
					{assign var=context_manifest value=$context_manifests.$ext_id}
					{include file="devblocks:cerberusweb.core::configuration/section/fields/edit_source.tpl" object=$context_manifest}
				{/if}
			</form>
		</td>
	</tr>
</table>