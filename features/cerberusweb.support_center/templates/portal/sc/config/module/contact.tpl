<label><input type="checkbox" name="allow_subjects" value="1" {if $allow_subjects}checked="checked"{/if}> {$translate->_('portal.sc.cfg.open_ticket.allow_custom_subjects')}</label><br>
<br>

<b>{$translate->_('portal.sc.cfg.open_ticket.attachments')}</b><br>
<label><input type="radio" name="attachments_mode" value="0" {if !$attachments_mode}checked="checked"{/if}> {$translate->_('portal.sc.cfg.open_ticket.attachments.anybody')}</label>
<label><input type="radio" name="attachments_mode" value="1" {if 1==$attachments_mode}checked="checked"{/if}> {$translate->_('portal.sc.cfg.open_ticket.attachments.logged_in')}</label>
<label><input type="radio" name="attachments_mode" value="2" {if 2==$attachments_mode}checked="checked"{/if}> {$translate->_('portal.sc.cfg.open_ticket.attachments.nobody')}</label>
<br>
<br>

<b>{$translate->_('portal.cfg.captcha')}</b> {$translate->_('portal.cfg.captcha_hint')}<br>
<label><input type="radio" name="captcha_enabled" value="1" {if $captcha_enabled}checked="checked"{/if}> {$translate->_('portal.cfg.enabled')}</label>
<label><input type="radio" name="captcha_enabled" value="0" {if !$captcha_enabled}checked="checked"{/if}> {$translate->_('portal.cfg.disabled')}</label>
<br>
<br>

<div id="situations" class="container">
{foreach from=$dispatch item=params key=reason}
	{include file="devblocks:cerberusweb.support_center::portal/sc/config/module/contact/situation.tpl" reason=$reason params=$params}
{/foreach}
</div>

<div style="margin-left:10px;margin-bottom:10px;">
	<button id="btnAddSituation" type="button" onclick=""><span class="cerb-sprite sprite-add"></span> {'portal.cfg.add_new_situation'|devblocks_translate|capitalize}</button>
</div>

<script type="text/javascript">
	$('DIV#situations')
	.sortable(
		{ items: 'FIELDSET.drag', placeholder:'ui-state-highlight' }
	)
	;
	
	$('BUTTON#btnAddSituation')
	.click(function() {
		genericAjaxGet('','c=config&a=handleSectionAction&section=portal&action=addContactSituation',function(html) {
			$clone = $(html);
			$container = $('DIV#situations');
			$container.append($clone);
		});
	})
	;
</script>
