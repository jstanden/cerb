<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Generate output using this script</label>
	<textarea rows="3" name="{$namePrefix}[value]" style="white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.value}</textarea>
</div>

<div class="cerb-ui-form--row">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Format</label>
		<select name="{$namePrefix}[format]">
			<option value="" {if $params.format=='text'}selected="selected"{/if}>Text</option>
			<option value="json" {if $params.format=='json'}selected="selected"{/if}>JSON</option>
		</select>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Only set placeholder in simulator mode</label>
		<div>
			<label><input type="radio" name="{$namePrefix}[is_simulator_only]" value="1" {if $params.is_simulator_only}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="{$namePrefix}[is_simulator_only]" value="0" {if !$params.is_simulator_only}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
		</div>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save output to a placeholder named</label>
	<div>
		&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="32" value="{if !empty($params.var)}{$params.var}{else}placeholder{/if}" required="required" spellcheck="false">&#125;&#125;
	</div>
	<div class="cerb-ui-form--help">The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</div>
</div>
