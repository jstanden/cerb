{$random = time()|cat:mt_rand(1000,9999)}
{$selected_group_id = $params.group_id}
{if empty($selected_group_id)}
	{$selected_group_id = key($groups)}
{/if}

<div id="{$random}_groupbucket">
	<select name="{$namePrefix}[group_id]">
	{foreach from=$groups item=group key=group_id}
		<option value="{$group_id}" {if $selected_group_id==$group_id}selected="selected"{/if}>{$group->name}</option>
	{/foreach}
	</select>
	
	<select name="{$namePrefix}[oper]">
		<option value="in" {if $params.oper=='in'}selected="selected"{/if}>in these buckets</option>
		<option value="!in" {if $params.oper=='!in'}selected="selected"{/if}>not in these buckets</option>
	</select>
	
	<br>
	
	<div class="buckets">
		<label><input type="checkbox" name="{$namePrefix}[bucket_id][]" value="0" {if in_array(0,$params.bucket_id)}checked="checked"{/if}> {'common.inbox'|devblocks_translate|capitalize}</label><br>
		{foreach from=$buckets_by_group.{$selected_group_id} item=bucket key=bucket_id}
		<label><input type="checkbox" name="{$namePrefix}[bucket_id][]" value="{$bucket_id}" {if in_array($bucket_id,$params.bucket_id)}checked="checked"{/if}> {$bucket->name}</label><br>
		{/foreach}
	</div>
</div>

<script type="text/javascript">
$move_to = $('#{$random}_groupbucket');
$move_to.find('select:nth(0)').change(function(e) {
	$move_to = $('#{$random}_groupbucket');

	$select_bucket = $move_to.find('div.buckets');
	$select_bucket.html('');
	
	buckets = {
		{foreach from=$groups item=group key=group_id name=groups}
		"group_{$group_id}":{
			"id":"{$group_id}",
			"buckets":[
			{ "id":0,"name":"{'common.inbox'|devblocks_translate|capitalize}" }{if $buckets_by_group.$group_id},{/if}
			{foreach from=$buckets_by_group.$group_id item=bucket key=bucket_id name=buckets}
				{ "id":{$bucket_id},"name":"{$bucket->name}" }{if !$smarty.foreach.buckets.last},{/if}
			{/foreach}
			]
		}{if !$smarty.foreach.groups.last},{/if}
		{/foreach}
	};
	
	group_id = $(this).val();
	group_key = 'group_' + group_id;
	
	if(null == buckets[group_key])
		return;
	
	group_buckets = buckets[group_key];
	
	for(i in group_buckets.buckets) {
		$select_bucket.append($('<label><input type="checkbox" name="{$namePrefix}[bucket_id][]" value="'+group_buckets.buckets[i].id+'"> '+group_buckets.buckets[i].name+'</label><br>'));
	}
	
	$select_bucket.focus();
});
</script>