<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
			<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBlue">
				<tr>
					<td class="tableThBlue">Workers</td>
				</tr>
				<tr>
					<td style="background-color:rgb(220, 220, 255);"><a href="javascript:;" onclick="configAjax.getWorker('0');">add new worker</a></td>
				</tr>
				<tr>
					<td>
						<div style="margin:0px;padding:3px;height:150px;width:200px;overflow:auto;">
						{if !empty($agents)}
							{foreach from=$agents item=agent}
							&#187; <a href="javascript:;" onclick="configAjax.getWorker('{$agent->id}')">{$agent->login}</a><br>
							{/foreach}
						{/if}
						</div>
					</td>
				</tr>
			</table>
		</td>
		
		<td width="100%" valign="top">
			<form action="index.php" method="post" id="configWorker">
				{include file="$path/configuration/workflow/edit_worker.tpl.php" worker=null}
			</form>
		</td>
		
	</tr>
</table>

