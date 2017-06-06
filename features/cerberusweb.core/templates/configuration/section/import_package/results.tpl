<div>
	<h3>The following records were created:</h3>
</div>

<div>

{if $records_created[CerberusContexts::CONTEXT_CUSTOM_FIELDSET]}
<fieldset class="peek">
	<legend>{'common.custom_fieldsets'|devblocks_translate|capitalize}</legend>
	
	<ul class="bubbles">
	{foreach from=$records_created[CerberusContexts::CONTEXT_CUSTOM_FIELDSET] item=record key=context}
	<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=handleSectionAction&section=custom_fieldsets&action=showCustomFieldsetPeek&id={$record.id}', null, false, '50%');">{$record.label}</a></li>
	{/foreach}
	</ul>
</fieldset>
{/if}

{if $records_created[CerberusContexts::CONTEXT_BOT]}
<fieldset class="peek">
	<legend>{'common.bots'|devblocks_translate|capitalize}</legend>
	
	<ul class="bubbles">
	{foreach from=$records_created[CerberusContexts::CONTEXT_BOT] item=record key=context}
	<li>
		<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=bot&context_id={$record.id}{/devblocks_url}?v={time()}">
		<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BOT}" data-context-id="{$record.id}">{$record.label}</a>
	</li>
	{/foreach}
	</ul>
</fieldset>
{/if}

{if $records_created[CerberusContexts::CONTEXT_PORTAL]}
<fieldset class="peek">
	<legend>{'common.portals'|devblocks_translate|capitalize}</legend>
	
	<ul class="bubbles">
	{foreach from=$records_created[CerberusContexts::CONTEXT_PORTAL] item=record key=context}
	<li><a href="{devblocks_url}c=config&a=portal&code={$record.code}{/devblocks_url}" rel="external" target="_blank">{$record.label}</a></li>
	{/foreach}
	</ul>
</fieldset>
{/if}

{if $records_created[CerberusContexts::CONTEXT_SAVED_SEARCH]}
<fieldset class="peek">
	<legend>{'common.saved_searches'|devblocks_translate|capitalize}</legend>
	
	<ul class="bubbles">
	{foreach from=$records_created[CerberusContexts::CONTEXT_SAVED_SEARCH] item=record key=context}
	<li>
		<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_SAVED_SEARCH}" data-context-id="{$record.id}">{$record.label}</a>
	</li>
	{/foreach}
	</ul>
</fieldset>
{/if}

{if $records_created[CerberusContexts::CONTEXT_CALENDAR]}
<fieldset class="peek">
	<legend>{'common.calendars'|devblocks_translate|capitalize}</legend>
	
	<ul class="bubbles">
	{foreach from=$records_created[CerberusContexts::CONTEXT_CALENDAR] item=record key=context}
	<li>
		<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-context-id="{$record.id}">{$record.label}</a>
	</li>
	{/foreach}
	</ul>
</fieldset>
{/if}

{if $records_created[CerberusContexts::CONTEXT_CLASSIFIER]}
<fieldset class="peek">
	<legend>{'common.classifiers'|devblocks_translate|capitalize}</legend>
	
	<ul class="bubbles">
	{foreach from=$records_created[CerberusContexts::CONTEXT_CLASSIFIER] item=record key=context}
	<li>
		<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_CLASSIFIER}" data-context-id="{$record.id}">{$record.label}</a>
	</li>
	{/foreach}
	</ul>
</fieldset>
{/if}

</div>
