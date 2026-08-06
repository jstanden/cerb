<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">File name</label>
	<input type="text" name="{$namePrefix}[file_name]" value="{$params.file_name}" class="placeholders" spellcheck="false" placeholder="e.g. report.txt">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">File type</label>
	<input type="text" name="{$namePrefix}[file_type]" value="{$params.file_type}" class="placeholders" spellcheck="false" placeholder="e.g. text/plain">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Content</label>
	<textarea rows="3" name="{$namePrefix}[content]" style="white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.content}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Encoding</label>
	<select name="{$namePrefix}[content_encoding]">
		<option value="" {if empty($params.content_encoding)}selected="selected"{/if}>Text</option>
		<option value="base64" {if 'base64' == $params.content_encoding}selected="selected"{/if}>Base64</option>
	</select>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Also create attachments in simulator mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save object metadata to a placeholder named</label>
	<div>
		&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_attachment_meta"}" required="required" spellcheck="false" size="32" placeholder="e.g. _attachment_meta">&#125;&#125;
	</div>
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
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Add object to list variable</label>
	<select name="{$namePrefix}[object_var]">
		<option value=""></option>
		{$smarty.capture.attachment_vars nofilter}
	</select>
</div>
{/if}

{*
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
var $action = $('#{$namePrefix}_{$nonce}');
</script>
*}
