<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin:5px;">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="">
	<button type="button" onclick="genericAjaxPopup('peek','c=internal&a=showEditWorkspacePanel&id={$workspace->id}&request={$request|escape:'url'}',null,true,'600');"><span class="cerb-sprite sprite-gear"></span> {$translate->_('dashboard.edit')|capitalize}</button>
</form>

<div id="divWorkspace{$workspace->id}"></div>

<script type="text/javascript">
	$workspace = $('#divWorkspace{$workspace->id}');
	$ajaxQueue = $({});
	
	{foreach from=$list_ids item=list_id}
	$ajaxQueue.queue(function(next) {
		$div = $('<div></div>');
		$div
			.appendTo($workspace)
			.html($('<div class="lazy" style="font-size:18pt;text-align:center;padding:50px;margin:20px;background-color:rgb(232,242,255);">Loading...</div>'))
			;
		
		window_fold = $(window).height() + $(window).scrollTop();
		div_top = $div.offset().top;

		if(div_top > window_fold + 100) {
			$div.one('appear',function(event) {
				var $this = $(this);
				$ajaxQueue.queue(function(next) {	
					genericAjaxGet(
						$this,
						'c=internal&a=initWorkspaceList&list_id={$list_id}',
						function(html) {
							$this
								.fadeTo("normal", 0.2)
								.html(html)
								.fadeTo("normal", 1.0)
								;
							$this.find('DIV[id^=view]:first').trigger('view_refresh');
							next();
						}
					);
				});
			});
			next();
			
		} else {
			genericAjaxGet(
				$div,
				'c=internal&a=initWorkspaceList&list_id={$list_id}',
				function(html){
					$div
						.fadeTo("normal", 0.2)
						.html(html)
						.fadeTo("normal", 1.0)
						;
					$div.find('DIV[id^=view]:first').trigger('view_refresh');
					next();
				}
			);
		}
	});
	{/foreach}

	$(window).scroll(function(event) {
		window_fold = $(window).height() + $(window).scrollTop();
		
		$lazies = $workspace.find('DIV.lazy');

		// If we have nothing else to load, unbind
		if(0 == $lazies.length) {
			$(window).unbind(event);
			return;
		}
		
		$lazies.each(function() {
			div_top = $(this).offset().top;
			if(div_top < window_fold + 50) {
				$(this)
					.removeClass('lazy')
					.parent()
					.trigger('appear')
					;
			}
		});
	});
		
</script>