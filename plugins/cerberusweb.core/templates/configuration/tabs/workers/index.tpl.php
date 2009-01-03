<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>Workers</h2></td>
				</tr>
				<tr>
					<td>
						{* [WGM]: Please respect our licensing and support the project! *}
						{if ((empty($license) || empty($license.key)) && count($workers) >= 3) || (!empty($license.key)&&!empty($license.users)&&count($workers)>=$license.users)}
						You have reached the number of workers permitted by your license.<br>
						[ <a href="{devblocks_url}c=config&a=settings{/devblocks_url}" style="color:rgb(0,160,0);">Enter License</a> ]
						[ <a href="http://www.cerberusweb.com/buy" target="_blank" style="color:rgb(0,160,0);">Buy License</a> ]
						{else}
						[ <a href="javascript:;" onclick="configAjax.getWorker('0');">add new worker</a> ]
						{/if}
					</td>
				</tr>
				<tr>
					<td>
						<div style="margin:0px;padding:3px;width:200px;overflow:auto;">
						{if !empty($workers)}
							{foreach from=$workers item=agent}
							{assign var=worker_name value=$agent->getName(true)}
							&#187; <a href="javascript:;" onclick="configAjax.getWorker('{$agent->id}')" title="{if !empty($agent->title)}{$agent->title}{/if}" style="{if $agent->is_disabled}color:rgb(120,0,0);font-style:italic;{elseif $agent->is_superuser}{/if}">{if !empty($worker_name)}{$worker_name}{else}{$agent->email}{/if}</a><br>
							{/foreach}
						{/if}
						</div>
					</td>
				</tr>
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configWorker">
				{include file="$path/configuration/tabs/workers/edit_worker.tpl.php" worker=null}
			</form>
		</td>
		
	</tr>
</table>


