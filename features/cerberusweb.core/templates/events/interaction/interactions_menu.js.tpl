var $interactions_parent = $interaction_container;
var $interactions_menu = $interactions_parent.find('ul.cerb-bot-interactions-menu').menu();

$interactions_parent.find('button.cerb-bot-interactions-button').on('click', function(e) {
	$interactions_menu.toggle();
});

$interactions_parent.find('.cerb-bot-trigger')
	.cerbBotTrigger()
	.on('click', function(e) {
		e.stopPropagation();
		$interactions_menu.hide();
	})
;
