{assign var=tags value=$ticket->getTags()}
<b>Tags:</b> 
<img src="images/bookmark_add.gif" align="absmiddle"> <a href="javascript:;" onclick="displayAjax.showApplyTagDialog(this);">Add Tags</a>
<img src="images/preferences.gif" align="absmiddle"> <a href="javascript:;" onclick="displayAjax.showFavTags();">Manage Favorites</a>
<br>
<div style="width:98%;background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
{if !empty($tags)}
{foreach from=$tags item=tag name=tags}
	<a href="javascript:;" onclick="displayAjax.showTag('{$tag->id}',this);">{$tag->name}</a>{if !$smarty.foreach.tags.last}, {/if}
{/foreach}
{else}
	No tags.
{/if}
</div>

<img src="images/spacer.gif" width="1" height="5"><br>

{assign var=flaggedAgents value=$ticket->getFlaggedWorkers()}
{assign var=suggestedAgents value=$ticket->getSuggestedWorkers()}

<b>Workers:</b> 
<img src="images/flag_red.gif" align="absmiddle"> <a href="javascript:;" onclick="displayAjax.showFlagAgents();">Assign Workers</a>
<img src="images/businessman_add.gif" align="absmiddle"> <a href="javascript:;" onclick="displayAjax.showSuggestAgents();" style="font-style:italic;">Suggest Workers</a>
<img src="images/preferences.gif" align="absmiddle"> <a href="javascript:;" onclick="displayAjax.showFavWorkers();">Manage Favorites</a>
<br>
<div style="width:98%;background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
{if !empty($flaggedAgents)}
{foreach from=$flaggedAgents item=agent name=agents}
	<a href="javascript:;" onclick="displayAjax.showAgent('{$agent->id}',this);">{$agent->login}</a>{if !$smarty.foreach.agents.last}, {/if}
{/foreach}
{/if}

{if !empty($suggestedAgents)}
{if !empty($flaggedAgents)}, {/if}
{foreach from=$suggestedAgents item=agent name=agents}
	<a href="javascript:;" onclick="displayAjax.showAgent('{$agent->id}',this);" style="font-style:italic;">{$agent->login}</a>{if !$smarty.foreach.agents.last}, {/if}
{/foreach}
{/if}

{if empty($flaggedAgents) && empty($suggestedAgents)}
	No workers.
{/if}
</div>

<span style="display:none;" id="displayWorkflowOptions"></span>