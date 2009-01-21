<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>Sources</h2></td>
				</tr>
				<tr>
					<td>
						<div style="margin:0px;padding:3px;width:200px;overflow:auto;">
						{if !empty($source_manifests)}
							{foreach from=$source_manifests item=manifest key=manifest_id}
								&#187; <a href="javascript:;" onclick="genericAjaxGet('configFieldSource','c=config&a=getFieldSource&ext_id={$manifest_id}');">{$manifest->name}</a><br>
							{/foreach}
						{/if}
						</div>
					</td>
				</tr>
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configFieldSource">
				{if !empty($ext_id)}
					{assign var=source_manifest value=$source_manifests.$ext_id}
					{include file="$path/configuration/tabs/fields/edit_source.tpl.php" object=$source_manifest}
				{/if}
			</form>
		</td>
		
	</tr>
</table>


