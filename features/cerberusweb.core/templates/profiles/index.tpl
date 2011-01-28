<ul class="submenu">
</ul>
<div style="clear:both;"></div>

{foreach from=$workers item=worker}
<fieldset style="">
	<div style="float:left;">
		<img src="{if $is_ssl}https://secure.{else}http://www.{/if}gravatar.com/avatar/{$worker->email|trim|lower|md5}?s=64&d=mm" border="0" style="margin:0px 5px 5px 0px;">
	</div>
	<div style="float:left;">
		<a href="{devblocks_url}c=profiles&k=worker&id={$worker->id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}" style="color:rgb(0,120,0);font-weight:bold;font-size:150%;margin:0px;">{$worker->getName()}</a><br>
		{if !empty($worker->title)}{$worker->title}<br>{/if}
		{if !empty($worker->email)}{$worker->email}<br>{/if}
		
		{$memberships = $worker->getMemberships()}
		{if !empty($memberships)}
		<div style="margin:5px 0px;">
			Member of: 
			{foreach from=$memberships item=member key=group_id name=groups}
				{$group = $groups.{$group_id}}
				<a href="{devblocks_url}c=profiles&k=group&id={$group->id}-{$group->name|devblocks_permalink}{/devblocks_url}" style="{if $member->is_manager}font-weight:bold;{/if}">{$group->name}</a>{if !$smarty.foreach.groups.last}, {/if}
			{/foreach}
		</div>
		{/if}
		
	</div>
	{*
	{if $active_worker->is_superuser}
	<div style="float:right;">
		<button type="button" id="btnProfileWorkerEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
	</div>
	{/if}
	*}
</fieldset>
{/foreach}

{*
<fieldset>
	<legend>Groups</legend>
	<ul style="margin:0px;">
	{foreach from=$groups item=group}
		<li style="margin-bottom:5px;">
			<a href="{devblocks_url}c=profiles&k=group&id={$group->id}-{$group->name|devblocks_permalink}{/devblocks_url}"><b>{$group->name}</b></a>
			<ul style="margin:0px;">
			{foreach from=$group->getMembers() item=member name=members}
				{if isset($workers.{$member->id})}
					{$worker = $workers.{$member->id}}
					<li><a href="{devblocks_url}c=profiles&k=worker&id={$worker->id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}">{$worker->getName()}</a></li>
				{/if}
			{/foreach}
			</ul>
		</li>
	{/foreach}
	</ul>
</fieldset>
*}