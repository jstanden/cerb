{if empty($calendars)}{$calendars = DAO_Calendar::getAll()}{/if}

<div class="cerb-ui-form--field set-date-mode">
	<label class="cerb-ui-form--label">Using</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[mode]" value="" {if empty($params.mode)}checked="checked"{/if}> Placeholders</label>
		<label><input type="radio" name="{$namePrefix}[mode]" value="calendar" {if $params.mode=='calendar'}checked="checked"{/if}> Calendar availability</label>
	</div>
</div>

<div class="date-mode date-mode-placeholders" style="{if !empty($params.mode)}display:none;{/if}">
	<textarea name="{$namePrefix}[value]" rows="3" cols="45" style="width:100%;" class="placeholders" placeholder="e.g. '+2 hours', '8am', 'tomorrow 5pm', 'next Thursday 3pm'">{$params.value}</textarea>
</div>

<div class="date-mode date-mode-calendar" style="{if $params.mode != 'calendar'}display:none;{/if}">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">To</label>
		<div>
			<input type="text" name="{$namePrefix}[calendar_reldate]" value="{$params.calendar_reldate}" size="24" placeholder="2 hours">
			of availability from now
		</div>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Based on calendar</label>
		<select name="{$namePrefix}[calendar_id]">
			<option value=""></option>
			{foreach from=$calendars item=calendar}
			<option value="{$calendar->id}" {if $params.calendar_id==$calendar->id}selected="selected"{/if}>{$calendar->name}</option>
			{/foreach}
		</select>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	
	$action.find('div.set-date-mode input:radio').change(function() {
		var $radio = $(this);
	
		$action.find('div.date-mode').hide();
		
		if($radio.val() == 'calendar') {
			$action.find('div.date-mode-calendar').fadeIn();
		} else {
			$action.find('div.date-mode-placeholders').fadeIn();
		}
		
	});
});
</script>