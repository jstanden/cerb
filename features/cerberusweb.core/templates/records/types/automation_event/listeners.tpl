<legend>Listeners:</legend>

<div style="display:flex;flex-flow:row wrap;gap:5px;">
    {if $listeners}
        {foreach from=$listeners item=listener}
			<div class="block" style="display:inline-block;padding:0.5em 1em;">
				<h3 style="text-decoration:underline;cursor:pointer;{if $listener->is_disabled}opacity:0.5;{/if}" data-context="cerb.contexts.automation.event.listener" data-context-id="{$listener->id}" data-edit="true">{$listener->name}</h3>
			</div>
        {/foreach}
    {/if}
	
	<div class="block" style="display:inline-block;padding:0.5em 1em;">
		<h3 style="text-decoration:none;cursor:pointer;{if $listener->is_disabled}opacity:0.5;{/if}" data-context="cerb.contexts.automation.event.listener" data-context-id="0" data-edit="event:{$event_name}"><span class="glyphicons glyphicons-plus"></span></h3>
	</div>
</div>

{$script_uid = uniqid('script')}

<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
    let $script = $('#{$script_uid}');
    let $listeners = $script.closest('fieldset');
    
    $listeners.find('[data-context]')
        .cerbPeekTrigger({
            'width': '80%'
        })
        .on('cerb-peek-saved cerb-peek-deleted', function(e) {
            e.stopPropagation();

            var formData = new FormData();
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'automation_event');
            formData.set('action', 'refreshListeners');
            formData.set('event_id', '{$event_id}');

            genericAjaxPost(formData, $listeners);
        })
    ;
});
</script>