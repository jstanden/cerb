{include file="file:$path/configuration/menu.tpl.php"}
<br>

<h2>Fetch &amp; Retrieve&trade; - Knowledge Resources</h2>

<div class="block">

<form action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<button type="button" onclick="genericAjaxPanel('c=config&a=showFnrResourcePanel',this,false,'400px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_new.gif{/devblocks_url}" align="top"> {"Add Knowledge Resource"|capitalize}</button>
</form>

{foreach from=$topics item=topic key=topic_id name=topics}
<div class="subtle">
	<h2 style="display:inline;">{$topic->name}</h2>&nbsp;
	<a href="javascript:;" onclick="genericAjaxPanel('c=config&a=showFnrTopicPanel&id={$topic_id}',this,false,'400px');">edit topic</a>
	<br>
	{assign var=resources value=$topic->getResources()}
	{if !empty($resources)}
	<ul style="margin-top:0px;">
		{foreach from=$resources item=resource key=resource_id name=resources}
			<li><a href="javascript:;" onclick="genericAjaxPanel('c=config&a=showFnrResourcePanel&id={$resource_id}',this,false,'400px');">{$resource->name}</a></li>
		{/foreach}
	</ul>
	{/if}
</div>
{/foreach}

</div>

<script>
	var configAjax = new cConfigAjax();
</script>