{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleProfileTabAction">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="availability">

<fieldset class="peek">
	<legend>{'preferences.account.availability.calendar_id'|devblocks_translate}</legend>
	
	<div style="margin-left:10px;"></div>
	
	<button type="button" class="chooser-abstract" data-field-name="availability_calendar_id" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-single="true" data-query="owner.worker:(id:{$worker->id})" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
	
	<ul class="bubbles chooser-container">
		{$calendar = DAO_Calendar::get($worker->calendar_id)}
		{if $calendar}
			<li><input type="hidden" name="availability_calendar_id" value="{$calendar->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-context-id="{$calendar->id}">{$calendar->name}</a></li>
		{/if}
	</ul>
</fieldset>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	
	$frm.find('.chooser-abstract')
		.cerbChooserTrigger()
		;
	
	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$frm.find('button.submit').on('click', function(e) {
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>
