<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-bottom:1px solid rgb(224,224,224);">
  <tbody>
    <tr>
      <td width="0%" nowrap="nowrap" valign="top">
      	<a href="javascript:;" onclick="toggleDiv('{$display_module->manifest->id}_body');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/icon_collapse.gif{/devblocks_url}" width="16" height="16" border="0" align="absmiddle"></a> 
      	<img src="{devblocks_url}c=resource&p={$display_module->manifest->plugin_id}&f={$display_module->manifest->params.icon}{/devblocks_url}" width="16" height="16" align="absmiddle">
      </td>
      <td width="100%" valign="top" nowrap="nowrap">
	      <h1 class="subtitle">&nbsp;{$display_module->manifest->params.title}</h1>
      </td>
    </tr>
  </tbody>
</table>
<div id="{$display_module->manifest->id}_body" style="display:block;">
<table border="0" cellpadding="0" cellspacing="0" width="100%"> <!--  class="displayModuleTable" -->
    <tr>
      <td colspan="2" valign="top" style="padding:2px;">
        <form action="javascript:;" id="{$display_module->manifest->id}_form">{$display_module->$callback($ticket)}</form>
      </td>
    </tr>
</table>
</div>