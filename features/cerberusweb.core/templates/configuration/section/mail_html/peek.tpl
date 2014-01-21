<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmMailHtmlTemplatePeek">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="html_template">
<input type="hidden" name="action" value="savePeek">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="true">
			</td>
		</tr>
		
		{*
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.owner'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				<select name="owner">
					<option value="{CerberusContexts::CONTEXT_APPLICATION}:0"  context="{CerberusContexts::CONTEXT_APPLICATION}" {if $model->owner_context==CerberusContexts::CONTEXT_APPLICATION}selected="selected"{/if}>Application: Cerb</option>

					{foreach from=$roles item=role key=role_id}
						<option value="{CerberusContexts::CONTEXT_ROLE}:{$role_id}"  context="{CerberusContexts::CONTEXT_ROLE}" {if $model->owner_context==CerberusContexts::CONTEXT_ROLE && $role_id==$model->owner_context_id}selected="selected"{/if}>Role: {$role->name}</option>
					{/foreach}
					
					{foreach from=$groups item=group key=group_id}
						<option value="{CerberusContexts::CONTEXT_GROUP}:{$group_id}"  context="{CerberusContexts::CONTEXT_GROUP}" {if $model->owner_context==CerberusContexts::CONTEXT_GROUP && $group_id==$model->owner_context_id}selected="selected"{/if}>Group: {$group->name}</option>
					{/foreach}
					
					{foreach from=$workers item=worker key=worker_id}
						{$is_selected = $model->owner_context==CerberusContexts::CONTEXT_WORKER && $worker_id==$model->owner_context_id}
						{if $is_selected || !$worker->is_disabled}
						<option value="{CerberusContexts::CONTEXT_WORKER}:{$worker_id}"  context="{CerberusContexts::CONTEXT_WORKER}" {if $is_selected}selected="selected"{/if}>Worker: {$worker->getName()}</option>
						{/if}
					{/foreach}
				</select>
			</td>
		</tr>
		*}
		
	</table>
	
<textarea name="content" style="width:98%;height:200px;border:1px solid rgb(180,180,180);padding:2px;" spellcheck="false">
{if $model->content}
{$model->content}
{else}
{literal}{{message_body}}{/literal}

&lt;style type="text/css"&gt;
body {
  font-family: Arial, Verdana, sans-serif;
  font-size: 10pt;
}

a { 
  color: black;
}

blockquote {
  color: rgb(0, 128, 255);
  font-style: italic;
  margin-left: 0px;
  border-left: 1px solid rgb(0, 128, 255);
  padding-left: 5px;
}

blockquote a {
  color: rgb(0, 128, 255);
}
&lt;/style&gt;
{/if}
</textarea>
	
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context='cerberusweb.contexts.mail.html_template' context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this HTML template?
	</div>
	
	<button type="button" class="delete" onclick="var $frm=$(this).closest('form');$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('button.submit').click();"><span class="cerb-sprite2 sprite-tick-circle"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="cerb-sprite2 sprite-minus-circle"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frmMailHtmlTemplatePeek','{$view_id}', false, 'mail_html_template_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
	{if !empty($model->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=html_template&id={$model->id}-{$model->name|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		var $this = $(this);
		
		$this.dialog('option','title',"{'HTML Template'}");

		var $content = $this.find('textarea[name=content]');
		
		try {
			var markitupHTMLSettings = $.extend(true, { }, markitupHTMLDefaults);
			
			delete markitupHTMLSettings.previewParserPath;
			delete markitupHTMLSettings.previewTemplatePath;
			
			markitupHTMLSettings.previewParser = function(content) {
				// Replace 'message_body' with sample text
				content = content.replace('{literal}{{message_body}}{/literal}', '<blockquote>This text is quoted.</blockquote><p>This text contains <b>bold</b>, <i>italics</i>, <a href="javascript:;">links</a>, and <code>code formatting</code>.</p><p><ul><li>These are unordered</li><li>list items</li></ul></p><p>This is an inline image:</p><p><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/gravatar_nouser.jpg{/devblocks_url}"></p>');
				return content;
			};
			
			$content.markItUp(markitupHTMLSettings);
			
			var $preview = $this.find('.markItUpHeader a[title="Preview"]');

			// Default with the preview panel open
			$preview.trigger('mouseup');
			
		} catch(e) {
			if(window.console)
				console.log(e);
		}
	} );
</script>
