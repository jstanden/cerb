{$uniqid = uniqid()}
{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}

<div style="display:inline-block;vertical-align:middle;">
	<div id="{$uniqid}" class="bubble cerb-filter-editor" style="padding:5px;display:block;">
		<div>
			<b>{$prompt.label}</b>

			{if $prompt.params.presets}
				{foreach from=$prompt.params.presets item=preset_query key=preset_label name=presets}
				<a data-preset="{$preset_query}">{$preset_label}</a>
				{if !$smarty.foreach.presets.last} | {/if}
				{/foreach}
			{else}
				<a data-preset="today to now">1d</a>
				|
				<a data-preset="today -1 week">1wk</a>
				|
				<a data-preset="first day of this month -1 month">1mo</a>
				|
				<a data-preset="first day of this month -6 months">6mo</a>
				|
				<a data-preset="first day of this month -1 year">1yr</a>
				|
				<a data-preset="Jan 1 to now">ytd</a>
				|
				<a data-preset="big bang to now">all</a>
			{/if}
		</div>
		<div>
			<input type="text" name="prompts[{$prompt.placeholder}]" value="{$prompt_value}" size="32" style="width:95%;">
		</div>
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