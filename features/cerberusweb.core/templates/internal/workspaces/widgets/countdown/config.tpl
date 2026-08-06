<div id="widget{$widget->id}ConfigTabDatasource" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced" id="widget{$widget->id}Datasource">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Data source</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Count down to date</label>
				<input type="text" name="params[target_timestamp]" value="{$widget->params.target_timestamp|devblocks_date}" size="45" placeholder="e.g. &quot;Jan 19 2038&quot;, &quot;+1 week&quot;">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Color</label>
				<input type="text" name="params[color]" value="{$widget->params.color|default:'#34434E'}" class="color-picker">
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $fieldset = $('#widget{$widget->id}Datasource');

	$fieldset.find('input:text.color-picker').each(function() {
		new CerbUI.ColorPicker(this, {
			palette: ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#D5D5D5','#ADADAD','#34434E']
		});
	});
});
</script>