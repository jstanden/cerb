{if $macros}
{if empty($selector_button)}{$selector_button = '#btnDisplayMacros'}{/if}
{if empty($selector_menu)}{$selector_menu = '#menuDisplayMacros'}{/if}

$menu = $('{$selector_menu}');
$menu.appendTo('body');
$menu.find('> li')
	.click(function(e) {
		e.stopPropagation();
		if(!$(e.target).is('li,div'))
			return;

		$link = $(this).find('a:first').click();
	})
	;

$menu.find('> li > input.filter').keyup(
	function(e) {
		$menu = $(this).closest('ul.cerb-popupmenu');
		
		if(27 == e.keyCode) {
			$(this).val('');
			$menu.hide();
			$(this).blur();
			return;
		}
		
		term = $(this).val().toLowerCase();
		$menu.find('> li.item').each(function(e) {
			if(-1 != $(this).html().toLowerCase().indexOf(term)) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	})
	;

$('{$selector_button}')
	.click(function(e) {
		$menu = $('{$selector_menu}');

		if($menu.is(':visible')) {
			$menu.hide();
			return;
		}
		
		$menu
			.css('position','absolute')
			.css('top',$(this).offset().top+($(this).height())+'px')
			.css('left',$(this).prev('button').offset().left+'px')
			.show()
			.find('> li input:text')
			.focus()
			.select()
		;
	});

$menu
	.hover(
		function(e) {},
		function(e) {
			$('{$selector_menu}')
				.hide()
			;
		}
	)
	;
{/if}