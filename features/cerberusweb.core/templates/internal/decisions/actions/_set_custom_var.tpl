<b>Generate output using this script:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea rows="3" cols="60" name="{$namePrefix}[value]" style="width:100%;white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.value}</textarea>
</div>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td style="padding-right:20px;">
			<b>Format:</b>
			<div style="margin-left:10px;margin-bottom:10px;">
				<select name="{$namePrefix}[format]">
					<option value="" {if $params.format=='text'}selected="selected"{/if}>Text</option>
					<option value="json" {if $params.format=='json'}selected="selected"{/if}>JSON</option>
				</select>
			</div>
		</td>
		
		<td>
			<b>Only set placeholder in simulator mode:</b>
			<div style="margin-left:10px;margin-bottom:10px;">
				<label><input type="radio" name="{$namePrefix}[is_simulator_only]" value="1" {if $params.is_simulator_only}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="{$namePrefix}[is_simulator_only]" value="0" {if !$params.is_simulator_only}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
			</div>
		</td>
	</tr>
</table>

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
