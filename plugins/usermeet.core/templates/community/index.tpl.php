<H1>Community</H1>
<br>

{foreach from=$communities item=community key=community_id}
	<H2>{$community->name}</H2>
		
	{assign var=addons value=$community_addons.$community_id}
	<blockquote>
		{assign var=tools value=$addons.tools}
		{assign var=widgets value=$addons.widgets}
		
		{if !empty($tools)}
		<b>Tools</b><br>
		<ul style="margin-top:0px;">
			{foreach from=$tools item=tool_extid key=tool_code}
				{assign var=tool value=$tool_manifests.$tool_extid}
				<li><a href="{devblocks_url}c=community&a=tool&id={$tool_code}{/devblocks_url}">{$tool->name}</a></li>
			{/foreach}
		</ul>
		{/if}
	
		{if !empty($widgets)}
		<b>Widgets</b><br>
		<ul style="margin-top:0px;">
			<li>Network Status: <a href="http://{$host}{devblocks_url}c=portal&app=forums&key=CERB{/devblocks_url}">http://{$host}{devblocks_url}c=portal&app=forums&key=CERB{/devblocks_url}</a></li>
		</ul>
		{/if}
	</blockquote>
{/foreach}

<br>

<div class="block">
<H2>Add Community Tool</H2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="addCommunityTool">

<b>Community:</b><br>
<select name="community_id">
	{foreach from=$communities item=community}
	<option value="{$community->id}">{$community->name}</option>
	{/foreach}
</select><br>
<br>

<b>Tool:</b><br>
<select name="extension_id">
	{foreach from=$tool_manifests item=tool}
	<option value="{$tool->id}">{$tool->name}</option>
	{/foreach}
</select><br>
<br>

<button onclick="this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>

</form>
</div>

<br>

<div class="block">
<H2>Add Community Widget</H2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="addCommunityWidget">

<b>Community:</b><br>
<select name="community_id">
	{foreach from=$communities item=community}
	<option value="{$community->id}">{$community->name}</option>
	{/foreach}
</select><br>
<br>

<b>Widget:</b><br>
<select name="extension_id">
	{foreach from=$widget_manifests item=widget}
	<option value="{$widget->id}">{$widget->name}</option>
	{/foreach}
</select><br>
<br>

<button onclick="this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</form>
</div>

<br>

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

<button onclick="this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>

</form>
</div>
