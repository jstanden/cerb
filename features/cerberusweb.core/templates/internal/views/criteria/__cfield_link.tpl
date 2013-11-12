<input type="hidden" name="oper" value="=">

{$random = uniqid()}
<div id="container_{$random}">

<button type="button" class="chooser"><span class="cerb-sprite sprite-view"></span></button>

<ul class="chooser-container bubbles" style="display:block;">
{if $field->params.context && $param->value}
	<li>
		{$context_ext = Extension_DevblocksContext::get($field->params.context, true)}
		{$meta = $context_ext->getMeta($param->value)}
		<b>{$meta.name}</b> ({$context_ext->manifest->name})<!--
		--><input type="hidden" name="context_id" value="{$param->value}"><!--
		--><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;" onclick="$(this).closest('li').remove();"></span>
	</li>
{/if}
</ul>

</div>

<script type="text/javascript">
$("#container_{$random}").find('button.chooser').click(function(e) {
	$popup = genericAjaxPopup("chooser{$random}",'c=internal&a=chooserOpen&context={$field->params.context}&single=1',null,true,'750');
	$popup.one('popup_close',function(event) {
		event.stopPropagation();
	});
	
	$popup.one('chooser_save',function(event) {
		event.stopPropagation();
		
		$container = $("#container_{$random}");
		$ul = $container.find('ul.chooser-container');
		
		for(i in event.labels) {
			// One link at a time
			$ul.find('li').remove();
			
			$li = $('<li><b>' + event.labels[i] + '</b></li>');
			$li.append($('<input type="hidden" name="context_id" value="' + event.values[i] + '">'));
			$li.append($('<span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;" onclick="$(this).closest(\'li\').remove();"></span>'));
				
			$ul.append($li);
		}
		
	});
	
});
</script>