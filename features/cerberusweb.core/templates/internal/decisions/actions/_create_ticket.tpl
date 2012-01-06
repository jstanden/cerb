<b>{'message.header.to'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<select name="{$namePrefix}[group_id]">
		{foreach from=$groups item=group key=group_id}
		<option value="{$group_id}" {if $group_id==$params.group_id}selected="selected"{/if}>{$group->name}</option>
		{/foreach}
	</select>
</div>

<b>{'ticket.requesters'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<input type="text" name="{$namePrefix}[requesters]" value="{$params.requesters}" size="45" style="width:100%;">
</div>

<b>{'message.header.subject'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" size="45" style="width:100%;" class="placeholders">
</div>

<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>