{if $macros}
$menu = $('#menuDisplayMacros');
$menu.appendTo('body');
$menu.find('> li')
	.click(function(e) {
		e.stopPropagation();
		if(!$(e.target).is('li'))
			return;

		$link = $(this).find('a:first');
		
		if($link.length > 0)
			window.location.href = $link.attr('href');
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
		$menu.find('> li a').each(function(e) {
			if(-1 != $(this).html().toLowerCase().indexOf(term)) {
				$(this).parent().show();
			} else {
				$(this).parent().hide();
			}
		});
	})
	;

$('#btnDisplayMacros')
	.click(function(e) {
		$menu = $('#menuDisplayMacros');

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
			$('#menuDisplayMacros')
				.hide()
			;
		}
	)
	;
{/if}