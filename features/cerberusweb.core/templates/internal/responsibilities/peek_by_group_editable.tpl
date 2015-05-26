<form id="group{$group->id}Responsibilities" action="javascript:;" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="responsibilities">
<input type="hidden" name="action" value="saveResponsibilitiesPopup">
<input type="hidden" name="context" value="{CerberusContexts::CONTEXT_GROUP}">
<input type="hidden" name="context_id" value="{$group->id}">

{foreach from=$buckets item=bucket}
	<fieldset class="peek cerb-delta-slider-container" style="display:inline-block;">
		<legend>{$bucket->name}</legend>
	
		<div style="margin-left:15px;">
	
		{foreach from=$members item=member}
		{$worker = $workers.{$member->id}}
		{$responsibility = $responsibilities.{$bucket->id}.{$member->id}}
		
		{if $worker}
		<div class="cerb-delta-slider-container" style="display:block;">
			<label>{$worker->getName()} <small>{if !empty($worker->title)}({$worker->title}){/if}</small></label>
			
			<input type="hidden" name="responsibilities[{$bucket->id}][{$worker->id}]" value="{$responsibility|default:0}"  data-worker-id="{$worker->id}"  data-bucket-id="{$bucket->id}">

			<div class="cerb-delta-slider {if $responsibility < 50}cerb-slider-red{elseif $responsibility > 50}cerb-slider-green{else}cerb-slider-gray{/if}">
				<span class="cerb-delta-slider-midpoint"></span>
			</div>
		</div>
		{/if}
		
		{/foreach}
		
		</div>
	
	</fieldset>
{/foreach}

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	var $frm = $('#group{$group->id}Responsibilities');
	
	$popup.one('popup_open', function(event,ui) {
		
		$popup.dialog('option','title',"{'common.responsibilities'|devblocks_translate|capitalize}");
		
		$frm.find('div.cerb-delta-slider').each(function() {
			var $this = $(this);
			var $input = $this.siblings('input:hidden');
			var $label = $this.siblings('label');
			var $level = $label.find('small');
			
			$this.slider({
				disabled: false,
				value: $input.val(),
				min: 0,
				max: 100,
				step: 10,
				range: 'min',
				slide: function(event, ui) {
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
				},
				stop: function(event, ui) {
					$input.val(ui.value);
				}
			});
		});
		
		$frm.find('button.submit').click(function() {
			genericAjaxPopupPostCloseReloadView('peek', $frm, null, false, 'responsibilities_save');
		});
		
	});

});
</script>