<h1>Configuration</h1>
{if $smarty.const.DEMO_MODE}
<div style="color:red;padding:2px;font-weight:bold;">NOTE: This helpdesk is in Demo Mode and changes will not be saved.</div>
{/if}
<div id="tourConfigMenu"></div>
[ <a href="{devblocks_url}c=config&a=general{/devblocks_url}">general settings</a> ] 
[ <a href="{devblocks_url}c=config&a=workflow{/devblocks_url}">users &amp; groups</a> ] 
[ <a href="{devblocks_url}c=config&a=mail{/devblocks_url}">mail</a> ] 
[ <a href="{devblocks_url}c=config&a=sla{/devblocks_url}">service levels</a> ] 
[ <a href="{devblocks_url}c=config&a=fields{/devblocks_url}">ticket fields</a> ] 
[ <a href="{devblocks_url}c=config&a=fnr{/devblocks_url}">fetch &amp; retrieve</a> ] 
[ <a href="{devblocks_url}c=config&a=extensions{/devblocks_url}">plugins</a> ] 
[ <a href="{devblocks_url}c=config&a=jobs{/devblocks_url}">scheduler</a> ] 
[ <a href="{devblocks_url}c=config&a=licenses{/devblocks_url}">licenses</a> ] 
<br>

{if $install_dir_warning}
<br>
<div class="error">
	Warning: The 'install' directory still exists.  This is a potential security risk.  Please delete it.
</div>
{/if}

