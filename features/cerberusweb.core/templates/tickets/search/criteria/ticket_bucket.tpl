<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in" {if $param && $param->operator=='in'}selected="selected"{/if}>{'search.oper.in_list'|devblocks_translate}</option>
		<option value="not in" {if $param && $param->operator=='not in'}selected="selected"{/if}>{'search.oper.in_list.not'|devblocks_translate}</option>
	</select>
</blockquote>

{foreach from=$groups item=group key=group_id}
{if isset($active_worker_memberships.$group_id)}{*censor*}
	<label><b>{$group->name}</b></label>
	<blockquote style="margin:0px 0px 5px 5px;">
		{if isset($group_buckets.$group_id)}
		{foreach from=$group_buckets.$group_id item=bucket}
			<label style="display:inline-block;padding:0px 2px;"><input name="options[]" type="checkbox" value="{$bucket->id}" {if is_array($param->value) && in_array($bucket->id, $param->value)}checked="checked"{/if}> {$bucket->name}</label>
		{/foreach}
		{/if}
	</blockquote>
{/if}
{/foreach}