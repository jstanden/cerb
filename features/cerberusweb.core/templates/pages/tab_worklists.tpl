<form action="{devblocks_url}{/devblocks_url}" id="frmWorkspaceTab{$tab->id}" method="POST" class="toolbar">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="">

	{if $page->isWriteableByWorker($active_worker)}
		<button type="button" class="edit toolbar-item"><span class="cerb-sprite2 sprite-ui-tab-gear"></span> Edit Tab</button>
		&nbsp;
	{/if}
</form>

<div id="divWorkspaceTab{$tab->id}"></div>

<script type="text/javascript">
	$workspace = $('#frmWorkspaceTab{$tab->id}');
	
	// Edit workspace tab
	$workspace.find('button.edit').click(function(e) {
		$popup = genericAjaxPopup('peek','c=pages&a=showEditWorkspaceTab&id={$tab->id}',null,true,'600');
		$popup.one('workspace_save',function(e) {
			$tabs = $('#frmWorkspaceTab{$tab->id}').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('load', $tabs.tabs('option','selected'));
			}
		});
		$popup.one('workspace_delete',function(e) {
			$tabs = $('#frmWorkspaceTab{$tab->id}').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('remove', $tabs.tabs('option','selected'));
			}
		});
	});
	
	// Lazy loading
	$workspace = $('#divWorkspaceTab{$tab->id}');
	$ajaxQueue = $({});
	
	{foreach from=$list_ids item=list_id}
	$ajaxQueue.queue(function(next) {
		$div = $('<div style="margin-bottom:10px;"></div>');
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
						'c=pages&a=initWorkspaceList&list_id={$list_id}',
						function(html) {
							$this
								.html(html)
								;
							//$this.find('DIV[id^=view]:first').trigger('view_refresh');
							next();
						}
					);
				});
			});
			next();
			
		} else {
			genericAjaxGet(
				$div,
				'c=pages&a=initWorkspaceList&list_id={$list_id}',
				function(html){
					$div
						.html(html)
						;
					//$div.find('DIV[id^=view]:first').trigger('view_refresh');
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