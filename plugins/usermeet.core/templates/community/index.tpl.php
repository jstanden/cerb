<H1>Communities</H1>
[ <a href="{devblocks_url}c=community&a=add_tool{/devblocks_url}">add tool</a> ]
[ <a href="{devblocks_url}c=community&a=add_widget{/devblocks_url}">add widget</a> ]
<br>
<br>

{foreach from=$communities item=community key=community_id}
	<div class="block">
	<H2>{$community->name}</H2>
	<a href="{$community->url}" target="_blank">{$community->url}</a><br>
	<br>
	
	{assign var=addons value=$community_addons.$community_id}
	<blockquote style="margin:5px;">
		{assign var=tools value=$addons.tools}
		{assign var=widgets value=$addons.widgets}
		
		{if !empty($tools)}
		<b>Tools:</b><br>
		<ul style="margin-top:0px;">
			{foreach from=$tools item=tool_extid key=tool_code}
				{assign var=tool value=$tool_manifests.$tool_extid}
				<li><a href="{devblocks_url}c=community&a=tool&id={$tool_code}{/devblocks_url}">{$tool->name}</a></li>
			{/foreach}
		</ul>
		{/if}
	
		{if !empty($widgets)}
		<b>Widgets:</b><br>
		<ul style="margin-top:0px;">
			<li>Network Status: <a href="http://{$host}{devblocks_url}c=portal&app=forums&key=CERB{/devblocks_url}">http://{$host}{devblocks_url}c=portal&app=forums&key=CERB{/devblocks_url}</a></li>
		</ul>
		{/if}
	</blockquote>
	</div>
	<br>
{/foreach}

<div class="block">
<H2>Create Community</H2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="createCommunity">

<b>Community Name:</b><br>
<input type="text" name="name" size="45" maxlength="64"><br>
<br>

<b>Homepage URL:</b><br>
<input type="text" name="url" size="64" maxlength="128"><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>

</form>
</div>
