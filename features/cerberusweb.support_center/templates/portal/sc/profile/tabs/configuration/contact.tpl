{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="contact">

<b>{'portal.sc.cfg.open_ticket.allow_headers'|devblocks_translate}</b><br>
<label><input type="checkbox" name="allow_cc" value="1" {if $allow_cc}checked="checked"{/if}> {'message.header.cc'|devblocks_translate|capitalize}</label> 
<label><input type="checkbox" name="allow_subjects" value="1" {if $allow_subjects}checked="checked"{/if}> {'message.header.subject'|devblocks_translate|capitalize}</label> 
<br>
<br>

<b>{'portal.sc.cfg.open_ticket.attachments'|devblocks_translate}</b><br>
<label><input type="radio" name="attachments_mode" value="0" {if !$attachments_mode}checked="checked"{/if}> {'common.everyone'|devblocks_translate|capitalize}</label>
<label><input type="radio" name="attachments_mode" value="1" {if 1==$attachments_mode}checked="checked"{/if}> {'portal.sc.cfg.open_ticket.attachments.logged_in'|devblocks_translate}</label>
<label><input type="radio" name="attachments_mode" value="2" {if 2==$attachments_mode}checked="checked"{/if}> {'common.nobody'|devblocks_translate|capitalize}</label>
<br>
<br>

<b>{'portal.cfg.captcha'|devblocks_translate}</b> {'portal.cfg.captcha_hint'|devblocks_translate}<br>
<label><input type="radio" name="captcha_enabled" value="1" {if 1 == $captcha_enabled}checked="checked"{/if}> {'common.everyone'|devblocks_translate|capitalize}</label>
<label><input type="radio" name="captcha_enabled" value="2" {if 2 == $captcha_enabled}checked="checked"{/if}> {'common.anonymous'|devblocks_translate|capitalize}</label>
<label><input type="radio" name="captcha_enabled" value="0" {if !$captcha_enabled}checked="checked"{/if}> {'common.nobody'|devblocks_translate|capitalize}</label>
<br>
<br>

<div id="situations" class="container">
{foreach from=$dispatch item=params key=reason}
	{include file="devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/contact/situation.tpl" reason=$reason params=$params}
{/foreach}
</div>

<div style="margin-left:10px;margin-bottom:10px;">
	<button id="btnAddSituation" type="button" onclick=""><span class="glyphicons glyphicons-circle-plus"></span> {'portal.cfg.add_new_situation'|devblocks_translate|capitalize}</button>
</div>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
		
	$frm.find('button.submit').on('click', function(e) {
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
	
	$frm.find('DIV#situations')
		.sortable(
			{ items: 'FIELDSET.drag', placeholder:'ui-state-highlight' }
		)
	;
	
	$frm.find('BUTTON#btnAddSituation')
	.click(function() {
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'community_portal');
		formData.set('action', 'showConfigTab');
		formData.set('config_tab', 'contact');
		formData.set('tab_action', 'addContactSituation');
		formData.set('portal_id', '{$portal->id}');

		genericAjaxPost(formData, '', '', function(html) {
			$clone = $(html);
			$container = $('DIV#situations');
			$container.append($clone);
		});
	})
	;
});
</script>
