{*
	The `mounts:`/`tools:` entries no chooser can represent.

	Carrying them through untouched on save is right, but it was ALSO the only thing happening to them: with
	nothing rendered there was no way to delete one, so "untouched" meant "permanent". Each row's hidden input
	is what keeps it -- the remove button drops the row, and the entry is simply not in the next POST.

	Params:
	  raw   [{key, reason}] from Config::describeScopeForForm()
	  name  the hidden input's field name (already `[]`-suffixed)
*}
{if $raw}
	<div class="cerb-u-mt-2">
		{foreach from=$raw item=raw_entry}
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2" data-cerb-agent-raw>
				<input type="hidden" name="{$name}" value="{$raw_entry.key}">
				<code>{$raw_entry.key}</code>
				<span class="cerb-u-text-muted cerb-u-fs-n1">{if $raw_entry.reason == 'reserved'}written by hand; not editable here{else}no record with this name{/if}</span>
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-agent-remove title="{'common.remove'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-circle-remove"></span></button>
			</div>
		{/foreach}
	</div>
{/if}
