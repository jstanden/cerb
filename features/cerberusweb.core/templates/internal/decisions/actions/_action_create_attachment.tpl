<b>File name:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[file_name]" value="{$params.file_name}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="e.g. report.txt">
</div>

<b>File type:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[file_type]" value="{$params.file_type}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="e.g. text/plain">
</div>

<b>Content:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea rows="3" cols="60" name="{$namePrefix}[content]" style="width:100%;white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.content}</textarea>
</div>

<b>Encoding:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[content_encoding]">
		<option value="" {if empty($params.content_encoding)}selected="selected"{/if}>Text</option>
		<option value="base64" {if 'base64' == $params.content_encoding}selected="selected"{/if}>Base64</option>
	</select>
</div>

<b>Also create attachments in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<b>Save object metadata to a placeholder named:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_attachment_meta"}" required="required" spellcheck="false" size="32" placeholder="e.g. _attachment_meta">&#125;&#125;
</div>

{* Check for attachment list variables *}
{capture name="attachment_vars"}
{foreach from=$trigger->variables item=var key=var_key}
{if $var.type == "ctx_{CerberusContexts::CONTEXT_ATTACHMENT}"}
<option value="{$var_key}" {if $params.object_var==$var_key}selected="selected"{/if}>{$var.label}</option>
{/if}
{/foreach}
{/capture}

{if $smarty.capture.attachment_vars}
<b>Add object to list variable:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[object_var]">
		<option value=""></option>
		{$smarty.capture.attachment_vars nofilter}
	</select>
</div>
{/if}

{*
<script type="text/javascript">
var $action = $('#{$namePrefix}_{$nonce}');
</script>
*}