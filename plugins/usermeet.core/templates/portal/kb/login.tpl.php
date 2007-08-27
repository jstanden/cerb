{include file="$path/portal/kb/header.tpl.php"}

<table id="kbNavMenu" style="" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td>
				 &nbsp; <a href="{devblocks_url}{/devblocks_url}"><b>Knowledgebase</b></a> 
			</td>
		</tr>
	</tbody>
</table>

<div style="margin:10px;">

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doLogin">
<table style="border: 1px solid rgb(0, 128, 255); text-align: left;" border="0" cellpadding="3" cellspacing="0">
  <tbody>
    <tr>
      <td style="background-color: rgb(237, 241, 255);"><span style="font-weight: bold;">Log in</span></td>
    </tr>
    <tr>
      <td>
      	Username:<br>
      	<input type="text" name="user" value="" size="35"><br>
      	Password:<br>
      	<input type="password" name="pass" value="" size="35"><br>
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

{include file="$path/portal/kb/footer.tpl.php"}