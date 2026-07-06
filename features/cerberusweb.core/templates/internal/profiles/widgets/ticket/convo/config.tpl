{$convo_uid = uniqid()}
<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Ticket</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label"><a class="cerb-chooser-ticket cerb-u-cursor-pointer" data-context="{CerberusContexts::CONTEXT_TICKET}" data-single="true">{{'common.id'|devblocks_translate}}</a> <span class="cerb-ui-form--hint">(leave blank to auto-detect)</span></label>
				<input type="text" name="params[ticket_id]" value="{$widget->extension_params.ticket_id}" class="placeholders" autocomplete="off" spellcheck="false">
			</div>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">{'common.comments'|devblocks_translate|capitalize}</div>
		</div>

		<div>
			<input type="hidden" name="params[comments_mode]" id="commentsMode{$convo_uid}" value="{$widget->extension_params.comments_mode|default:0}">
			<div class="cerb-ui-switcher" data-cerb-input="commentsMode{$convo_uid}">
				<button type="button" data-value="0" {if !$widget->extension_params.comments_mode}class="cerb-ui-switcher--active"{/if}>Show</button>
				<button type="button" data-value="2" {if 2 == $widget->extension_params.comments_mode}class="cerb-ui-switcher--active"{/if}>Show with the latest comment pinned at the top</button>
				<button type="button" data-value="1" {if 1 == $widget->extension_params.comments_mode}class="cerb-ui-switcher--active"{/if}>{'common.hide'|devblocks_translate|capitalize}</button>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $config = $('#widget{$widget->id}Config');
	let $input = $config.find('input[name="params[ticket_id]"]');

	if(window.CerbUI && CerbUI.RecordChooser) CerbUI.RecordChooser.pickerLink($config.find('.cerb-chooser-ticket')[0], { input: $input });

	if(window.CerbUI && CerbUI.Switcher) {
		let commentsModeEl = $config.find('.cerb-ui-switcher[data-cerb-input="commentsMode{$convo_uid}"]')[0];
		let $commentsMode = $config.find('#commentsMode{$convo_uid}');
		if(commentsModeEl)
			new CerbUI.Switcher(commentsModeEl, { value: $commentsMode.val(), onSelect: function(value) { $commentsMode.val(value); } });
	}
});
</script>