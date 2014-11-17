<div>
	<select name="{$namePrefix}[value]">
		{foreach from=$options item=option}
		<option value="{$option}" {if $params.value == $option}selected="selected"{/if}>{$option}</option>
		{/foreach}
	</select>
</div>

<script type="text/javascript">
//$condition = $('fieldset#{$namePrefix}');
</script>
