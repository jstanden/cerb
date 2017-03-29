{if $macro_event && $context && $context_id}
<button type="button" class="split-left" onclick="$(this).next('button').click();" title="{'common.bots'|devblocks_translate|capitalize}{if $pref_keyboard_shortcuts} (M){/if}"><img src="{devblocks_url}c=avatars&context=app&id=0{/devblocks_url}" style="width:22px;height:22px;margin:-3px 0px 0px 2px;"></button><!--  
--><button type="button" class="split-right" id="btnDisplayMacros" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-query="event:{$macro_event} usableBy.worker:{$active_worker->id}" data-single="true"><span class="glyphicons glyphicons-chevron-down" style="font-size:12px;color:white;"></span></button>

<script type="text/javascript">
$(function() {
var $button = $('#btnDisplayMacros')
	.click(function(e) {
		var $trigger = $(this);
		var context = $trigger.attr('data-context');
		var q = $trigger.attr('data-query');
		var single = $trigger.attr('data-single') != null ? '1' : '';
		var width = $(window).width()-100;
		
		var $chooser = genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=chooserOpen&context=' + encodeURIComponent(context) + '&q=' + encodeURIComponent(q) + '&single=' + encodeURIComponent(single),null,true,width);

		$chooser.on('chooser_save', function(evt) {
			var behavior_id = evt.values[0];
			
			if(!behavior_id)
				return;
			
			genericAjaxPopup('peek','c=internal&a=showMacroSchedulerPopup&context={$context}&context_id={$context_id}&macro=' + encodeURIComponent(behavior_id) + '&return_url={$return_url|escape:'url'}',null,false,'50%');
		})
	})
;
});
</script>
{/if}