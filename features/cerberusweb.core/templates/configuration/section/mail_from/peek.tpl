<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmAddyOutgoingPeek">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_from">
<input type="hidden" name="action" value="savePeek">
<input type="hidden" name="id" value="{$address->address_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{$types = $values._types}
{function tree level=0}
	{foreach from=$keys item=data key=idx}
		{$type = $types.{$data->key}}
		{if is_array($data->children) && !empty($data->children)}
			<li {if $data->key}data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"{/if}>
				{if $data->key}
					<div style="font-weight:bold;">{$data->l|capitalize}</div>
				{else}
					<div>{$idx|capitalize}</div>
				{/if}
				<ul>
					{tree keys=$data->children level=$level+1}
				</ul>
			</li>
		{elseif $data->key}
			<li data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
		{/if}
	{/foreach}
{/function}

<fieldset class="peek">
	<legend>Send worker replies as:</legend>
	
	<div style="margin-bottom:5px;">
		<b>{'common.email'|devblocks_translate|capitalize}:</b>
		<div>
			{if !empty($address->address_id)}
			{$address->email}
			{else}
			<input type="text" name="reply_from" value="" style="width:100%;" placeholder="support@example.com">
			<br>
			<span style="color:rgb(0,120,0);">(Make sure the above address delivers to the helpdesk or you won't receive replies!)</span>
			{/if}
		</div>
	</div>
	
	<div>
		<b>{'common.name'|devblocks_translate|capitalize}:</b>
		<div>
			<input type="text" name="reply_personal" value="{$address->reply_personal}" style="width:100%;" placeholder="Example, Inc." class="placeholders">
			<br>
			<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert placeholder &#x25be;</button>
			<button type="button" onclick="genericAjaxPost('frmAddyOutgoingPeek','divFromTester','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.worker&snippet_field=reply_personal');">{'common.test'|devblocks_translate|capitalize}</button>
			<button type="button" onclick="genericAjaxPopup('help', 'c=internal&a=showSnippetHelpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');">Help</button>
			
			<ul class="menu" style="width:150px;">
			{tree keys=$placeholders}
			</ul>
			
			<div id="divFromTester"></div>
		</div>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>Default signature:</legend>
	
	<textarea name="reply_signature" rows="10" cols="76" style="width:100%;" class="placeholders">{$address->reply_signature}</textarea>
	<br>
	<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert placeholder &#x25be;</button>
	<button type="button" onclick="genericAjaxPost('frmAddyOutgoingPeek','divSigTester','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.worker&snippet_field=reply_signature');">{'common.test'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&raw=1&group_id=0',function(txt) { $('#frmAddyOutgoingPeek textarea').text(txt); } );">{'common.default'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="genericAjaxPopup('help', 'c=internal&a=showSnippetHelpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');">Help</button>
	
	<ul class="menu" style="width:150px;">
	{tree keys=$placeholders}
	</ul>
	
	<div id="divSigTester"></div>
</fieldset>

<fieldset class="peek">
	<legend>Send mail using this transport:</legend>
	
	<select name="reply_mail_transport_id">
		<option value="0"> - {'common.default'|devblocks_translate|lower} -</option>
		{foreach from=$mail_transports item=mail_transport}
		<option value="{$mail_transport->id}" {if $mail_transport->id==$address->reply_mail_transport_id}selected="selected"{/if}>{$mail_transport->name}</option>
		{/foreach}
	</select>
</fieldset>

<fieldset class="peek">
	<legend>Send HTML replies using this template:</legend>
	
	<select name="reply_html_template_id">
		<option value="0"> - {'common.default'|devblocks_translate|lower} -</option>
		{foreach from=$html_templates item=html_template}
		<option value="{$html_template->id}" {if $html_template->id==$address->reply_html_template_id}selected="selected"{/if}>{$html_template->name}</option>
		{/foreach}
	</select>
</fieldset>

<fieldset class="peek">
	<legend>Make default:</legend>
	
	<label>
		<input type="checkbox" name="is_default" value="1" {if $address->is_default}checked="checked"{/if}> 
		This will be used as the sender address for outgoing mail when no other preference exists.
	</label>
</fieldset>

<fieldset class="delete" style="display:none;">
	<legend>Are you sure you want to delete this sender address?</legend>
	<p>Any groups or buckets using this sender address will be reverted to defaults.</p>
	<button name="form_action" type="submit" value="delete" class="green"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button name="form_action" type="button" class="red" onclick="$(this).closest('fieldset').nextAll('.toolbar').show();$(this).closest('fieldset').hide();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="toolbar">
<button type="submit" value="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
{if $address->address_id && !$address->is_default}<button type="button" onclick="$(this).closest('.toolbar').hide();$(this).closest('form').find('fieldset.delete').show();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	$popup.css('overflow', 'inherit');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', 'Sender Address');
		
		$popup.find('textarea[name=reply_signature]').autosize();
		
		// Placeholders
		
		var $placeholder_menu_trigger = $popup.find('button.cerb-popupmenu-trigger');
		var $placeholder_menu = $popup.find('ul.menu').hide();
		
		$placeholder_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				$(this).siblings('input:text,textarea').first().focus().insertAtCursor('{literal}{{{/literal}' + token + '{literal}}}{/literal}');
			}
		});
		
		$placeholder_menu_trigger
			.click(
				function(e) {
					$(this).siblings('ul.menu').toggle();
				}
			)
		;
		
		$popup.find('.placeholders')
			.atwho({
				{literal}at: '{%',{/literal}
				limit: 20,
				{literal}displayTpl: '<li>${content} <small style="margin-left:10px;">${name}</small></li>',{/literal}
				{literal}insertTpl: '${name}',{/literal}
				data: atwho_twig_commands,
				suffix: ''
			})
			.atwho({
				{literal}at: '|',{/literal}
				limit: 20,
				startWithSpace: false,
				searchKey: "content",
				{literal}displayTpl: '<li>${content} <small style="margin-left:10px;">${name}</small></li>',{/literal}
				{literal}insertTpl: '|${name}',{/literal}
				data: atwho_twig_modifiers,
				suffix: ''
			})
			;
	});
});
</script>