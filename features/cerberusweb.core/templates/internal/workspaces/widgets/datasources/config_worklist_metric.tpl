{$div_popup_worklist = uniqid()}
{$div_id = uniqid()}

{$ctx_id = $widget->params.worklist_model.context}

{$ctx = null}
{$ctx_view = null}
{$ctx_fields = []}

{if !empty($ctx_id)}
	{$ctx = Extension_DevblocksContext::get($ctx_id)}
	{if is_a($ctx, 'Extension_DevblocksContext')}
		{$ctx_view = $ctx->getChooserView()}
		{$ctx_fields = $ctx_view->getParamsAvailable()}
	{/if}
{/if}

<div class="cerb-ui-form" id="ds{$div_id}">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Data from</label>
		<select name="params[context]" class="context">
			{foreach from=$context_mfts item=context_mft key=context_id}
			<option value="{$context_id}" data-cerb-ui-icon="{$context_mft->params.icon|default:'collection'}" {if $ctx_id==$context_id}selected="selected"{/if}>{$context_mft->name}</option>
			{/foreach}
		</select>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Worklist</label>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-flex-wrap">
			<button type="button" id="popup{$div_popup_worklist}" class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-list"></span> Worklist <span class="cerb-icons cerb-icon-chevron-down"></span></button>
			<input type="hidden" name="params[worklist_model_json]" value="{$widget->params.worklist_model|json_encode}" class="model">
		</div>
		<span class="cerb-ui-form--help">Choose a worklist to filter which records are measured.</span>
	</div>

	<div class="cerb-ui-form--field">
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" name="params[search_mode]" value="quick_search" class="mode" id="mode{$div_id}" {if $params.search_mode == "quick_search"}checked="checked"{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="mode{$div_id}">Filter using quick search</label>
		</div>
		<input type="text" name="params[quick_search]" value="{$params.quick_search}" class="quicksearch cerb-u-mt-1" autocomplete="off" spellcheck="false">
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Value</label>
		{$metric_func = $widget->params.metric_func}
		{$metric_field = $widget->params.metric_field}
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
			<span class="cerb-u-text-muted">is the</span>
			<select name="params[metric_func]" class="metric_func">
				<option value="count" {if 'count'==$widget->params.metric_func}selected="selected"{/if}>count</option>
				<option value="avg" class="number" {if 'avg'==$widget->params.metric_func}selected="selected"{/if}>average</option>
				<option value="sum" class="number" {if 'sum'==$widget->params.metric_func}selected="selected"{/if}>sum</option>
				<option value="min" class="number" {if 'min'==$widget->params.metric_func}selected="selected"{/if}>min</option>
				<option value="max" class="number" {if 'max'==$widget->params.metric_func}selected="selected"{/if}>max</option>
			</select>
			<select name="params[metric_field]" class="metric_field" style="display:{if empty($ctx_fields) || 'count'==$metric_func}none{else}inline{/if};">
				{if !empty($ctx_fields)}
				{foreach from=$ctx_fields item=field}
					{if !empty($field->db_label)}
						{if $field->type == Model_CustomField::TYPE_NUMBER}
						<option value="{$field->token}" {if $metric_field==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
						{/if}
					{/if}
				{/foreach}
				{/if}
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

	$scope.find('select.context').change(function(e) {
		const ctx = $(this).val();

		// Hide options until we know the context
		const $select = $(this);

		if(0 == ctx.length)
			return;

		genericAjaxGet('','c=ui&a=getContextFieldsJson&context=' + ctx, function(json) {
			if('object' == typeof(json) && json.length > 0) {
				const $select_metric_field = $scope.find('select.metric_field').html('');

				for(let idx in json) {
					const field = json[idx];
					const field_type = (field.type=='E') ? 'date' : ((field.type=='N') ? 'number' : '');

					const $option = $('<option/>').attr('value',field.key).addClass(field_type).text(field.label);

					// Number
					if(field_type == 'number')
						$select_metric_field.append($option.clone());
				}
			}
		});
	});

	$scope.find('select.metric_func').change(function(e) {
		const val = $(this).val();

		const $select_metric_field = $scope.find('select.metric_field');

		if(val == 'count')
			$select_metric_field.hide();
		else
			$select_metric_field.show();
	});

	$('#popup{$div_popup_worklist}').click(function(e) {
		const context = $scope.find('select.context').val();
		const $mode = $scope.find('input.mode');
		let q = '';

		if($mode.is(':checked')) {
			q = $scope.find('input.quicksearch').val();
		}
		const $chooser = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=invoke&module=records&action=chooserOpenParams&context='+context+'&view_id={"widget{$widget->id}_worklist"}&q=' + encodeURIComponent(q),null,true,'750');

		$chooser.bind('chooser_save',function(event) {
			if(null != event.worklist_model) {
				$scope.find('input:hidden.model').val(event.worklist_model);
				$scope.find('input:text.quicksearch').val(event.worklist_quicksearch);
			}
		});
	});
});
</script>
