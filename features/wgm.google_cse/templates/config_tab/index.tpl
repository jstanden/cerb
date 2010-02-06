<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>{'wgm.google_cse.cfg.engines'|devblocks_translate|capitalize}</h2></td>
				</tr>
				<tr>
					<td nowrap="nowrap">
						[ <a href="javascript:;" onclick="genericAjaxGet('configEngine','c=google_cse.ajax&a=getConfigEngine&id=0');">{'wgm.google_cse.cfg.engine.add'|devblocks_translate}</a> ]
					</td>
				</tr>
				<tr>
					<td nowrap="nowrap">
						{if !empty($engines)}
							{foreach from=$engines item=engine}
							&#187; <a href="javascript:;" onclick="genericAjaxGet('configEngine','c=google_cse.ajax&a=getConfigEngine&id={$engine->id}');">{$engine->name}</a><br>
							{/foreach}
						{/if}
					</td>
				</tr>
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configEngine">
				{include file="$path/config_tab/edit_engine.tpl" engine=null}
			</form>
		</td>
		
	</tr>
</table>


