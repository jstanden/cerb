{$pc_uid = uniqid()}
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-mt-3" id="widget{$widget->id}Config">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Data source</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Legend</label>
			<div>
				<input type="hidden" name="params[show_legend]" id="showLegend{$pc_uid}" value="{if $widget->params.show_legend}1{else}0{/if}">
				<div class="cerb-ui-switcher" data-cerb-input="showLegend{$pc_uid}">
					<button type="button" data-value="1"{if $widget->params.show_legend} class="cerb-ui-switcher--active"{/if}>Show</button>
					<button type="button" data-value="0"{if !$widget->params.show_legend} class="cerb-ui-switcher--active"{/if}>Hide</button>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Data from</label>
			{$source = $widget->params.datasource}
			<select name="params[datasource]" class="datasource-selector">
				<option value=""></option>
			{foreach from=$datasource_mfts item=datasource_mft}
				<option value="{$datasource_mft->id}" {if $source==$datasource_mft->id}selected="selected"{/if}>{$datasource_mft->name}</option>
			{/foreach}
			</select>

			<div class="datasource-params cerb-u-mt-2">
				{$datasource = Extension_WorkspaceWidgetDatasource::get($source)}
				{if !empty($datasource) && method_exists($datasource, 'renderConfig')}
					{$datasource->renderConfig($widget, $widget->params)}
				{/if}
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Display as</label>
				{$types = [['number','number'], ['decimal','decimal'], ['percent','percentage'], ['bytes','bytes'], ['seconds','time elapsed']]}
				<select name="params[metric_type]">
					{foreach from=$types item=type}
					<option value="{$type[0]}" {if $widget->params.metric_type==$type[0]}selected="selected"{/if}>{$type[1]}</option>
					{/foreach}
				</select>
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Prepend</label>
				<input type="text" name="params[metric_prefix]" value="{$widget->params.metric_prefix}">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Append</label>
				<input type="text" name="params[metric_suffix]" value="{$widget->params.metric_suffix}">
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $config = $('#widget{$widget->id}Config');

	if(window.CerbUI && CerbUI.Switcher) {
		const $legend = $config.find('#showLegend{$pc_uid}');
		const legendEl = $config.find('[data-cerb-input="showLegend{$pc_uid}"]')[0];
		if(legendEl)
			new CerbUI.Switcher(legendEl, { value: $legend.val(), onSelect: function(value) { $legend.val(value); } });
	}

	$config.find('select.datasource-selector').change(function() {
		const datasource = $(this).val();
		const $div_params = $(this).next('DIV.datasource-params');

		if(datasource.length == 0) {
			$div_params.html('');
		} else {
			genericAjaxGet($div_params, 'c=profiles&a=invoke&module=workspace_widget&action=getWidgetDatasourceConfig&widget_id={$widget->id}&ext_id=' + datasource);
		}
	});
});
</script>
