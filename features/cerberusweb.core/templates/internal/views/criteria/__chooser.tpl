<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in" {if $param && $param->operator=='in'}selected="selected"{/if}>{$translate->_('search.oper.in_list')}</option>
		<option value="not in" {if $param && $param->operator=='not in'}selected="selected"{/if}>{$translate->_('search.oper.in_list.not')}</option>
	</select>
</blockquote>

<b>{$translate->_('search.value')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	{$random = uniqid()}
	<div id="container_{$random}" style="margin-bottom:5px;">
	
	<button type="button" class="chooser"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
	{foreach from=$param->value item=context_id}
		<li>
			{$context_ext = Extension_DevblocksContext::get($context,true)}
			{$meta = $context_ext->getMeta($context_id)}
			<b>{$meta.name}</b><!--
			--><input type="hidden" name="context_id[]" value="{$context_id}"><!--
			--><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;" onclick="$(this).closest('li').remove();"></span>
		</li>
	{/foreach}
	</ul>
	</div>
</blockquote>

<script type="text/javascript">
$("#container_{$random}").find('button.chooser').click(function(e) {
	$this = $(this);
	
	$popup = genericAjaxPopup("chooser{$random}",'c=internal&a=chooserOpen&context={$context}',null,true,'750');
	$popup.one('popup_close',function(event) {
		event.stopPropagation();
		$container = $('#container_{$random}');
	});
	$popup.one('chooser_save',function(event) {
		event.stopPropagation();
		
		$container = $("#container_{$random}");
		$chooser = $container.find('button.chooser');
		$ul = $container.find('ul.chooser-container');
		
		for(i in event.labels) {
			// Look for dupes
			if(0 == $ul.find('input:hidden[value="' + event.values[i] + '"]').length) {
				$li = $('<li><b>' + event.labels[i] + '</b></li>');
				$li.append($('<input type="hidden" name="context_id[]" value="' + event.values[i] + '">'));
				$li.append($('<span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;" onclick="$(this).closest(\'li\').remove();"></span>'));
				
				$ul.append($li);
			}
		}
	});
});
</script>