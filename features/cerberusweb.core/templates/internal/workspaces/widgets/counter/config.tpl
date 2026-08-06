<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Data source</div>
		</div>

		<div class="cerb-ui-form">
			{$source = $widget->params.datasource}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Data</label>
				<select name="params[datasource]" class="datasource-selector">
					<option value=""></option>
					{foreach from=$datasource_mfts item=datasource_mft}
					<option value="{$datasource_mft->id}" {if $source==$datasource_mft->id}selected="selected"{/if}>{$datasource_mft->name}</option>
					{/foreach}
				</select>

				<div class="datasource-params">
					{$datasource = Extension_WorkspaceWidgetDatasource::get($source)}
					{if !empty($datasource) && method_exists($datasource, 'renderConfig')}
						{$datasource->renderConfig($widget, $widget->params)}
					{/if}
				</div>
			</div>

			<div class="cerb-ui-form--row">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Display as</label>
					{$types = [['number','number'], ['decimal','decimal'], ['percent','percentage'], ['bytes','bytes'], ['seconds','secs elapsed'], ['minutes','mins elapsed']]}
					<select name="params[metric_type]">
						{foreach from=$types item=type}
						<option value="{$type[0]}" {if $widget->params.metric_type==$type[0]}selected="selected"{/if}>{$type[1]}</option>
						{/foreach}
					</select>
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Prepend</label>
					<input type="text" name="params[metric_prefix]" value="{$widget->params.metric_prefix}" size="10">
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Append</label>
					<input type="text" name="params[metric_suffix]" value="{$widget->params.metric_suffix}" size="10">
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Color</label>
					<input type="text" name="params[color]" value="{$widget->params.color|default:'#34434E'}" class="color-picker">
				</div>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');

	$config.find('input:text.color-picker').each(function() {
		new CerbUI.ColorPicker(this, {
			palette: ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#D5D5D5','#ADADAD','#34434E']
		});
	});

	$config.find('select.datasource-selector').change(function() {
		var $this = $(this);
		var datasource = $this.val();
		var $div_params = $this.next('DIV.datasource-params');

		if(datasource.length==0) {
			$div_params.html('');
		} else {
			genericAjaxGet($div_params, 'c=profiles&a=invoke&module=workspace_widget&action=getWidgetDatasourceConfig&widget_id={$widget->id}&ext_id=' + datasource);
		}
	});
});
</script>