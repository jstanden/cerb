<div class="set-date-mode">
	<b>Using:</b> 
	<div style="margin:0px 0px 5px 5px;">
		<label><input type="radio" name="{$namePrefix}[mode]" value="" {if empty($params.mode)}checked="checked"{/if}> Placeholders</label>
		<label><input type="radio" name="{$namePrefix}[mode]" value="calendar" {if $params.mode=='calendar'}checked="checked"{/if}> Calendar availability</label>
	</div>
</div>

<div class="date-mode date-mode-placeholders" style="{if !empty($params.mode)}display:none;{/if}">
	<textarea name="{$namePrefix}[value]" rows="3" cols="45" style="width:100%;" class="placeholders" placeholder="e.g. '+2 hours', '8am', 'tomorrow 5pm', 'next Thursday 3pm'">{$params.value}</textarea>
</div>

<div class="date-mode date-mode-calendar" style="{if $params.mode != 'calendar'}display:none;{/if}">
	<b>To:</b>
	
	<div style="margin:0px 0px 5px 5px;">
		<input type="text" name="{$namePrefix}[calendar_reldate]" value="{$params.calendar_reldate}" size="24" placeholder="2 hours">
		of availability from now
	</div>
	
	<b>Based on calendar:</b>
	<div style="margin:0px 0px 5px 5px;">
		<select name="{$namePrefix}[calendar_id]">
			<option value=""></option>
			{foreach from=$calendars item=calendar}
			<option value="{$calendar->id}" {if $params.calendar_id==$calendar->id}selected="selected"{/if}>{$calendar->name}</option>
			{/foreach}
		</select>
	</div>
</div>

<script type="text/javascript">
var $action = $('fieldset#{$namePrefix}');

$action.elastic();

$action.find('div.set-date-mode input:radio').change(function() {
	var $radio = $(this);
	var $action = $('fieldset#{$namePrefix}');

	$action.find('div.date-mode').hide();
	
	if($radio.val() == 'calendar') {
		$action.find('div.date-mode-calendar').fadeIn();
	} else {
		$action.find('div.date-mode-placeholders').fadeIn();
	}
	
});
</script>
