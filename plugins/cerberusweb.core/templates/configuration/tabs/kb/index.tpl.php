<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>Topics</h2></td>
				</tr>
				<tr>
					<td>
						[ <a href="javascript:;" onclick="genericAjaxGet('configKb','c=config&a=getKbCategory&id=0');">add top-level category</a> ]
					</td>
				</tr>
				<tr>
					<td>
						<div style="margin:0px;padding:3px;width:200px;overflow:auto;">
						{if !empty($categories)}
							{foreach from=$categories item=category key=category_id}
							&#187; <a href="javascript:;" onclick="genericAjaxGet('configKb','c=config&a=getKbCategory&id={$category_id}');">{$category->name}</a><br>
							{/foreach}
						{/if}
						</div>
					</td>
				</tr>
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configKb">
				{include file="$path/configuration/tabs/kb/edit_category.tpl.php" category=null}
			</form>
		</td>
		
	</tr>
</table>

