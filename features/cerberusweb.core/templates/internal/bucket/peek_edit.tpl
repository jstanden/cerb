{$form_id = "frmBucketPeek{uniqid()}"}

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

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="bucket">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($bucket) && !empty($bucket->id)}<input type="hidden" name="id" value="{$bucket->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="2" cellspacing="0" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="middle">
			<b>{'common.name'|devblocks_translate|capitalize}:</b> 
		</td>
		<td width="100%" valign="middle">
			<input type="text" name="name" value="{$bucket->name}" maxlength="64" autofocus="true" style="width:100%;">
		</td>
	</tr>
	
	<tr>
		<td align="right" valign="middle">
			<b>{'common.group'|devblocks_translate|capitalize}:</b> 
		</td>
		<td valign="middle">
			{if empty($bucket->id)}
			<select name="group_id">
				{foreach from=$groups item=group}
				{if $active_worker->is_superuser || $active_worker->isGroupManager($group->id)}
				<option value="{$group->id}">{$group->name}</option>
				{/if}
				{/foreach}
			</select>
			{else}
				<span>{$group->name}</span>
			{/if}
		</td>
	</tr>
	
</table>

<div style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Send worker replies as:</legend>
		
		<div>
			<b>{'common.email'|devblocks_translate|capitalize}:</b><br>
			<select name="reply_address_id">
				<option value="">- {'common.default'|devblocks_translate|lower} -</option>
				{foreach from=$replyto_addresses item=addy}
				<option value="{$addy->address_id}" {if $bucket->reply_address_id == $addy->address_id}selected="selected"{/if}>{$addy->email}</option>
				{/foreach}
			</select>
		</div>
		
		{$bucket_from_tester_id = "divSnippetBucketFromTester{uniqid()}"}
		<div>
			<b>{'common.name'|devblocks_translate|capitalize}:</b><br>
			<input type="text" name="reply_personal" value="{$bucket->reply_personal}" class="placeholders" placeholder="(leave blank for default)" size="65" style="width:100%;">
			<br>
			<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert placeholder &#x25be;</button>
			<button type="button" onclick="genericAjaxPost('{$form_id}','{$bucket_from_tester_id}','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.worker&snippet_field=reply_personal');">{'common.test'|devblocks_translate|capitalize}</button>
			<button type="button" onclick="genericAjaxPopup('help', 'c=internal&a=showSnippetHelpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');">Help</button>
			
			<ul class="menu" style="width:150px;">
			{tree keys=$placeholders}
			</ul>

			<div id="{$bucket_from_tester_id}"></div>
		</div>
	</fieldset>
	
	{$bucket_sig_tester_id = "divSnippetBucketSigTester{uniqid()}"}
	<fieldset class="peek">
		<legend>Bucket signature:</legend>
		
		<textarea name="reply_signature" rows="5" cols="76" style="width:100%;" class="placeholders" placeholder="(leave blank for default)" wrap="off">{$bucket->reply_signature}</textarea>
		<br>
		<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert placeholder &#x25be;</button>
		<button type="button" onclick="genericAjaxPost('{$form_id}','{$bucket_sig_tester_id}','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.worker&snippet_field=reply_signature');">{'common.test'|devblocks_translate|capitalize}</button>
		<button type="button" onclick="genericAjaxPopup('help', 'c=internal&a=showSnippetHelpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');">Help</button>
		
		<ul class="menu" style="width:150px;">
		{tree keys=$placeholders}
		</ul>
		
		<div id="{$bucket_sig_tester_id}"></div>
	</fieldset>
	
	<fieldset class="peek">
		<legend>Send HTML replies using template:</legend>
		<select name="reply_html_template_id">
			<option value="0"> - {'common.default'|devblocks_translate|lower} - </option>
			{foreach from=$html_templates item=html_template}
			<option value="{$html_template->id}" {if $bucket->reply_html_template_id==$html_template->id}selected="selected"{/if}>{$html_template->name}</option>
			{/foreach}
		</select>
	</fieldset>
</div>

{if !empty($bucket->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div style="margin:5px 10px;">
	Permanently delete this bucket and move the tickets?<br>
	<select name="delete_moveto">
		{foreach from=$buckets item=move_bucket key=move_bucket_id}
		{if $move_bucket_id == $bucket->id}
		{elseif $bucket->group_id == $move_bucket->group_id}
		<option value="{$move_bucket_id}">{$move_bucket->name}</option>
		{/if}
		{/foreach}
	</select>
	</div>

	{if !empty($bucket->id) && !$bucket->is_default}<button type="button" class="green delete">{'common.yes'|devblocks_translate|capitalize}</button>{/if}
	<button type="button" class="red" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($bucket->id) && !$bucket->is_default}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', '{'common.edit'|devblocks_translate|capitalize}: {'common.bucket'|devblocks_translate|capitalize|escape:'javascript' nofilter}');
		$popup.css('overflow', 'inherit');
		
		// Buttons
		
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
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