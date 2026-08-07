{$uniqid = uniqid()}
{$is_blank = empty($params.worklist_model.context)}

{if !empty($params.is_available)}{$is_available = 1}{else}{$is_available = 0}{/if}

<div id="div{$uniqid}" class="cerb-ui-form datasource-params">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Data from</label>
		<select class="context">
			<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
			{foreach from=$context_mfts item=context_mft}
			<option value="{$context_mft->id}" data-cerb-ui-icon="{$context_mft->params.icon|default:'collection'}" {if $params.worklist_model.context==$context_mft->id}selected="selected"{/if}>{$context_mft->name}</option>
			{/foreach}
		</select>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Worklist</label>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-flex-wrap">
			<button type="button" id="popup{$uniqid}" class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-list"></span> Worklist <span class="cerb-icons cerb-icon-chevron-down"></span></button>
			<input type="hidden" name="params{$params_prefix}[worklist_model_json]" value="{$params.worklist_model|json_encode}" class="model">
		</div>
		<span class="cerb-ui-form--help">Choose a worklist to select which records become events.</span>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Label</label>
		<input type="text" name="params{$params_prefix}[label]" value="{$params.label|default:'{{_label}}'}" class="placeholders-input">
	</div>

	<div style="display:none;" class="placeholders-toolbar">
		<select class="placeholders">
			<option value="">- insert at cursor -</option>
			{if !empty($placeholders)}
			{foreach from=$placeholders key=k item=label}
			<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$label}</option>
			{/foreach}
			{/if}
		</select>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Start date</label>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
			<select name="params{$params_prefix}[field_start_date]" class="field_start_date">
				{if !empty($ctx_fields)}
				{foreach from=$ctx_fields item=field}
					{if !empty($field->db_label)}
						{if $field->type == Model_CustomField::TYPE_DATE}
						<option value="{$field->token}" class="{if $field->type == Model_CustomField::TYPE_DATE}date{else}{/if}" {if $params.field_start_date==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
						{/if}
					{/if}
				{/foreach}
				{/if}
			</select>
			<input type="text" name="params{$params_prefix}[field_start_date_offset]" value="{$params.field_start_date_offset|default:''}" placeholder="e.g. +2 hours" style="width:12em;">
		</div>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">End date</label>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
			<select name="params{$params_prefix}[field_end_date]" class="field_end_date">
				{if !empty($ctx_fields)}
				{foreach from=$ctx_fields item=field}
					{if !empty($field->db_label)}
						{if $field->type == Model_CustomField::TYPE_DATE}
						<option value="{$field->token}" class="{if $field->type == Model_CustomField::TYPE_DATE}date{else}{/if}" {if $params.field_end_date==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
						{/if}
					{/if}
				{/foreach}
				{/if}
			</select>
			<input type="text" name="params{$params_prefix}[field_end_date_offset]" value="{$params.field_end_date_offset|default:''}" placeholder="e.g. +2 hours" style="width:12em;">
		</div>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Status</label>
		<div>
			<input type="hidden" name="params{$params_prefix}[is_available]" id="isavail{$uniqid}" value="{$is_available}">
			<div class="cerb-ui-switcher" data-cerb-input="isavail{$uniqid}">
				<button type="button" data-value="1"{if $is_available==1} class="cerb-ui-switcher--active"{/if}>Available</button>
				<button type="button" data-value="0"{if $is_available==0} class="cerb-ui-switcher--active"{/if}>Busy</button>
			</div>
		</div>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Color</label>
		<div><input type="text" name="params{$params_prefix}[color]" value="{$params.color|default:'#A0D95B'}" class="color-picker"></div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
const $div = $('#div{$uniqid}');

if(window.CerbUI && CerbUI.SelectMenu)
	$div.find('select.context').each(function() { new CerbUI.SelectMenu(this); });

if(window.CerbUI && CerbUI.Switcher) {
	$div.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
		const el = this;
		const $input = $div.find('#' + $(el).attr('data-cerb-input'));
		new CerbUI.Switcher(el, { value: $input.val(), onSelect: function(value) { $input.val(value); } });
	});
}

$div.find('input:text.color-picker').each(function() {
	new CerbUI.ColorPicker(this, {
		palette: ['#A0D95B','#FEAF03','#FCB3B3','#FF6666','#C5DCFA','#85BAFF','#E8F554','#F4A3FE','#C8C8C8']
	});
});

$div.find('select.context').change(function(e) {
	const ctx = $(this).val();

	if(0 === ctx.length)
		return;

	genericAjaxGet('','c=ui&a=getContextFieldsJson&context=' + ctx, function(json) {
		if('object' == typeof(json) && json.length > 0) {
			const $select_field_start_date = $div.find('select.field_start_date').html('');
			const $select_field_end_date = $div.find('select.field_end_date').html('');

			for(let idx in json) {
				const field = json[idx];
				const field_type = (field.type==='E') ? 'date' : ((field.type==='N') ? 'number' : '');

				const $option = $('<option/>')
					.attr('value', field.key)
					.addClass(field_type)
					.text(field.label)
				;

				// Field: Start Date
				if(field_type === 'date') {
					$select_field_start_date.append($option.clone());
					$select_field_end_date.append($option.clone());
				}
			}
		}
	});

	genericAjaxGet('','c=ui&a=getContextPlaceholdersJson&context=' + ctx, function(json) {
		if('object' == typeof(json) && json.length > 0) {
			const $placeholders = $div.find('select.placeholders');

			$placeholders.html('');

			$('<option/>')
				.val('')
				.text('- insert at cursor -')
				.appendTo($placeholders)
			;

			for(let i in json) {
				const field = json[i];

				if(field.label.length === 0 || field.key.length === 0)
					continue;

				$('<option/>')
					.val("{literal}{{{/literal}" + field.key + "{literal}}}{/literal}")
					.text(field.label)
					.appendTo($placeholders)
				;
			}

			$placeholders.val('');
		}
	});
});

$div.find('input.placeholders-input')
	.focus(function() {
		const $this = $(this);
		const $params = $(this).closest('div.datasource-params');

		$params.find('div.placeholders-toolbar')
			.insertAfter($this)
			.css('margin-left', ($this.position().left-30) + 'px')
			.fadeIn();
	})
	;

$div.find('select.placeholders').change(function() {
	const $select = $(this);
	const $input = $select.parent().prev('input:text');
	const txt = $select.val();
	$input.insertAtCursor(txt);
	$select.val('');
});

$('#popup{$uniqid}').click(function() {
	const $select = $div.find('select.context');
	const context = $select.val();

	if(context.length === 0) {
		if(window.CerbUI && CerbUI.effects) CerbUI.effects.flash($select);
		return;
	}

	let $chooser = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=invoke&module=records&action=chooserOpenParams&context='+context+'&view_id={"calendar{$calendar->id}_worklist{$series_idx}"}',null,true,'750');
	$chooser.bind('chooser_save',function(event) {
		if(null != event.worklist_model) {
			$div.find('input:hidden.model').val(event.worklist_model);
		}
	});
});
</script>