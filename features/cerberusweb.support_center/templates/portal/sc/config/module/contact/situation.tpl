{$uniq_id = uniqid()}
<fieldset style="background-image:none;background-color:rgb(239,245,255);border:0;cursor:move;" id="{$uniq_id}" class="drag">
{if !empty($reason)}
	<legend style="{if empty($params.is_hidden)}color:rgb(74,110,158);{else}color:rgb(130,130,130);{/if}cursor:pointer;">{$reason}{if !empty($params.is_hidden)} ({'portal.sc.cfg.situation.hidden'|devblocks_translate|lower}){/if}</legend>
{else}
	<legend style="color:rgb(74,110,158);cursor:pointer;">{$translate->_('portal.sc.cfg.add_contact_situation')}</legend>
{/if}

<div style="padding-left:20px;">
	<b>Status:</b>
	<select name="status[{$uniq_id}]">
		<option value="" {if empty($params.is_hidden)}selected="selected"{/if}>{'portal.sc.cfg.situation.visible'|devblocks_translate|capitalize}</option>
		<option value="hidden" {if !empty($params.is_hidden)}selected="selected"{/if}>{'portal.sc.cfg.situation.hidden'|devblocks_translate|capitalize}</option>
		<option value="deleted">{'portal.sc.cfg.situation.deleted'|devblocks_translate|capitalize}</option>
	</select>
	<br>
	<br>

	<b>{$translate->_('portal.sc.cfg.reason_contacting')}</b> {$translate->_('portal.sc.cfg.reason_contacting_hint')}<br>
	<input type="text" name="contact_reason[{$uniq_id}]" size="65" value="{$reason}"><br>
	<br>
	
	<b>{$translate->_('portal.cfg.deliver_to')}</b> {'portal.cfg.deliver_to_hint'|devblocks_translate:$replyto_default->email}<br>
	<input type="text" name="contact_to[{$uniq_id}]" size="65" value="{$params.to}"><br>
	<br>
	
	<b>{$translate->_('portal.cfg.followup_questions')}</b> {$translate->_('portal.sc.cfg.followup_questions_hint')}
	<div class="container">
		<div class="template" style="display:none;">
			{include file="devblocks:cerberusweb.support_center::portal/sc/config/module/contact/situation_followups.tpl" q=null field_id=null uniq_id=$uniq_id}
		</div>
		{foreach from=$params.followups key=q item=field_id name=followups}
			{include file="devblocks:cerberusweb.support_center::portal/sc/config/module/contact/situation_followups.tpl" field_id=$field_id uniq_id=$uniq_id}
		{/foreach}
	</div>
	<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
</div>
</fieldset>

<script type="text/javascript">
$('FIELDSET#{$uniq_id} DIV.container')
	.sortable({ items: 'DIV.drag', placeholder:'ui-state-highlight' })
	;

$('FIELDSET#{$uniq_id} BUTTON.add')
	.click(function() {
		$fieldset = $('FIELDSET#{$uniq_id}');
		$clone = $fieldset.find('DIV.template DIV.drag').clone();
		$fieldset.find('DIV.container').append($clone);
	})
	;
</script>
