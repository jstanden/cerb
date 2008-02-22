<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveOverviewFilter">
<h1>Filter</h1>

Hide these buckets from my overview:<br>
<div id="" style="height:300px;overflow:auto;background-color:rgb(255,255,255);padding:5px;border:1px solid rgb(180,180,180);">
{foreach from=$groups key=group_id item=group}
	{assign var=counts value=$group_counts.$group_id}
	{if isset($active_worker_memberships.$group_id) && !empty($group_buckets.$group_id)}
	<span style="font-weight:bold;">{$groups.$group_id->name}</span> 
	<div id="" style="display:block;padding-left:10px;padding-bottom:0px;">
	{foreach from=$group_buckets.$group_id key=bucket_id item=b}
		<label><input type="checkbox" name="hide_bucket_ids[]" value="{$bucket_id}" {if isset($hide_bucket_ids.$bucket_id)}checked{/if}> {if isset($hide_bucket_ids.$bucket_id)}<i>{$b->name}</i>{else}{$b->name} ({$counts.$bucket_id|string_format:"%d"}){/if}</label><br>
	{/foreach}
	</div>
	{/if}
{/foreach}
</div>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>

</form>