{foreach from=$workers item=worker}
<fieldset style="">
	<div style="float:left;">
		<img src="{if $is_ssl}https://secure.{else}http://www.{/if}gravatar.com/avatar/{$worker->email|trim|lower|md5}?s=64&d={devblocks_url full=true}c=resource&p=cerberusweb.core&f=images/wgm/gravatar_nouser.jpg{/devblocks_url}" border="0" height="64" width="64" style="margin:0px 5px 5px 0px;">
	</div>
	<div style="float:left;">
		<a href="{devblocks_url}c=profiles&k=worker&id={$worker->id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}" style="color:rgb(0,120,0);font-weight:bold;font-size:150%;margin:0px;">{$worker->getName()}</a><br>
		{if !empty($worker->title)}{$worker->title}<br>{/if}
		
		{$memberships = $worker->getMemberships()}
		{if !empty($memberships)}
		<ul class="bubbles">
		{foreach from=$memberships item=member key=group_id name=groups}
			{$group = $groups.{$group_id}}
			<li><a href="{devblocks_url}c=profiles&k=group&id={$group->id}-{$group->name|devblocks_permalink}{/devblocks_url}" style="{if $member->is_manager}font-weight:bold;{/if}">{$group->name}</a></li>
		{/foreach}
		</ul>
		{/if}
		
	</div>
	{if $active_worker->is_superuser}
	<div style="float:right;">
		{if $worker->id != $active_worker->id}<button type="button" onclick="genericAjaxGet('','c=internal&a=su&worker_id={$worker->id}',function(o) { window.location.reload(); });"><span class="cerb-sprite2 sprite-user-silhouette"></span> Impersonate</button>{/if}
		<button type="button" onclick="$popup = genericAjaxPopup('peek','c=config&a=handleSectionAction&section=workers&action=showWorkerPeek&id={$worker->id}',null,false,'550');	$popup.one('worker_save', function(event) {	event.stopPropagation(); window.location.reload(); });"><span class="cerb-sprite sprite-document_edit"></span> {'common.edit'|devblocks_translate|capitalize}</button>
	</div>
	{/if}
</fieldset>
{/foreach}
