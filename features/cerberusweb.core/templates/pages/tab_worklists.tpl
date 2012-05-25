{if empty($list_ids)}
<form action="#" onsubmit="return false;">
<div class="help-box" style="padding:5px;border:0;">
	<h1 style="margin-bottom:5px;text-align:left;">Let's put this space to good use</h1>
	
	<p>
		You now have a blank tab.  You can click the  
		<button type="button" onclick="$btn=$('#frmWorkspacePage{$page->id} button.edit-tab');$(this).effect('transfer', { to:$btn, className:'effects-transfer' }, 500, function() { $btn.effect('pulsate', {  times: 3 }, function(e) { $(this).click(); } ); } );"><span class="cerb-sprite2 sprite-ui-tab-gear"></span> Edit Tab</button> 
		button to display any number of worklists with the exact information you want. 
	</p>
</div>
</form>
{/if}

<div id="divWorkspaceTab{$tab->id}"></div>

<script type="text/javascript">
	$workspace = $('#frmWorkspaceTab{$tab->id}');
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