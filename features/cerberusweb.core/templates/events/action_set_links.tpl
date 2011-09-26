{$random = time()|cat:'_'|cat:mt_rand(1000,9999)}
<div id="container_{$random}">

<select class="chooser">
	<option value="">-- {'common.choose'|devblocks_translate|lower} --</option>
	{foreach from=$contexts item=context key=context_id}
		{if isset($context->params['options'][0]['find'])}
		<option value="{$context_id}">{$context->name}</option>
		{/if}
	{/foreach}
</select>

<ul class="chooser-container bubbles" style="display:block;">
{foreach from=$params.context_objects item=context_data}
	{$context_pair = explode(':',$context_data)}
	{if is_array($context_pair) && 2 == count($context_pair)}
	<li>
		{$context = $context_pair.0}
		{$context_id = $context_pair.1}
		{$context_ext = Extension_DevblocksContext::get($context,true)}
		{$meta = $context_ext->getMeta($context_id)}
		{$meta.name} ({$context_ext->manifest->name})<!--
		--><input type="hidden" name="{$namePrefix}[context_objects][]" value="{$context}:{$context_id}"><!--
		--><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;" onclick="$(this).closest('li').remove();"></span>
	</li>
	{/if}
{/foreach}
</ul>

</div>

<script type="text/javascript">
$('#container_{$random}').find('select.chooser').change(function(e) {
	$this = $(this);
	$val = $this.val();
	
	if($val.length > 0) {
		$popup = genericAjaxPopup('chooser','c=internal&a=chooserOpen&context='+encodeURIComponent($val),null,true,'750');
		$popup.one('popup_close',function(event) {
			event.stopPropagation();
			$container = $('#container_{$random}');
			$chooser = $container.find('select.chooser');
			$chooser.val('');
		});
		$popup.one('chooser_save',function(event) {
			event.stopPropagation();
			
			$container = $('#container_{$random}');
			$chooser = $container.find('select.chooser');
			$ul = $container.find('ul.chooser-container');
			$context_name = $chooser.find(':selected').text();
			$context = $chooser.val();
			
			for(i in event.labels) {
				// Look for dupes
				if(0 == $ul.find('input:hidden[value="' + $context + ':' + event.values[i] + '"]').length) {
					$li = $('<li>' + event.labels[i] + ' (' + $context_name + ')</li>');
					$li.append($('<input type="hidden" name="{$namePrefix}[context_objects][]" value="' + $context + ':' + event.values[i] + '">'));
					$li.append($('<span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;" onclick="$(this).closest(\'li\').remove();"></span>'));
					
					$ul.append($li);
				}
			}
			
			$chooser.val('');
		});
	}
});
</script>