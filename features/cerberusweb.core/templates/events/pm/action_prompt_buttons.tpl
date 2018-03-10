<b>{'common.options'|devblocks_translate|capitalize}:</b> (one per line)
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[options]" rows="5" cols="45" style="width:100%;height:150px;" class="placeholders">{$params.options}</textarea>
</div>

<b>Colors:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[color_from]" value="{$params.color_from|default:'#4795F7'}" size="7" class="color-picker">
	<input type="text" name="{$namePrefix}[color_mid]" value="{$params.color_mid|default:'#4795F7'}" size="7" class="color-picker">
	<input type="text" name="{$namePrefix}[color_to]" value="{$params.color_to|default:'#4795F7'}" size="7" class="color-picker">
</div>

<b>Custom CSS style:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[style]" style="width:100%;" value="{$params.style}" class="placeholders" placeholder="font-size:48px;">
</div>

<b>Save the response to a placeholder named:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="32" value="{if !empty($params.var)}{$params.var}{else}placeholder{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>
<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	
	$action.find('input:text.color-picker').minicolors({
		swatches: ['#CF2C1D','#FEAF03','#57970A','#4795F7','#7047BA','#D5D5D5','#ADADAD','#34434E','#FFFFFF']
	});
});
</script>
