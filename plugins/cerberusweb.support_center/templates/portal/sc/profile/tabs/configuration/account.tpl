{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="account">

{$account_fields = [contact_first_name,contact_last_name,contact_title,contact_username,contact_gender,contact_location,contact_dob,contact_phone,contact_mobile,contact_photo]}
{$account_labels = ['First Name','Last Name','Title','Username','Gender','Location','Date of Birth','Phone','Mobile','Photo']}
{$account_types = ['text','text','text','username','gender','location','date','phone','phone','image']}
{$type_meta = ['text'=>['text','text'],'username'=>['id-card','username'],'gender'=>['user','gender'],'location'=>['location','location'],'date'=>['calendar','date'],'phone'=>['phone-handset','phone'],'image'=>['picture','image']]}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.contact'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-ui-form">
		{foreach from=$account_fields item=field name=fields}
		{$idx = $smarty.foreach.fields.index}
		{$t = $account_types[$idx]}
		{$tm = $type_meta.$t}
		{$cur = $show_fields.$field|default:0}
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-3">
			<input type="hidden" name="fields[]" value="{$field}">
			<input type="hidden" name="fields_visible[]" id="acctVis_{$form_id}_{$idx}" value="{$cur}">

			<span class="cerb-icons cerb-icon-{$tm[0]} cerb-u-text-muted" title="{$tm[1]|capitalize}"></span>
			<span class="cerb-u-flex-1">{$account_labels[$idx]|capitalize}</span>

			<div class="cerb-ui-switcher" data-cerb-input="acctVis_{$form_id}_{$idx}">
				<button type="button" data-value="2" title="Editable"{if 2==$cur} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-edit"></span></button>
				<button type="button" data-value="1" title="Read only"{if 1==$cur} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-eye-open"></span></button>
				<button type="button" data-value="0" title="Hidden"{if !$cur} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-eye-close"></span></button>
			</div>
		</div>
		{/foreach}
	</div>
</div>

<div class="buttons cerb-u-mt-2">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	// Per-field visibility Switcher (Editable / Read only / Hidden) bound to its hidden fields_visible[] input.
	if(window.CerbUI && CerbUI.Switcher) {
		$frm.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
			let input = document.getElementById(this.getAttribute('data-cerb-input'));
			if(!input) return;
			new CerbUI.Switcher(this, { value: input.value, onSelect: function(value) { input.value = value; } });
		});
	}

	$frm.find('button.save').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.clearAlerts();
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.createAlertError(json.error);
				} else if (json.message) {
					Devblocks.createAlert(json.message, 'success', 5000);
				} else {
					Devblocks.createAlert('Saved!', 'success', 5000);
				}
			}
		});
	});
});
</script>
