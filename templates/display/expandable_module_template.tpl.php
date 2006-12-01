<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-bottom:1px solid rgb(224,224,224);">
  <tbody>
    <tr>
      <td width="0%" nowrap="nowrap" valign="top">
      	<a href="javascript:;" onclick="toggleDiv('{$display_module->manifest->id}_body');"><img src="images/icon_collapse.gif" width="16" height="16" border="0" align="absmiddle"></a> 
      	<img src="{$display_module->manifest->params.icon}" width="16" height="16" align="absmiddle">
      </td>
      <td width="100%" valign="top" nowrap="nowrap">
	      <h1 class="subtitle">&nbsp;{$display_module->manifest->params.title}</h1>
      </td>
    </tr>
  </tbody>
</table>
<table border="0" cellpadding="0" cellspacing="0" width="100%" class="displayModuleTable" id="{$display_module->manifest->id}_body" style="display:block;">
    <tr>
      <td colspan="2" valign="top">
        {$display_module->$callback()}
      </td>
    </tr>
</table>