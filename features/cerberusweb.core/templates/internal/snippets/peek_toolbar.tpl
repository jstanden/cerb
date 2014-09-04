	{if !empty($token_labels)}<button type="button" class="cerb-popupmenu-trigger">Insert placeholder &#x25be;</button>{/if}
	<button type="button" onclick="genericAjaxPost('formSnippetsPeek','peekTemplateTest','c=internal&a=snippetTest&snippet_context={$context}{if !empty($context_id)}&snippet_context_id={$context_id}{/if}&snippet_field=content');">Test</button>
	<button type="button" onclick="genericAjaxPopup('help', 'c=internal&a=showSnippetHelpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');">Help</button>

	<div id="peekTemplateTest"></div>
	
	{if !empty($token_labels)}
	<ul class="cerb-popupmenu" style="border:0;">
		<li style="background:none;">
			<input type="text" size="16" class="input_search filter">
		</li>
		{foreach from=$token_labels key=token item=label}
		<li><a href="javascript:;" token="{$token}">{$label}</a></li>
		{/foreach}
	</ul>
	{/if}

{if !empty($token_labels)}
<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#peekTemplateTest');
	
	// Quick insert token menu
	
	var $menu_trigger = $popup.find('button.cerb-popupmenu-trigger');
	var $menu = $menu_trigger.siblings('ul.cerb-popupmenu');
	
	$menu_trigger.data('menu', $menu);
	
	$menu_trigger
		.click(
			function(e) {
				var $menu = $(this).data('menu');
	
				if($menu.is(':visible')) {
					$menu.hide();
					return;
				}
				
				$menu
					.show()
					.find('> li input:text')
					.focus()
					.select()
					;
			}
		)
	;
	
	$menu.find('> li > input.filter').keyup(
		function(e) {
			var term = $(this).val().toLowerCase();
			var $menu = $(this).closest('ul.cerb-popupmenu');
			$menu.find('> li a').each(function(e) {
				if(-1 != $(this).html().toLowerCase().indexOf(term)) {
					$(this).parent().show();
				} else {
					$(this).parent().hide();
				}
			});
		}
	);
	
	$menu.find('> li').click(function(e) {
		e.stopPropagation();
		if(!$(e.target).is('li'))
			return;
	
		$(this).find('a').trigger('click');
	});
	
	$menu.find('> li > a').click(function() {
		var token = $(this).attr('token');
		var $content = $popup.find('textarea[name=content]');
		if(token.match(/^\(\(__(.*?)__\)\)$/)) {
			$content.insertAtCursor(token);
		} else {
			{literal}$content.insertAtCursor('{{'+token+'}}');{/literal}
		}
		$content.focus();
	});
});
</script>
{/if}