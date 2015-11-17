<script type="text/javascript">
$(function() {
	var $props = $('fieldset.properties');
	
	$props.find('a.cerb-peek-trigger').cerbPeekTrigger();
});
</script>

{$profile_scripts = Extension_ContextProfileScript::getExtensions(true, $page_context)}
{if !empty($profile_scripts)}
{foreach from=$profile_scripts item=renderer}
	{if method_exists($renderer,'renderScript')}
		{$renderer->renderScript($page_context, $page_context_id)}
	{/if}
{/foreach}
{/if}