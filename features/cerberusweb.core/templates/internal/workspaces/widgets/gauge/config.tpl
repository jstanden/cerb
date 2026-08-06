<div id="widget{$widget->id}ConfigTabs">
	<ul>
		<li><a href="#widget{$widget->id}ConfigTabDatasource">Data Source</a></li>
		<li><a href="#widget{$widget->id}ConfigTabThresholds">Thresholds</a></li>
	</ul>

	<div id="widget{$widget->id}ConfigTabThresholds" class="cerb-ui-form cerb-u-mt-3">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Minimum value</label>
			<div><input type="text" name="params[metric_min]" value="{$widget->params.metric_min|default:0}" placeholder="0" style="width:8em;"></div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Thresholds</label>
			<table style="width:100%;border-collapse:collapse;">
				<thead>
					<tr class="cerb-u-text-muted cerb-u-fs-n1 cerb-u-text-uppercase">
						<td style="width:50%;padding:0 10px 4px 0;">Label</td>
						<td style="width:30%;padding:0 10px 4px 0;">Max. value</td>
						<td style="width:20%;padding:0 0 4px 0;">Color</td>
					</tr>
				</thead>
				<tbody>
					{section name=thresholds loop=7}
					<tr>
						<td style="padding:0 10px 6px 0;" valign="top">
							<input type="text" name="params[threshold_labels][]" value="{$widget->params.threshold_labels.{$smarty.section.thresholds.index}}" style="width:100%;">
						</td>
						<td style="padding:0 10px 6px 0;" valign="top">
							<input type="text" name="params[threshold_values][]" value="{$widget->params.threshold_values.{$smarty.section.thresholds.index}}" style="width:100%;">
						</td>
						<td style="padding:0 0 6px 0;" valign="top">
							<input type="text" name="params[threshold_colors][]" value="{$widget->params.threshold_colors.{$smarty.section.thresholds.index}}" style="width:100%;" class="color-picker">
						</td>
					</tr>
					{/section}
				</tbody>
			</table>
		</div>
	</div>

	<div id="widget{$widget->id}ConfigTabDatasource" class="cerb-ui-form cerb-u-mt-3">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Data from</label>
			{$source = $widget->params.datasource}
			<select name="params[datasource]" class="datasource-selector">
				<option value=""></option>
				{foreach from=$datasource_mfts item=datasource_mft}
				<option value="{$datasource_mft->id}" {if $source==$datasource_mft->id}selected="selected"{/if}>{$datasource_mft->name}</option>
				{/foreach}
			</select>
		</div>

		<div class="datasource-params">
			{$datasource = Extension_WorkspaceWidgetDatasource::get($source)}
			{if !empty($datasource) && method_exists($datasource, 'renderConfig')}
				{$datasource->renderConfig($widget, $widget->params)}
			{/if}
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field cerb-u-flex-2">
				<label class="cerb-ui-form--label">Display as</label>
				{$types = [['number','number'], ['decimal','decimal'], ['percent','percentage'], ['bytes','bytes'], ['seconds','secs elapsed'], ['minutes','mins elapsed']]}
				<select name="params[metric_type]">
					{foreach from=$types item=type}
					<option value="{$type[0]}" {if $widget->params.metric_type==$type[0]}selected="selected"{/if}>{$type[1]}</option>
					{/foreach}
				</select>
			</div>
			<div class="cerb-ui-form--field cerb-u-flex-1">
				<label class="cerb-ui-form--label">Prepend</label>
				<input type="text" name="params[metric_prefix]" value="{$widget->params.metric_prefix}">
			</div>
			<div class="cerb-ui-form--field cerb-u-flex-1">
				<label class="cerb-ui-form--label">Append</label>
				<input type="text" name="params[metric_suffix]" value="{$widget->params.metric_suffix}">
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $tabs = $('#widget{$widget->id}ConfigTabs');
	$tabs.find('> ul').each(function() { if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this); });

	$tabs.find('input:text.color-picker').each(function() {
		new CerbUI.ColorPicker(this, {
			palette: ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#D5D5D5','#ADADAD','#34434E']
		});
	});

	const $datasource_tab = $('#widget{$widget->id}ConfigTabDatasource');

	$datasource_tab.find('select.datasource-selector').change(function() {
		const datasource = $(this).val();
		const $div_params = $datasource_tab.find('DIV.datasource-params');

		if(datasource.length == 0) {
			$div_params.html('');
		} else {
			genericAjaxGet($div_params, 'c=profiles&a=invoke&module=workspace_widget&action=getWidgetDatasourceConfig&widget_id={$widget->id}&ext_id=' + datasource);
		}
	});
});
</script>
