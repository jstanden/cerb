<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div>
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>
<br>

<b>When should this mail be sent?</b> (default: now)<br>
<input type="text" name="{$namePrefix}[delivery_date]" value="{if empty($params.delivery_date)}now{else}{$params.delivery_date}{/if}" size="45" style="width:100%;"><br>
<i>e.g. +2 days; next Monday; tomorrow 8am; 5:30pm; Dec 21 2012</i>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>