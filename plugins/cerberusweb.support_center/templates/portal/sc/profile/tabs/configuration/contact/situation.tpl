{$uniq_id = uniqid('fieldset')}
<div id="{$uniq_id}" class="cerb-ui-panel cerb-ui-panel--spaced drag cerb-u-cursor-move">
	<div class="cerb-ui-header cerb-ui-header--tight">
	{if !empty($reason)}
		<div class="cerb-ui-header--title-sm{if $params.is_hidden} cerb-u-text-muted{/if}">{$reason}{if !empty($params.is_hidden)} ({'portal.sc.cfg.situation.hidden'|devblocks_translate|lower}){/if}</div>
	{else}
		<div class="cerb-ui-header--title-sm">{'portal.sc.cfg.add_contact_situation'|devblocks_translate}</div>
	{/if}
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
			<div>
				<select name="status[{$uniq_id}]">
					<option value="" {if empty($params.is_hidden)}selected="selected"{/if}>{'portal.sc.cfg.situation.visible'|devblocks_translate|capitalize}</option>
					<option value="hidden" {if !empty($params.is_hidden)}selected="selected"{/if}>{'portal.sc.cfg.situation.hidden'|devblocks_translate|capitalize}</option>
					<option value="deleted">{'portal.sc.cfg.situation.deleted'|devblocks_translate|capitalize}</option>
				</select>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.sc.cfg.reason_contacting'|devblocks_translate} <span class="cerb-ui-form--hint">{'portal.sc.cfg.reason_contacting_hint'|devblocks_translate}</span></label>
			<input type="text" name="contact_reason[{$uniq_id}]" value="{$reason}">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.cfg.deliver_to'|devblocks_translate} <span class="cerb-ui-form--hint">{'portal.cfg.deliver_to_hint'|devblocks_translate:$replyto_default->email}</span></label>
			<input type="text" name="contact_to[{$uniq_id}]" value="{$params.to}">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.cfg.followup_questions'|devblocks_translate} <span class="cerb-ui-form--hint">{'portal.sc.cfg.followup_questions_hint'|devblocks_translate}</span></label>
			<div class="container">
				<div class="template" style="display:none;">
					{include file="devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/contact/situation_followups.tpl" q=null field_id=null uniq_id=$uniq_id}
				</div>
				{foreach from=$params.followups key=q item=field_id name=followups}
					{include file="devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/contact/situation_followups.tpl" field_id=$field_id uniq_id=$uniq_id}
				{/foreach}
			</div>
			<div>
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle add"><span class="cerb-icons cerb-icon-circle-plus"></span> {'portal.cfg.followup_questions'|devblocks_translate|capitalize}</button>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $fieldset = $('#{$uniq_id}');

	let $situationContainer = $fieldset.find('div.container');

	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($situationContainer.get(0), { items: 'div.drag' });

	$situationContainer
		.on('click', function(e) {
			e.stopPropagation();
			let $target = $(e.target);

			if(!$target.is('button'))
				$target = $target.closest('button');

			if($target.is('[data-cerb-button-remove]')) {
				$target.closest('div.drag').remove();
			}
		})
	;

	$fieldset.find('BUTTON.add')
		.on('click', function(e) {
			e.stopPropagation();
			let $clone = $fieldset.find('DIV.template DIV.drag').clone();
			$fieldset.find('DIV.container').append($clone);
		})
	;
});
</script>
