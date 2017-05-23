<fieldset class="peek" style="margin-top:10px;" id="widget{$widget->id}Config">
	<legend>Custom HTML</legend>
	
	<div>
		<textarea name="params[content]" style="width:100%;height:150px;">{$widget->params.content}</textarea>
	</div>
	<div>
		<button type="button" class="cerb-popupmenu-trigger">Insert placeholder &#x25be;</button>
		
		<button type="button" onclick="genericAjaxPopup('help', 'c=internal&a=showSnippetHelpPopup', { my:'left top', at:'left+10 top+10'}, false, '49%');">Help</button>
	</div>
	
	<ul class="menu" style="display:none;width:250px;z-index:5;">
		{foreach from=$labels item=label key=k}
		<li data-value="{literal}{{{/literal}{$k}{literal}}}{/literal}"><b>{$label.label}</b></li>
		{/foreach}
	</ul>
</fieldset>

<script type="text/javascript">
$(function() {
	var $widget = $('#widget{$widget->id}Config');
	var $textarea = $widget.find('textarea');
	var $menu = $widget.find('ul.menu');
	
	// Placeholders
	
	$widget.find('button.cerb-popupmenu-trigger').click(function() {
		$menu.toggle();
	});
	
	$menu
		.menu({
			select: function(event, ui) {
				var val = ui.item.attr('data-value');
				
				var $field = $widget.find('pre.ace_editor');
				
				if($field.is(':text, textarea')) {
					$field.focus().insertAtCursor(val);
					
				} else if($field.is('.ace_editor')) {
					var evt = new jQuery.Event('cerb.insertAtCursor');
					evt.content = val;
					$field.trigger(evt);
				}
			}
		})
		.hide()
		;
	
	// Syntax editor autocompletion
	
	$textarea
		.cerbCodeEditor()
		;
});
</script>