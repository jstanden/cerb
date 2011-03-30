<select name="{$namePrefix}[oper]">
	<option value="in" {if $params.oper=='in'}selected="selected"{/if}>is any of</option>
	<option value="!in" {if $params.oper=='!in'}selected="selected"{/if}>is not any of</option>
</select>
<br>

<label><input type="checkbox" name="{$namePrefix}[bucket_ids][]" value="0" {if in_array(0,$params.bucket_ids)}checked="checked"{/if}> {'common.inbox'|devblocks_translate|capitalize}</label><br>

{foreach from=$buckets item=bucket key=bucket_id}
<label><input type="checkbox" name="{$namePrefix}[bucket_ids][]" value="{$bucket_id}" {if in_array($bucket_id,$params.bucket_ids)}checked="checked"{/if}> {$bucket->name}</label><br>
{/foreach}
