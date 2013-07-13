<b>Generate output using this script:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea rows="3" cols="60" name="{$namePrefix}[value]" style="width:100%;" class="placeholders" spellcheck="false">{$params.value}</textarea>
</div>

<b>Save output to a placeholder named:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="24" value="{if !empty($params.var)}{$params.var}{else}placeholder{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>
