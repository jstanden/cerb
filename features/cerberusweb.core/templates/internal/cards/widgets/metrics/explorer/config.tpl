<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Default series:</div>
		</div>

		<div style="opacity:0.7;margin-bottom:5px;">
			Define the starting series as KATA. Placeholders like <code>{literal}{{record_name}}{/literal}</code>
			and <code>{literal}{{record_id}}{/literal}</code> adapt to the current record. Use the Explorer's
			<b>Copy explorer config</b> button to generate this.
		</div>

		<div class="cerb-ui-kataeditor">
			<div class="cerb-ui-kataeditor--gutter" aria-hidden="true"></div>
			<div class="cerb-ui-kataeditor--field">
				<div class="cerb-ui-kataeditor--highlight" aria-hidden="true"></div>
				<textarea class="cerb-ui-kataeditor--input" name="params[series_kata]" data-editor-lines="10" spellcheck="false">{$widget->extension_params.series_kata}</textarea>
				<span class="cerb-ui-kataeditor--caret-anchor"></span>
			</div>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Defaults:</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Default time range</label>
				<input type="text" name="params[range]" value="{$widget->extension_params.range|default:'-24 hours to now'}">
			</div>

			{$periods = ['minute' => '5 minutes', 'hour' => 'Hour', 'day' => 'Day']}

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Default period</label>
				<select name="params[period]">
					{foreach from=$periods item=label key=key}
					<option value="{$key}" {if ($widget->extension_params.period|default:'hour') == $key}selected="selected"{/if}>{$label}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');

	new CerbUI.KataEditor($config.find('textarea[name="params[series_kata]"]').closest('.cerb-ui-kataeditor')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataSchemaMetricsExplorerSeries)
	});
});
</script>