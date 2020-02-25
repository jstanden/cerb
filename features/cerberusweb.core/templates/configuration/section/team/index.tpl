<h2>{'common.team'|devblocks_translate|capitalize}</h2>

<div id="tabsSetupTeam">
	<ul>
		{$tabs = ['roles', 'groups', 'workers']}
		<li data-alias="roles"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=team&action=renderTabRoles{/devblocks_url}">{'common.roles'|devblocks_translate|capitalize}</a></li>
		<li data-alias="groups"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=team&action=renderTabGroups{/devblocks_url}">{'common.groups'|devblocks_translate|capitalize}</a></li>
		<li data-alias="workers"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=team&action=renderTabWorkers{/devblocks_url}">{'common.workers'|devblocks_translate|capitalize}</a></li>
	</ul>
</div>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('tabsSetupTeam', '{$tab}');
	
	var $tabs = $('#tabsSetupTeam').tabs(tabOptions);
	
	$tabs.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$tabs.find('.chooser-abstract')
		.cerbChooserTrigger()
		;
	
	$tabs.find('.cerb-code-editor')
		.cerbCodeEditor()
		;
	
	$tabs.find('.cerb-template-trigger')
		.cerbTemplateTrigger()
		;
	
	$tabs.find('BUTTON.submit')
		.click(function(e) {
			var $button = $(this);
			var $button_form = $button.closest('form');
			var $status = $button_form.find('div.status');
			
			genericAjaxPost($button_form,'',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError($status, $o.error);
				} else {
					Devblocks.showSuccess($status,'Saved!');
				}
			});
		})
	;
});
</script>