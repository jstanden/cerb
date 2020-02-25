<form id="group{$group->id}Responsibilities" action="javascript:;" onsubmit="return false;">

<div class="cerb-delta-slider-container" style="display:none;margin-right:0;">
	<div class="cerb-delta-slider cerb-slider-gray">
		<span class="cerb-delta-slider-midpoint"></span>
	</div>
</div>

<div style="column-width:275px;">
{foreach from=$buckets item=bucket}
	<fieldset class="peek" style="vertical-align:top;break-inside:avoid-column;margin:0;">
		<legend>
			<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$bucket->id}">{$bucket->name}</a>
		</legend>
	
		<div style="margin-left:15px;">
		{foreach from=$members item=member}
		{$worker = $workers.{$member->id}}
		{$responsibility = $responsibilities.{$bucket->id}.{$member->id}}
		
		{if $worker}
		<div class="cerb-slider-card" style="width:250px;display:block;margin:0 10px 10px 5px;">
			<label>
				<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}"><b>{$worker->getName()}</b></a> {if $worker->title}({$worker->title}){/if}
			</label>
			
			<div class="cerb-slider-readonly" style="margin-bottom:15px;">
				<input type="hidden" name="responsibilities[{$bucket->id}][{$worker->id}]" value="{$responsibility|default:0}"  data-worker-id="{$worker->id}" data-bucket-id="{$bucket->id}">
				<div style="margin:5px 0 0 5px;position:relative;width:250px;height:9px;background-color:rgb(230,230,230);border-radius:9px;">
					<span style="display:inline-block;background-color:rgb(200,200,200);height:18px;width:1px;position:absolute;top:-4px;margin-left:1px;left:50%;"></span>
					<div class="cerb-slider-handle" style="position:relative;margin-left:-6px;top:-3px;left:{$responsibility}%;width:15px;height:15px;border-radius:15px;background-color:{if $responsibility < 50}rgb(230,70,70);{elseif $responsibility > 50}rgb(0,200,0);{else}rgb(175,175,175);{/if}"></div>
				</div>
			</div>
		</div>
		{/if}
		
		{/foreach}
		</div>
	
	</fieldset>
{/foreach}
</div>

<button type="button" class="done"><span class="glyphicons glyphicons-circle-ok"></span> {'common.done'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	var $frm = $('#group{$group->id}Responsibilities');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.responsibilities'|devblocks_translate|capitalize}: {$group->name}");
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		var $slider_helper = $frm.find('> .cerb-delta-slider-container');
		var $slider_helper_control = $slider_helper.find('.cerb-delta-slider');
		
		$slider_helper_control.each(function() {
			var $this = $(this);
			var $label = $this.siblings('label');
			var $level = $label.find('small');
			
			var funcColorizeHandle = function(e, ui) {
				e.stopPropagation();
				
				$this.removeClass('cerb-slider-gray cerb-slider-red cerb-slider-green');
				
				if(ui.value < 50) {
					$this.addClass('cerb-slider-red');
					$this.slider('option', 'range', 'min');
				} else if(ui.value > 50) {
					$this.addClass('cerb-slider-green');
					$this.slider('option', 'range', 'max');
				} else {
					$this.addClass('cerb-slider-gray');
					$this.slider('option', 'range', false);
				}
			};
			
			$this.slider({
				disabled: false,
				value: 0,
				min: 0,
				max: 100,
				step: 10,
				range: 'min',
				slide: funcColorizeHandle,
				change: funcColorizeHandle
			});
		});
		
		$popup.find('fieldset.peek').parent().on('mouseenter mouseleave', '.cerb-slider-card', 
			function(e) {
				e.stopPropagation();
				
				var $this = $(this);
				var $slider = $this.find('.cerb-slider-readonly');
				var $input = $slider.find('input:hidden');
				
				if('mouseenter' == e.type) {
					$slider_helper_control.slider('value', $input.val());
					$slider_helper.insertAfter($slider.hide()).show();
					
				} else {
					var value = $slider_helper_control.slider('value');
					var $slider_handle = $slider.find('.cerb-slider-handle');
					
					// If the value changed
					if($input.val() != value) {
						var form_data = new FormData();
						form_data.append('c', 'profiles');
						form_data.append('a', 'invokeWidget');
						form_data.append('widget_id', '{$widget->id}');
						form_data.append('action', 'savePopupJson');
						form_data.append('worker_id', $input.attr('data-worker-id'));
						form_data.append('bucket_id', $input.attr('data-bucket-id'));
						form_data.append('responsibility', value);
						
						genericAjaxPost(form_data, '', null, function(err) {
							Devblocks.clearAlerts();
							
							if(err.error) {
								Devblocks.createAlertError(err.error);
								
							} else {
								Devblocks.createAlert('Saved!');
							}
						});
					}
					
					$input.val(value);
					
					$slider_handle.css('left', value + '%');
					
					if(value < 50) {
						$slider_handle.css('background-color', 'rgb(230,70,70)');
					} else if (value > 50) {
						$slider_handle.css('background-color', 'rgb(0,200,0)');
					} else {
						$slider_handle.css('background-color', 'rgb(175,175,175)');
					}
					
					$slider.show();
					$slider_helper.detach();
				}
			}
		);
		
		$frm.find('button.done').click(function(e) {
			e.stopPropagation();
			genericAjaxPopupClose($popup, 'responsibilities_save');
		});
		
	});
});
</script>