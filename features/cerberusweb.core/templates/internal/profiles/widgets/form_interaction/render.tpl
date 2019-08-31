<div id="widget{$widget->id}">
	<form action="{devblocks_url}{/devblocks_url}" method="POST" class="cerb-form-builder" onsubmit="return false;">
	{$widget_ext->renderForm($widget, $dict, $is_refresh)}
	</form>
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#widget{$widget->id}');
	var $form = $widget.find('> form');
	
	$form.on('submit', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		$form.triggerHandler('cerb-form-builder-submit');
		return false;
	});
	
	$form.on('cerb-form-builder-submit', function(e) {
		e.stopPropagation();
		
		var $this = $(this);
		var $tab = $this.closest('.cerb-profile-layout');
		
		// Grab the entire form params
		var form_elements = $form.serializeArray();

		var evt = $.Event('cerb-widget-refresh');
		evt.widget_id = {$widget->id};
		evt.refresh_options = form_elements;
		
		$tab.triggerHandler(evt);
	});
	
	$form.on('cerb-form-builder-reset', function(e) {
		e.stopPropagation();
		
		var $this = $(this);
		var $tab = $this.closest('.cerb-profile-layout');
		
		var evt = $.Event('cerb-widget-refresh');
		evt.widget_id = {$widget->id};
		evt.refresh_options = {
			'reset': 1
		};
		
		$tab.triggerHandler(evt);
	});
	
	$form.find('input[type=text],textarea').first().focus();
});
</script>
