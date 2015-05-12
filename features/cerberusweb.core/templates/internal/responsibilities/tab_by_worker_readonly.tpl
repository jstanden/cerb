{foreach from=$groups item=group key=group_id}
{if $active_worker->isGroupMember($group_id)}
<fieldset class="peek" style="margin-bottom:0;">
	<legend>{$group->name}</legend>
	
	<div style="padding-left:10px;">
	
		{foreach from=$group->getBuckets() item=bucket key=bucket_id}
		{$responsibility_level = $responsibilities.$bucket_id}
		<div style="width:250px;display:inline-block;margin:0 10px 10px 5px;">
			<label><b>{$bucket->name}</b></label>
			
			<div style="margin-top:5px;position:relative;margin-left:5px;width:250px;height:10px;background-color:rgb(230,230,230);border-radius:10px;">
				<span style="display:inline-block;background-color:rgb(200,200,200);height:18px;width:1px;position:absolute;top:-4px;margin-left:1px;left:50%;"></span>
				<div style="position:relative;margin-left:-6px;top:-3px;left:{$responsibility_level}%;width:15px;height:15px;border-radius:15px;background-color:{if $responsibility_level < 50}rgb(230,70,70);{elseif $responsibility_level > 50}rgb(0,200,0);{else}rgb(175,175,175);{/if}"></div>
			</div>
			
		</div>
		{/foreach}
		
	</div>
</fieldset>
{/if}
{/foreach}