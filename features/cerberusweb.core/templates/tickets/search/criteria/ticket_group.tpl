<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{$translate->_('search.oper.in_list')}</option>
		<option value="not in">{$translate->_('search.oper.in_list.not')}</option>
	</select>
</blockquote>

<b>{$translate->_('common.groups')|capitalize}:</b><br>
{foreach from=$groups item=group key=group_id}
{if isset($active_worker_memberships.$group_id)}{*censor*}
	<label><input name="group_id[]" type="checkbox" value="{$group_id}" onclick="toggleDiv('searchGroup{$id}{$group_id}',(this.checked)?'block':'none');"><span style="font-weight:bold;color:rgb(0,120,0);">{$group->name}</span></label><br>
	<blockquote style="margin:0px;margin-left:10px;display:none;" id="searchGroup{$id}{$group_id}">
		<label><input name="bucket_id[]" type="checkbox" value="0"><span style="font-size:90%;">Inbox</span></label><br>
		{if isset($group_buckets.$group_id)}
		{foreach from=$group_buckets.$group_id item=bucket}
			<label><input name="bucket_id[]" type="checkbox" value="{$bucket->id}"><span style="font-size:90%;">{$bucket->name}</span></label><br>
		{/foreach}
		{/if}
	</blockquote>
{/if}
{/foreach}
