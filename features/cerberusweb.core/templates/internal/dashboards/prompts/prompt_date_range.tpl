{$uniqid = uniqid()}
{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}

<div id="{$uniqid}" class="cerb-ui-form--field cerb-filter-editor" style="flex:2 1 20em;">
	<label class="cerb-ui-form--label">{$prompt.label}</label>
	<input type="text" name="prompts[{$prompt.placeholder}]" value="{$prompt_value}">
	<div class="cerb-filter-editor--presets" style="display:flex;flex-wrap:wrap;gap:0.35em;cursor:pointer;">
		{if $prompt.params.presets}
			{foreach from=$prompt.params.presets item=preset_query key=preset_label name=presets}
			<a class="cerb-ui-pill" data-preset="{$preset_query}">{$preset_label}</a>
			{/foreach}
		{else}
			<a class="cerb-ui-pill" data-preset="today to now">1d</a>
			<a class="cerb-ui-pill" data-preset="today -1 week">1wk</a>
			<a class="cerb-ui-pill" data-preset="first day of this month -1 month">1mo</a>
			<a class="cerb-ui-pill" data-preset="first day of this month -6 months">6mo</a>
			<a class="cerb-ui-pill" data-preset="first day of this month -1 year">1yr</a>
			<a class="cerb-ui-pill" data-preset="Jan 1 to now">ytd</a>
			<a class="cerb-ui-pill" data-preset="big bang to now">all</a>
		{/if}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $filter = $('#{$uniqid}');

	$filter.find('[data-preset]')
		.on('click', function(e) {
			var $this = $(this);
			var preset = $this.attr('data-preset');
			var $editor = $this.closest('.cerb-filter-editor');
			$editor.find('input:text').val(preset).focus();
		})
	;

	$filter.find('input:text')
		.on('keydown.dashboard-filters', null, 'return', function(e) {
			$(this)
				.closest('form')
				.find('.cerb-filter-editor--save')
				.click()
			;
		})
	;
});
</script>
