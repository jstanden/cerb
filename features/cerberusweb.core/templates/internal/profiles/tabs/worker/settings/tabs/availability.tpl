{$form_id = uniqid()}
<form id="{$form_id}" class="cerb-ui-form" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="availability">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'preferences.account.availability.calendar_id'|devblocks_translate}</div></div>

	<div class="cerb-ui-form--field">
		<div class="cerb-ui-record-chooser" id="availCalendarChooser">
			{$calendar = DAO_Calendar::get($worker->calendar_id)}
			{if $calendar}
				<li data-context-id="{$calendar->id}" data-label="{$calendar->name}"></li>
			{/if}
		</div>
	</div>
</div>

<div>
	<div class="cerb-ui-toolbar-strip">
		<button type="button" id="btnSave_{$form_id}" class="cerb-ui-toolbar-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	if(window.CerbUI && CerbUI.RecordChooser)
		new CerbUI.RecordChooser($frm.find('#availCalendarChooser')[0], { context: '{CerberusContexts::CONTEXT_CALENDAR}', name: 'availability_calendar_id', emptyIcon: 'calendar', query: 'owner.worker:(id:{$worker->id})' });

	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;

	$frm.find('#btnSave_{$form_id}').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>
