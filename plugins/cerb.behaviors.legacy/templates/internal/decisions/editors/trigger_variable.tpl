{$var_type_label = $variable_types.{$var.type}}
{* The parent behavior peek makes these rows sortable (items:'.cerb-behavior-var', handle:'.cerb-ui-header')
   and reveals the remove icon via a ".cerb-behavior-var"-delegated hover. *}
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-behavior-var">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center" style="cursor:move;">
		<div class="cerb-ui-header--title-sm">{$var_type_label}</div>
		<div class="cerb-ui-header--right">
			<span data-cerb-onhover data-cerb-link="remove_parent" class="cerb-icons cerb-icon-circle-minus" style="display:none;cursor:pointer;"></span>
		</div>
	</div>
	<input type="hidden" name="var[]" value="{$seq}">
	<input type="hidden" name="var_key[]" value="{$var.key}">
	<input type="hidden" name="var_type[]" value="{$var.type}">

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.visibility'|devblocks_translate|capitalize}</label>
				<select name="var_is_private[]">
					<option value="1" {if $var.is_private}selected="selected"{/if}>private</option>
					<option value="0" {if empty($var.is_private)}selected="selected"{/if}>public</option>
				</select>
			</div>
			<div class="cerb-ui-form--field cerb-u-flex-2">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
				<input type="text" name="var_label[]" value="{$var.label}" placeholder="Variable name">
			</div>
		</div>

		{if $var.type == Model_CustomField::TYPE_SINGLE_LINE}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Widget</label>
				<input type="hidden" name="var_params{$seq}[widget]" value="{$var.params.widget|default:'single'}">
				<div>
					<div class="cerb-ui-switcher cerb-behavior-var-switcher">
						<button type="button" data-value="single"{if $var.params.widget != 'multiple'} class="cerb-ui-switcher--active"{/if}>Single line</button>
						<button type="button" data-value="multiple"{if $var.params.widget == 'multiple'} class="cerb-ui-switcher--active"{/if}>Multiple lines</button>
					</div>
				</div>
			</div>
		{elseif $var.type == Model_CustomField::TYPE_DROPDOWN}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.options'|devblocks_translate|capitalize}</label>
				<textarea name="var_params{$seq}[options]" rows="5" placeholder="Enter one option per line">{$var.params.options}</textarea>
			</div>
		{elseif $var.type == Model_CustomField::TYPE_LINK}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
				<select name="var_params{$seq}[context]">
					{foreach from=$context_mfts item=context_mft}
					<option value="{$context_mft->id}" {if $var.params.context==$context_mft->id}selected="selected"{/if}>{$context_mft->name}</option>
					{/foreach}
				</select>
			</div>
		{elseif $var.type == Model_CustomField::TYPE_NUMBER}
		{elseif $var.type == Model_CustomField::TYPE_DATE}
		{elseif $var.type == Model_CustomField::TYPE_CHECKBOX}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Default input to</label>
				<input type="hidden" name="var_params{$seq}[checkbox_default_on]" value="{$var.params.checkbox_default_on|default:0}">
				<div>
					<div class="cerb-ui-switcher cerb-behavior-var-switcher">
						<button type="button" data-value="1"{if $var.params.checkbox_default_on} class="cerb-ui-switcher--active"{/if}>{'common.yes'|devblocks_translate|capitalize}</button>
						<button type="button" data-value="0"{if empty($var.params.checkbox_default_on)} class="cerb-ui-switcher--active"{/if}>{'common.no'|devblocks_translate|capitalize}</button>
					</div>
				</div>
			</div>
		{elseif $var.type == Model_CustomField::TYPE_WORKER}
		{elseif substr($var.type,0,4)=='ctx_'}
		{/if}
	</div>
</div>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
	let $script = $('#{$script_uid}');
	let $fieldset = $script.prev('.cerb-behavior-var');

	$fieldset.find('[data-cerb-link=remove_parent]').on('click', function(e) {
		e.stopPropagation();
		$(this).closest('.cerb-behavior-var').remove();
	});

	if(window.CerbUI && CerbUI.Switcher) {
		$fieldset.find('.cerb-behavior-var-switcher').each(function() {
			const input = this.closest('.cerb-ui-form--field').querySelector('input[type=hidden]');
			new CerbUI.Switcher(this, { value: input ? input.value : null, onSelect: function(v) { if(input) input.value = v; } });
		});
	}
});
</script>
