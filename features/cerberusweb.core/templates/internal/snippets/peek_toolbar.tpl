{if !empty($token_labels)}
	<button type="button" class="cerb-popupmenu-trigger">Insert at cursor &#x25be;</button>
	<button type="button" onclick="genericAjaxPost('formSnippetsPeek','peekTemplateTest','c=internal&a=snippetTest&snippet_context={$context}{if !empty($context_id)}&snippet_context_id={$context_id}{/if}&snippet_field=content');">Test</button>
	<div id="peekTemplateTest"></div>
	<ul class="cerb-popupmenu" style="border:0;">
		<li style="background:none;">
			<input type="text" size="16" class="input_search filter">
		</li>
		{foreach from=$token_labels key=token item=label}
		<li><a href="javascript:;" token="{$token}">{$label}</a></li>
		{/foreach}
	</ul>
{/if}

<script type="text/javascript">
$popup = genericAjaxPopupFetch('peek');
var $menu = $popup.find('ul.cerb-popupmenu'); 

// Quick insert token menu

$menu_trigger = $popup.find('button.cerb-popupmenu-trigger');
$menu_trigger.data('menu', $menu);

$menu_trigger
	.click(
		function(e) {
			$menu = $(this).data('menu');

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
		term = $(this).val().toLowerCase();
		$menu = $(this).closest('ul.cerb-popupmenu');
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
	token = $(this).attr('token');
	$content = $popup.find('textarea[name=content]');
	{literal}$content.insertAtCursor('{{'+token+'}}');{/literal}
	$content.focus();
});
</script>