<div id="history">

<form action="{devblocks_url}c=history{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="history">
<input type="hidden" name="a" value="">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">

<div style="display:inline-block;vertical-align:top;">
	<div id="{$uniqid}" class="cerb-filter-created" style="padding:5px;display:block;">
		<div>
			<b>{'common.created'|devblocks_translate|capitalize}:</b>
			<a href="javascript:;" data-preset="today to now">1d</a>
			| 
			<a href="javascript:;" data-preset="today -1 week">1wk</a>
			| 
			<a href="javascript:;" data-preset="first day of this month -1 month">1mo</a>
			| 
			<a href="javascript:;" data-preset="first day of this month -6 months">6mo</a>
			| 
			<a href="javascript:;" data-preset="first day of this month -1 year">1yr</a>
			| 
			<a href="javascript:;" data-preset="Jan 1 to now">ytd</a>
			| 
			<a href="javascript:;" data-preset="big bang to now">all</a>
		</div>
		<div>
			<input type="text" name="prompts[created]" value="{$prompts.created}" placeholder="e.g. -1 year to now" size="32" style="width:95%;">
		</div>
	</div>
</div>

<div style="display:inline-block;vertical-align:top;">
	<div class="cerb-filter-status" style="padding:5px;display:block;">
		<b>{'common.status'|devblocks_translate|capitalize}:</b> 
		
		<div>
			<label>
				<input type="checkbox" name="prompts[status][]" value="o" {if in_array('o',$prompts.status)}checked="checked"{/if}> {'status.open'|devblocks_translate|capitalize}
			</label>
			<label>
				<input type="checkbox" name="prompts[status][]" value="w" {if in_array('w',$prompts.status)}checked="checked"{/if}> {'status.waiting.abbr'|devblocks_translate|capitalize}
			</label>
			<label>
				<input type="checkbox" name="prompts[status][]" value="c" {if in_array('c',$prompts.status)}checked="checked"{/if}> {'status.closed'|devblocks_translate|capitalize}
			</label>
		</div>
	</div>
</div>

<div style="display:inline-block;vertical-align:top;">
	<div class="cerb-filter-keywords" style="padding:5px;display:block;">
		<b>{'common.search'|devblocks_translate|capitalize}:</b> 
		
		<div>
			<input type="text" name="prompts[keywords]" value="{$prompts.keywords}" placeholder="e.g. receipt" size="32" style="width:95%;">
		</div>
	</div>
</div>

<div style="display:inline-block;margin-top:10px;">
	<button type="submit" class="cerb-filter-editor--save"><span class="glyphicons glyphicons-refresh"></span> {'common.update'|devblocks_translate|capitalize}</button>
</div>

</form>

{if !empty($view)}
	<div id="view{$view->id}">
	{$view->render()}
	</div>
{/if}

</div><!--#history-->

<script type="text/javascript">
$(function() {
	var $history = $('#history');
	
	// Date presets
	$history.find('.cerb-filter-created a')
		.on('click', function(e) {
			var $this = $(this);
			var $input = $history.find('input[name="prompts[created]"]');
			var preset = $this.attr('data-preset');
			$input.val(preset);
		})
		;
});
</script>