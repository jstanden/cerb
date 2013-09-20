<select name="{$namePrefix}[calendar_id]">
	{foreach from=$calendars item=calendar key=calendar_id}
	<option value="{$calendar->id}" {if $params.calendar_id==$calendar->id}selected="selected"{/if}>{$calendar->name}</option>
	{/foreach}
</select>

 is 
 
<select name="{$namePrefix}[is_available]">
	<option value="1" {if $params.is_available}selected="selected"{/if}>Available</option>
	<option value="0" {if !$params.is_available}selected="selected"{/if}>Busy</option>
</select>
<br>

<b>{'search.date.between'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="{$namePrefix}[from]" size="20" value="{$params.from}" style="width:98%;"><br>
	-{'search.date.between.and'|devblocks_translate}-<br>
	<input type="text" name="{$namePrefix}[to]" size="20" value="{$params.to}" style="width:98%;"><br>
</blockquote>

<script type="text/javascript">
$condition = $('li#{$namePrefix}');
</script>

