<form action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=config&a=showFnrResourcePanel',this,false,'400px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_new.gif{/devblocks_url}" align="top"> {"Add Knowledge Resource"|capitalize}</button>
</form>

{foreach from=$topics item=topic key=topic_id name=topics}
<div class="block">
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

<script type="text/javascript">
	var configAjax = new cConfigAjax();
</script>