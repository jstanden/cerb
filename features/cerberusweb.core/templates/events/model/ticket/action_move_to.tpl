{$random = time()|cat:mt_rand(1000,9999)}

<div id="{$random}_moveto">
<select name="{$namePrefix}[group_id]">
	{foreach from=$groups item=group key=group_id}
	<option value="{$group_id}" {if $group_id==$params.group_id}selected="selected"{/if}>{$group->name}</option>
	{/foreach}
</select><!-- 
--><select name="{$namePrefix}[bucket_id]">
<option value="0">{'common.inbox'|devblocks_translate|capitalize}</option>
{foreach from=$group_buckets.{$params.group_id} item=bucket key=bucket_id name=buckets}
	<option value="{$bucket_id}" {if $params.bucket_id==$bucket_id}selected="selected"{/if}>{$bucket->name}</option>
{/foreach}
</select>
</div>

<script type="text/javascript">
$move_to = $('#{$random}_moveto');
$move_to.find('select:nth(0)').change(function(e) {
	$move_to = $('#{$random}_moveto');

	$select_bucket = $move_to.find('select:nth(1)');
	$select_bucket.html('');
	
	buckets = {
		{foreach from=$groups item=group key=group_id name=groups}
		"group_{$group_id}":{
			"id":"{$group_id}",
			"buckets":[
			{ "id":0,"name":"{'common.inbox'|devblocks_translate|capitalize}" }{if $group_buckets.$group_id},{/if}
			{foreach from=$group_buckets.$group_id item=bucket key=bucket_id name=buckets}
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
		$select_bucket.append($('<option value="'+group_buckets.buckets[i].id+'">'+group_buckets.buckets[i].name+'</option>'));
	}
	
	$select_bucket.focus();
});
</script>