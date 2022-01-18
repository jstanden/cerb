<div>
{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null focus=true}
</div>

<div style="clear:both;"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<form action="#" method="POST" id="chooser{$view->id}" style="{if $single}display:none;{/if}}">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	<b>Selected:</b>
	<ul class="buffer bubbles chooser-container"></ul>
	<br>
	<br>
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
</form>
<br>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('{$layer}');

	$popup.find('UL.buffer').sortable({ placeholder: 'ui-state-highlight' });

	$popup.one('popup_open',function(event,ui) {
		$popup.css('overflow', 'inherit');
		event.stopPropagation();

		$popup.dialog('option','title','{$context->manifest->name|escape:'javascript' nofilter} Chooser');

		// Progressive de-enhancement

		var on_refresh = function() {
			var $view = $('#view{$view->id}');
			var $worklist = $view.find('TABLE.worklist');
			$worklist.css('background','none');
			$worklist.css('background-color','var(--cerb-color-table-header-background-alt)');

			var $header_links = $worklist.find('> tbody > tr:first td:nth(1)');

			$header_links.children().each(function(e) {
				var $this = $(this);

				if(!$this.is('a.minimal, input:checkbox')) {
					$this.remove();
				}
			});

			var $worklist_body = $('#view{$view->id}').find('TABLE.worklistBody');
			$worklist_body.find('a.subject').each(function() {
				$txt = $('<b class="subject"></b>').text($(this).text());
				$txt.insertBefore($(this));
				$(this).remove();
			});

			var $actions = $('#{$view->id}_actions');
			$actions.html('');

			// If there is a marquee, add its record to the selection
			var $marquee = $view.find('div.cerb-view-marquee');

			if($marquee.length > 0) {
				var $marquee_trigger = $marquee.find('a.cerb-peek-trigger');
				var $buffer = $('form#chooser{$view->id} UL.buffer');

				var $label = $marquee_trigger.text();
				var $value = $marquee_trigger.attr('data-context-id');

				if($label.length > 0 && $value.length > 0) {
					if(0==$buffer.find('input:hidden[value="'+$value+'"]').length) {
						var $li = $('<li></li>').text($label);

						var $hidden = $('<input type="hidden">');
						$hidden.attr('name', 'to_context_id[]');
						$hidden.attr('title', $label);
						$hidden.attr('value', $value);
						$hidden.appendTo($li);

						var $a = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>');
						$a.appendTo($li);

						$buffer.append($li);
					}

					{if $single}
					$buffer.closest('form').find('button.submit').click();
					{/if}
				}
			}
		}

		on_refresh();

		$popup.delegate('DIV[id^=view]','view_refresh', on_refresh);

		$('#view{$view->id}').delegate('TABLE.worklistBody input:checkbox', 'check', function(event) {
			var checked = $(this).is(':checked');

			var $view = $('#viewForm{$view->id}');
			var $buffer = $('form#chooser{$view->id} UL.buffer');

			var $tbody = $(this).closest('tbody');

			var $label = $tbody.find('.subject').text();
			var $value = $(this).val();

			if(checked) {
				if($label.length > 0 && $value.length > 0) {
					if(0==$buffer.find('input:hidden[value="'+$value+'"]').length) {
						var $li = $('<li></li>').text($label);

						var $hidden = $('<input type="hidden">');
						$hidden.attr('name', 'to_context_id[]');
						$hidden.attr('title', $label);
						$hidden.attr('value', $value);
						$hidden.appendTo($li);

						var $a = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>');
						$a.appendTo($li);

						$buffer.append($li);
					}

					{if $single}
					$buffer.closest('form').find('button.submit').click();
					{/if}
				}

			} else {
				$buffer.find('input:hidden[value="'+$value+'"]').closest('li').remove();
			}

		});

		$("form#chooser{$view->id} button.submit").click(function(event) {
			event.stopPropagation();
			var $popup = genericAjaxPopupFetch('{$layer}');
			var $buffer = $($popup).find('UL.buffer input:hidden');
			var $labels = [];
			var $values = [];

			$buffer.each(function() {
				$labels.push($(this).attr('title'));
				$values.push($(this).val());
			});

			// Trigger event
			var event = jQuery.Event('chooser_save');
			event.labels = $labels;
			event.values = $values;
			$popup.trigger(event);

			genericAjaxPopupDestroy('{$layer}');
		});
	});

	$popup.one('dialogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});

});
</script>