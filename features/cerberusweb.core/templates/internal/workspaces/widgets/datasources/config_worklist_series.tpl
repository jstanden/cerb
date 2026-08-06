{$series_ctx_id = $params.worklist_model.context}

{$series_ctx = null}
{$series_ctx_view = null}
{$series_ctx_fields = []}

{if !empty($series_ctx_id)}
	{$series_ctx = Extension_DevblocksContext::get($series_ctx_id)}
	{$series_ctx_view = $series_ctx->getChooserView()}
	{$series_ctx_fields = $series_ctx_view->getParamsAvailable()}
{/if}

{$div_id = uniqid()}
{$div_popup_worklist = uniqid()}

{$xaxis_field = $params.xaxis_field}
{$xaxis_tick = $params.xaxis_tick}
{$xaxis_field_type = $series_ctx_fields.{$xaxis_field}->type}
{$xaxis_ticks = [hour,day,week,month,year]}

{$yaxis_func = $params.yaxis_func}
{$yaxis_field = $params.yaxis_field}

<div class="cerb-ui-form" id="ds{$div_id}">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Data from</label>
		<select name="params{$params_prefix}[context]" class="context">
			<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
			{foreach from=$context_mfts item=context_mft key=context_id}
			<option value="{$context_id}" data-cerb-ui-icon="{$context_mft->params.icon|default:'collection'}" {if $series_ctx_id==$context_id}selected="selected"{/if}>{$context_mft->name}</option>
			{/foreach}
		</select>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Worklist</label>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-flex-wrap">
			<button type="button" id="popup{$div_popup_worklist}" class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-list"></span> Worklist <span class="cerb-icons cerb-icon-chevron-down"></span></button>
			<input type="hidden" name="params{$params_prefix}[worklist_model_json]" value="{$params.worklist_model|json_encode}" class="model">
		</div>
		<span class="cerb-ui-form--help">Choose a worklist to filter which records are plotted.</span>
	</div>

	<div class="cerb-ui-form--field">
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" name="params{$params_prefix}[search_mode]" value="quick_search" class="mode" id="mode{$div_id}" {if $params.search_mode == "quick_search"}checked="checked"{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="mode{$div_id}">Filter using quick search</label>
		</div>
		<input type="text" name="params{$params_prefix}[quick_search]" value="{$params.quick_search}" class="quicksearch cerb-u-mt-1" autocomplete="off" spellcheck="false">
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">X-axis</label>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
			<select name="params{$params_prefix}[xaxis_field]" class="xaxis_field">
				<option value="_id" {if $xaxis_field=='_id'}selected="selected"{/if}>(each record)</option>
				{if !empty($series_ctx_fields)}
				{foreach from=$series_ctx_fields item=field}
					{if !empty($field->db_label)}
						{if $field->type == Model_CustomField::TYPE_DATE || $field->type == Model_CustomField::TYPE_NUMBER}
						<option value="{$field->token}" class="{if $field->type == Model_CustomField::TYPE_DATE}date{elseif $field->type == Model_CustomField::TYPE_NUMBER}number{else}{/if}" {if $xaxis_field==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
						{/if}
					{/if}
				{/foreach}
				{/if}
			</select>

			<select name="params{$params_prefix}[xaxis_tick]" class="xaxis_tick" style="display:{if $xaxis_field_type=='E'}inline{else}none{/if};">
				{foreach from=$xaxis_ticks item=v}
				<option value="{$v}" {if $xaxis_tick==$v}selected="selected"{/if}>by {$v}</option>
				{/foreach}
			</select>

			<span class="cerb-u-text-muted">as</span>

			<select name="params{$params_prefix}[xaxis_format]">
				<option value="" {if empty($params.xaxis_format)}selected="selected"{/if}></option>
				<option value="number" {if 'number'==$params.xaxis_format}selected="selected"{/if}>number</option>
				<option value="decimal" {if 'decimal'==$params.xaxis_format}selected="selected"{/if}>decimal</option>
				<option value="percent" {if 'percent'==$params.xaxis_format}selected="selected"{/if}>percentage</option>
				<option value="bytes" {if 'bytes'==$params.xaxis_format}selected="selected"{/if}>bytes</option>
				<option value="seconds" {if 'seconds'==$params.xaxis_format}selected="selected"{/if}>secs elapsed</option>
				<option value="minutes" {if 'minutes'==$params.xaxis_format}selected="selected"{/if}>mins elapsed</option>
			</select>
		</div>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Y-axis</label>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
			<select name="params{$params_prefix}[yaxis_func]" class="yaxis_func">
				<option value="count" {if 'count'==$params.yaxis_func}selected="selected"{/if}>count</option>
				<option value="value" {if 'value'==$params.yaxis_func}selected="selected"{/if}>value</option>
				<option value="avg" class="number" {if 'avg'==$params.yaxis_func}selected="selected"{/if}>average</option>
				<option value="sum" class="number" {if 'sum'==$params.yaxis_func}selected="selected"{/if}>sum</option>
				<option value="min" class="number" {if 'min'==$params.yaxis_func}selected="selected"{/if}>min</option>
				<option value="max" class="number" {if 'max'==$params.yaxis_func}selected="selected"{/if}>max</option>
			</select>

			<select name="params{$params_prefix}[yaxis_field]" class="yaxis_field" style="display:{if empty($series_ctx_fields) || 'count'==$yaxis_func}none{else}inline{/if};">
				{if !empty($series_ctx_fields)}
				{foreach from=$series_ctx_fields item=field}
					{if !empty($field->db_label)}
						{if $field->type == Model_CustomField::TYPE_NUMBER}
						<option value="{$field->token}" {if $yaxis_field==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
						{/if}
					{/if}
				{/foreach}
				{/if}
			</select>

			<span class="cerb-u-text-muted">as</span>

			<select name="params{$params_prefix}[yaxis_format]">
				<option value="" {if empty($params.yaxis_format)}selected="selected"{/if}></option>
				<option value="number" {if 'number'==$params.yaxis_format}selected="selected"{/if}>number</option>
				<option value="decimal" {if 'decimal'==$params.yaxis_format}selected="selected"{/if}>decimal</option>
				<option value="percent" {if 'percent'==$params.yaxis_format}selected="selected"{/if}>percentage</option>
				<option value="bytes" {if 'bytes'==$params.yaxis_format}selected="selected"{/if}>bytes</option>
				<option value="seconds" {if 'seconds'==$params.yaxis_format}selected="selected"{/if}>secs elapsed</option>
				<option value="minutes" {if 'minutes'==$params.yaxis_format}selected="selected"{/if}>mins elapsed</option>
			</select>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $scope = $('#ds{$div_id}');

	if(window.CerbUI && CerbUI.SelectMenu)
		$scope.find('select.context').each(function() { new CerbUI.SelectMenu(this); });

	if(window.CerbUI && CerbUI.Toggle)
		$scope.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

	$scope.find('select.xaxis_field').change(function(e) {
		const $this = $(this);

		const $xaxis_tick = $scope.find('select.xaxis_tick');

		if($this.find('option:selected').is('.date')) {
			$xaxis_tick.show();
		} else {
			$xaxis_tick.hide();
		}
	});

	$scope.find('select.yaxis_func').change(function(e) {
		const val = $(this).val();

		const $select_yaxis = $scope.find('select.yaxis_field');

		if(val == 'count')
			$select_yaxis.hide();
		else
			$select_yaxis.show();
	});

	$scope.find('select.context').change(function(e) {
		const ctx = $(this).val();

		// [TODO] Hide options until we know the context
		const $select = $(this);

		if(0 == ctx.length)
			return;

		genericAjaxGet('','c=ui&a=getContextFieldsJson&context=' + ctx, function(json) {
			if('object' == typeof(json) && json.length > 0) {
				const $select_xaxis = $scope.find('select.xaxis_field').html('');
				const $select_yaxis = $scope.find('select.yaxis_field').html('');

				const $option = $('<option value="_id">(each record)</option>');
				$select_xaxis.append($option);

				for(let idx in json) {
					const field = json[idx];
					const field_type = (field.type=='E') ? 'date' : ((field.type=='N') ? 'number' : '');

					const $option = $('<option/>').attr('value',field.key).addClass(field_type).text(field.label);

					// X-Axis
					// Number or date
					if(field_type == 'number' || field_type == 'date')
						$select_xaxis.append($option.clone());

					// Y-Axis
					// Number
					if(field_type == 'number')
						$select_yaxis.append($option.clone());
				}
			}
		});
	});

	$('#popup{$div_popup_worklist}').click(function(e) {
		const $select = $scope.find('select.context');
		const context = $select.val();
		const $mode = $scope.find('input.mode');
		let q = '';

		if(context.length == 0) {
			if(window.CerbUI && CerbUI.effects) CerbUI.effects.flash($select);
			return;
		}

		if($mode.is(':checked')) {
			q = $scope.find('input.quicksearch').val();
		}

		const $chooser = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=invoke&module=records&action=chooserOpenParams&context='+context+'&view_id={"widget{$widget->id}_worklist{$series_idx}"}&q=' + encodeURIComponent(q),null,true,'750');

		$chooser.bind('chooser_save',function(event) {
			if(null != event.worklist_model) {
				$scope.find('input:hidden.model').val(event.worklist_model);
				$scope.find('input:text.quicksearch').val(event.worklist_quicksearch);
			}
		});
	});
});
</script>
