<div class="ui-widget">
	<div class="ui-state-error ui-corner-all" style="padding: 0.7em; margin: 0.2em; "> 
		<p>
			<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
			<strong>This professional feature is disabled in the free version.</strong><br>
			<a href="{devblocks_url}c=config&a=settings{/devblocks_url}">(upgrade license)</a>
		</p>
	</div>
</div>

<div class="block">
{foreach from=$plugins item=plugin key=plugin_id}
	{if $plugin->enabled}
		{assign var=show_plugin value=0}
		{foreach from=$acl item=priv key=priv_id}{if $priv->plugin_id==$plugin_id}{assign var=show_plugin value=1}{/if}{/foreach}
		
		{if $show_plugin}				
		<div style="margin-left:10px;background-color:rgb(255,255,221);border:2px solid rgb(255,215,0);padding:2px;margin-bottom:10px;">
		<b>{$plugin->name}</b><br>
		<div id="privs{$plugin_id}" style="margin-top:5px;margin-bottom:5px;">
		{foreach from=$acl item=priv key=priv_id}
			{if $priv->plugin_id==$plugin_id}
			<label style="padding-left:10px;">{$priv->label|devblocks_translate}</label><br>
			{/if}
		{/foreach}
		</div>
		</div>
		{/if}
	{/if}
{/foreach}

</div>