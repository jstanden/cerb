<div style="float:right;">
{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null}
</div>

<div style="clear:both;"></div>

<div class="cerb-popup-scrollable">
{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}
</div>

<form action="#" method="POST" id="chooser{$view->id}">
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#chooser{$view->id}');
	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$(this).dialog('option','title','{$context->manifest->name|escape:'javascript' nofilter} Chooser');
		
		// Max height
		var max_height = Math.round($(window).height() * 0.80);
		$popup.find('.cerb-popup-scrollable').css('max-height', max_height + 'px').css('overflow','auto');

		// Quick search
		
		$popup.find('input:text:first').focus().select();
		
		// Progressive de-enhancement
		
		var on_refresh = function() {
			$worklist = $('#view{$view->id}').find('TABLE.worklist');
			$worklist.css('background','none');
			$worklist.css('background-color','rgb(100,100,100)');
			
			$header = $worklist.find('> tbody > tr:first > td:first > span.title');
			$header.css('font-size', '14px');
			$header_links = $worklist.find('> tbody > tr:first td:nth(1)');
			$header_links.children().each(function(e) {
				if(!$(this).is('a.minimal'))
					$(this).remove();
			});
			$header_links.find('a').css('font-size','11px');

			$worklist_body = $('#view{$view->id}').find('TABLE.worklistBody');
			
			$worklist_body.find('TBODY')
				.unbind('click')
				.click(function(e) {
					var $preview = $(this).find('TR.preview');
					$preview.toggle();
					
					var $icon = $(this).closest('tbody').find('tr:first b.subject').prev('span.ui-icon');
					
					if($preview.is(':visible')) {
						$icon.removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
					} else {
						$icon.removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
					}
				})
				;
			
			$worklist_body.find('TBODY TR.preview')
				.hide()
				.css('max-height','')
				;
			
			$worklist_body.find('a.subject').each(function() {
				var $tr = $(this).closest('tr');
				var $td = $(this).closest('td');
				var $td_first = $tr.find('> td:first');
				
				var attr_id = $td.attr('id');
				var attr_label = $(this).text();
				var attr_context = $td.attr('context');
				var attr_context_id = $td.attr('context_id');
				var attr_has_custom_placeholders = $td.attr('has_custom_placeholders');
				
				var $btn = $('<button type="button"><span class="glyphicons glyphicons-circle-ok"></span></button>');
				$btn.attr('id', attr_id);
				$btn.attr('context', attr_context);
				$btn.attr('context_id', attr_context_id);
				$btn.attr('has_custom_placeholders', attr_has_custom_placeholders);
				
				$btn.click(function(e) {
					e.stopPropagation();
					
					var $btn = $(this);

					$btn.find('span.glyphicons')
						.css('color', 'rgb(0,180,0)')
						;
					
					$popup=genericAjaxPopupFind('#chooser{$view->id}');

					var event = jQuery.Event('snippet_select');
					event.snippet_id = attr_id
					event.context = attr_context;
					event.context_id = attr_context_id;
					event.label = attr_label;
					event.has_custom_placeholders = attr_has_custom_placeholders;
					
					$popup.trigger(event);
					
					{if $single}
					$popup.dialog('close');
					{/if}
				});
				
				$td_first.prepend($btn);
				
				var $span = $('<span class="ui-icon ui-icon-triangle-1-e" style="display:inline-block;vertical-align:middle;"></span>');
				var $b = $('<b class="subject"></b>').text($(this).text());
				$b.insertBefore($(this));
				$span.insertBefore($b);
				
				$(this).remove();
			});
			
			$actions = $('#{$view->id}_actions');
			$actions.html('');
		}
		
		on_refresh();
		
		$popup.closest('.ui-dialog')
			.css('top', '')
			.css('position', 'fixed')
			;
		
		$(this).delegate('DIV[id^=view]','view_refresh', on_refresh);
	});
	
	$popup.one('dialogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
});
</script>