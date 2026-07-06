<div id="widget{$widget->id}Config" class="cerb-ui-form" style="margin-top:10px;">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
			<div class="cerb-ui-header--title-sm">Run this data query:</div>
			<div class="cerb-ui-header--right">
				{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
			</div>
		</div>

		<textarea id="widget{$widget->id}DataQuery" name="params[data_query]" data-editor-lines="12" spellcheck="false">{$widget->extension_params.data_query}</textarea>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Chart options:</div>
		</div>

		<div class="cerb-ui-form">
			{$chart_types = [ 'donut' => 'donut', 'pie' => 'pie' ] }

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Display the chart as</label>
				<select name="params[chart_as]">
					{foreach from=$chart_types item=label key=key}
					<option value="{$key}" {if $widget->extension_params.chart_as == $key}selected="selected"{/if}>{$label}</option>
					{/foreach}
				</select>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">The chart height is</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
					<input type="text" size="5" maxlength="4" name="params[height]" placeholder="(auto)" value="{$widget->extension_params.height}" style="width:6em;flex:0 0 auto;">
					<span class="cerb-u-text-muted">pixels</span>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Use these options</label>
				{$uid = uniqid()}
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-toggle"><input type="checkbox" name="params[options][show_legend]" id="{$uid}" value="1" {if $widget->extension_params.options.show_legend}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
					<label for="{$uid}">Show legend</label>
				</div>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');

	const dq = new CerbUI.DataQuery($config.find('#widget{$widget->id}DataQuery')[0], {
		onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource(),
		toolbar: true,
	});
});
</script>