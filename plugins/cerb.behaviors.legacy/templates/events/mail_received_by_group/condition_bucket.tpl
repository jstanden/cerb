<select name="{$namePrefix}[oper]">
	<option value="in" {if $params.oper=='in'}selected="selected"{/if}>is any of</option>
	<option value="!in" {if $params.oper=='!in'}selected="selected"{/if}>is not any of</option>
</select>
<br>

{foreach from=$buckets item=bucket key=bucket_id}
<label><input type="checkbox" name="{$namePrefix}[bucket_ids][]" value="{$bucket_id}" {if is_array($params.bucket_ids) && in_array($bucket_id, $params.bucket_ids)}checked="checked"{/if}> {$bucket->name}</label><br>
{/foreach}
