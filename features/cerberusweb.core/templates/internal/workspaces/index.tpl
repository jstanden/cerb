<form action="{devblocks_url}{/devblocks_url}" id="frmWorkspace{$workspace->id}" method="POST" style="margin:5px;">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="">

	{if $workspace->isWriteableByWorker($active_worker)}
		<button type="button" class="edit"><span class="cerb-sprite sprite-gear"></span> {$translate->_('dashboard.edit')|capitalize}</button>
		&nbsp;
	{/if}
	
	{if !($workspace->owner_context == CerberusContexts::CONTEXT_WORKER && $workspace->owner_context_id == $active_worker->id)}
		{$context = Extension_DevblocksContext::get($workspace->owner_context)}
		{if !empty($context)}
			{$meta = $context->getMeta({$workspace->owner_context_id})}
			Owned by <b>{$meta.name}</b> ({$context->manifest->name})
		{/if}
	{/if}
</form>

<div id="divWorkspace{$workspace->id}"></div>

<script type="text/javascript">
	
	// Edit workspace actions
	$workspace = $('#frmWorkspace{$workspace->id}');
	$workspace.find('button.edit').click(function(e) {
		$popup = genericAjaxPopup('peek','c=internal&a=showEditWorkspacePanel&id={$workspace->id}',null,true,'600');
		$popup.one('workspace_save',function(e) {
			$tabs = $('#frmWorkspace{$workspace->id}').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('load', $tabs.tabs('option','selected'));
			}
		});
		$popup.one('workspace_delete',function(e) {
			$tabs = $('#frmWorkspace{$workspace->id}').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('remove', $tabs.tabs('option','selected'));
			}
		});
	});
	
	// Lazy loading
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
								.html(html)
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
						.html(html)
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