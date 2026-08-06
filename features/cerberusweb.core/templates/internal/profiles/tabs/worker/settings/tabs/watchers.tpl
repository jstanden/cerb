{$form_id = uniqid()}
<form id="{$form_id}" class="cerb-ui-form" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="watchers">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">If I'm watching something, notify me when these events happen</div>
		<div class="cerb-ui-header--right cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<a data-cerb-link="check_all" class="cerb-u-cursor-pointer">{'common.all'|devblocks_translate|lower}</a>
			<span class="cerb-u-text-muted">|</span>
			<a data-cerb-link="check_none" class="cerb-u-cursor-pointer">{'common.none'|devblocks_translate|lower}</a>
		</div>
	</div>

	<div id="watcherActivities">
		{foreach from=$activities item=activity key=activity_point}
		{$selected = !in_array($activity_point,$dont_notify_on_activities)}
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-mb-1">
			<input type="hidden" name="activity_point[]" value="{$activity_point}">
			<label class="cerb-ui-toggle"><input type="checkbox" name="activity_enable[]" id="act_{$activity_point}_{$form_id}" value="{$activity_point}" {if $selected}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
			<label for="act_{$activity_point}_{$form_id}">{$activity.params.label_key|devblocks_translate}</label>
		</div>
		{/foreach}
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

	if(window.CerbUI && CerbUI.Toggle)
		$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

	$frm.find('[data-cerb-link=check_all]').on('click', function(e) {
		e.stopPropagation();
		checkAll('watcherActivities', true);
	});

	$frm.find('[data-cerb-link=check_none]').on('click', function(e) {
		e.stopPropagation();
		checkAll('watcherActivities', false);
	});

	$frm.find('#btnSave_{$form_id}').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>
