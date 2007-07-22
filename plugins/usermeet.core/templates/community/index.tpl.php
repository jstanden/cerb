<H1>Communities</H1>
<!-- [ <a href="{devblocks_url}c=community&a=add_tool{/devblocks_url}">add tool</a> ]  -->
<!-- [ <a href="{devblocks_url}c=community&a=add_widget{/devblocks_url}">add widget</a> ]  -->
<br>

<div class="block">
{if !empty($communities)}
{foreach from=$communities item=community key=community_id}
	<H2>{$community->name}</H2>
	
	{assign var=addons value=$community_addons.$community_id}
	<blockquote style="margin:5px;">
		{assign var=tools value=$addons.tools}
		{assign var=widgets value=$addons.widgets}
		
		{if !empty($tools)}
		<ul style="margin-top:0px;">
			{foreach from=$tools item=tool_extid key=tool_code}
				{assign var=tool value=$tool_manifests.$tool_extid}
				<li><a href="{devblocks_url}c=community&a=tool&id={$tool_code}{/devblocks_url}">{$tool->name}</a></li>
			{/foreach}
		</ul>
		{/if}
	
		{*
		{if !empty($widgets)}
		<b>Widgets:</b><br>
		<ul style="margin-top:0px;">
			<li>Network Status: <a href="http://{$host}{devblocks_url}c=portal&app=forums&key=CERB{/devblocks_url}">http://{$host}{devblocks_url}c=portal&app=forums&key=CERB{/devblocks_url}</a></li>
		</ul>
		{/if}
		*}
	</blockquote>
{/foreach}
<br>
{/if}

{if !empty($communities)}
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="addCommunityTool">
Add new <select name="extension_id">
	{foreach from=$tool_manifests item=tool}
	<option value="{$tool->id}">{$tool->name}</option>
	{/foreach}
</select>
to community <select name="community_id">
	{foreach from=$communities item=community}
	<option value="{$community->id}">{$community->name}</option>
	{/foreach}
</select>
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</form>
<br>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="createCommunity">
Add community: <input type="text" name="name" size="40" maxlength="64">
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</form>

</div>
<br>
