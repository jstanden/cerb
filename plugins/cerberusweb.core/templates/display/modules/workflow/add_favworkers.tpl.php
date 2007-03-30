<input type="hidden" name="c" value="core.display.module.workflow">
<input type="hidden" name="a" value="saveFavoriteWorkers">

<br>
<div class="automod">
<H1>My Favorite Workers</H1>
<b>Add workers separated by commas:</b><br>
<div class="autocomplete" style="width:98%;margin:2px;">
<textarea style="width:98%;height:50px;margin:2px;background-color:rgb(255,255,255);border:1px solid rgb(200,200,200);" name="favWorkerEntry" id="favWorkerEntry" class="autoinput">{foreach from=$favoriteWorkers item=worker name=workers}{$worker->login}{if !$smarty.foreach.workers.last}, {/if}{/foreach}</textarea>
<div id="favWorkerContainer" class="autocontainer"></div>
</div>
</div>
<br>
<input type="button" value="{$translate->_('common.save_changes')|capitalize}" onclick="displayAjax.saveFavWorkers();">
<input type="button" value="{$translate->_('common.cancel')|capitalize}" onclick="toggleDiv('displayWorkflowOptions','none');">
<br>
<br>
<b>All Workers:</b><br>
<div style="background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
{foreach from=$agents item=agent name=agents}
	<a href="javascript:;" onclick="appendTextboxAsCsv('{$moduleLabel}_form','favWorkerEntry',this);">{$agent->login}</a>{if !$smarty.foreach.agents.last}, {/if}
{/foreach}
</div>
