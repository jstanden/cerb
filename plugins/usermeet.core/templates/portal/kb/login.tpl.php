{include file="$tpl_path/portal/kb/header.tpl.php"}

<div style="margin:10px;">

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doLogin">
<table style="border: 1px solid rgb(0, 128, 255); text-align: left;" border="0" cellpadding="3" cellspacing="0">
  <tbody>
    <tr>
      <td style="background-color: rgb(237, 241, 255);"><span style="font-weight: bold;">Editor Log in</span></td>
    </tr>
    <tr>
      <td>
      	E-mail Address:<br>
      	<input type="text" name="editor_email" value="" size="35"><br>
      	Password:<br>
      	<input type="password" name="editor_pass" value="" size="35"><br>
      </td>
     </tr>
     <tr>
     	<td align="right">
     		<button type="submit">Submit</button>
     	</td>
     </tr>
  </tbody>
</table>
</form>

</div>

{include file="$tpl_path/portal/kb/footer.tpl.php"}