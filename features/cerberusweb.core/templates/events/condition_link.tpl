{$random = time()|cat:'_'|cat:mt_rand(1000,9999)}
<div id="container_{$random}">

<select name="{$namePrefix}[oper]">
	<option value="in" {if $params.oper=='in'}selected="selected"{/if}>to any of</option>
	<option value="all" {if $params.oper=='all'}selected="selected"{/if}>to all of</option>
	<option value="!in" {if $params.oper=='!in'}selected="selected"{/if}>to none of</option>
</select>

<select class="chooser">
	<option value="">-- {'common.choose'|devblocks_translate|lower} --</option>
	{foreach from=$contexts item=context key=context_id}
		{if $context->hasOption('links')}
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
		{$context_ext = Extension_DevblocksContext::get($context|default:'',true)}
		{if is_a($context_ext, 'Extension_DevblocksContext')}
			{$meta = $context_ext->getMeta($context_id)}
			{$meta.name} ({$context_ext->manifest->name})
		{/if}<!--
		--><input type="hidden" name="{$namePrefix}[context_objects][]" value="{$context}:{$context_id}"><!--
		--><span class="glyphicons glyphicons-circle-remove" onclick="$(this).closest('li').remove();"></span>
	</li>
	{/if}
{/foreach}
</ul>

</div>

<script type="text/javascript">
$(function() {
	$('#container_{$random}').find('select.chooser').change(function(e) {
		var $this = $(this);
		var $val = $this.val();
		
		if($val.length > 0) {
			var $popup = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=invoke&module=records&action=chooserOpen&context='+encodeURIComponent($val),null,true,'750');
			$popup.one('popup_close',function(event) {
				event.stopPropagation();
				var $container = $('#container_{$random}');
				var $chooser = $container.find('select.chooser');
				$chooser.val('');
			});
			$popup.one('chooser_save',function(event) {
				event.stopPropagation();
				
				var $container = $('#container_{$random}');
				var $chooser = $container.find('select.chooser');
				var $ul = $container.find('ul.chooser-container');
				var $context_name = $chooser.find(':selected').text();
				var $context = $chooser.val();
				
				for(i in event.labels) {
					// Look for dupes
					if(0 == $ul.find('input:hidden[value="' + $context + ':' + event.values[i] + '"]').length) {
						var $li = $('<li/>').text(event.labels[i] + ' (' + $context_name + ')');
						$li.append($('<input type="hidden" name="{$namePrefix}[context_objects][]">').attr('value',$context + ':' + event.values[i]));
						$li.append($('<span class="glyphicons glyphicons-circle-remove" onclick="$(this).closest(\'li\').remove();"></span>'));
						
						$ul.append($li);
					}
				}
				
				$chooser.val('');
			});
		}
	});
});
</script>