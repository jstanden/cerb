<b>{'common.bucket'|devblocks_translate|capitalize}:</b>
<select name="{$namePrefix}[bucket_id]">
	<option value="0">- {'common.inbox'|devblocks_translate|capitalize} -</option>
	{foreach from=$buckets item=bucket key=bucket_id}
	<option value="{$bucket_id}" {if $bucket_id==$params.bucket_id}selected="selected"{/if}>{$bucket->name}</option>
	{/foreach}
</select>
<br>
