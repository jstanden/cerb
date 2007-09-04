<H1 style="display:inline;">Communities</H1>&nbsp;
[ <a href="javascript:;" onclick="genericAjaxPanel('c=community&a=showCommunityPanel&id=0',this,false,'400px');">add community</a> ]
<br>
<br>
<!-- [ <a href="{devblocks_url}c=community&a=add_tool{/devblocks_url}">add tool</a> ]  -->
<!-- [ <a href="{devblocks_url}c=community&a=add_widget{/devblocks_url}">add widget</a> ]  -->

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

{if !empty($communities)}
{foreach from=$communities item=community key=community_id}
<div class="block">
	<H2 style="display:inline;">{$community->name}</H2> &nbsp;
	<a href="javascript:;" onclick="genericAjaxPanel('c=community&a=showCommunityPanel&id={$community->id}',this,false,'400px');">edit</a><br>
	
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
</div>
<br>
{/foreach}
{/if}

<br>
