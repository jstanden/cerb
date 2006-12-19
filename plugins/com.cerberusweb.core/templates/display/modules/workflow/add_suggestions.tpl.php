<input type="hidden" name="c" value="core.display.module.workflow">
<input type="hidden" name="a" value="suggestAgents">
<input type="hidden" name="id" value="{$ticket->id}">

<br>
<H1>Suggest Workers</H1>
<b>Add workers separated by commas:</b><br>
<textarea style="width:98%;height:50px;margin:2px;background-color:rgb(255,255,255);border:1px solid rgb(200,200,200);" name="agentEntry"></textarea>
<br>
<input type="button" value="Suggest Workers" onclick="displayAjax.submitWorkflow();">
<input type="button" value="Cancel" onclick="toggleDiv('displayWorkflowOptions','none');">
<br>
<br>
<table width="100%" cellpadding="0" cellspacing="0">
	<tr>
		<td width="50%" valign="top">
			<b>Favorite Workers:</b><br>
			<div style="background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
			{foreach from=$favoriteWorkers item=worker name=workers}
				<a href="javascript:;" onclick="appendTextboxAsCsv('{$moduleLabel}_form','agentEntry',this);">{$worker->login}</a>{if !$smarty.foreach.workers.last}, {/if}
			{/foreach}
			</div>
		</td>
		<td width="50%" valign="top">
			<b>All Workers:</b><br>
			<div style="background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
			{foreach from=$agents item=agent name=agents}
				<a href="javascript:;" onclick="appendTextboxAsCsv('{$moduleLabel}_form','agentEntry',this);">{$agent->login}</a>{if !$smarty.foreach.agents.last}, {/if}
			{/foreach}
			</div>
		</td>
	</tr>
</table>