<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1"
 http-equiv="content-type">
  <title>FAQ</title>
  <style type="text/css">
  {literal}
body, td { font-family: Arial,Helvetica,sans-serif;
}
body { background-image: url({/literal}{devblocks_url}c=resource&p=cerberusweb.faq&f=images/back.jpg{/devblocks_url}{literal});
background-repeat: repeat-x;
}
.title { font-family: Arial,Helvetica,sans-serif;
font-size: 36px;
font-weight: bold;
color: rgb(255, 255, 255);
}
.result { border: 1px solid rgb(105, 161, 13);
background-color: rgb(255, 255, 255);
background-image: url({/literal}{devblocks_url}c=resource&p=cerberusweb.faq&f=images/bg_message.jpg{/devblocks_url}{literal});
background-repeat: repeat-x;
font-weight: normal;
width: 100%;
color: rgb(102, 102, 102);
}
.result a { color: rgb(105, 161, 13);
font-weight: normal;
}
.result h5 { font-weight: bold;
font-size: 30px;
margin:0px;
color: rgb(102, 102, 102);
}
.search {
border: 1px solid rgb(82, 120, 255);
color: rgb(80,80,80);
background-color: rgb(227, 235, 255);
}
.search input { border: 1px solid rgb(82, 120, 255);
font-weight: normal;
font-size: 24px;
width: 100%;
}
.search h4 { margin-bottom: 5px;
font-weight: bold;
color: rgb(253, 71, 3);
font-size: 18px;
}
form { margin: 0px;
}
{/literal}
  </style>
</head>
<body>
<!-- <span class="title">Cerberus Helpdesk Q&amp;A</span><br> -->
<img src="{devblocks_url}c=resource&p=cerberusweb.faq&f=images/logo.jpg{/devblocks_url}" style="margin-bottom:10px;" alt="Logo"><br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<table style="text-align: left; width: 550px;" class="search" border="0" cellpadding="5" cellspacing="5">
  <tbody>
    <tr>
      <td colspan="2">
      	<h4>Search our frequently asked questions:</h4>	
		<input name="q" class="question" value="{$q}"><br>
		<i>(Enter keywords)</i>
      </td>
    </tr>
  </tbody>
</table>
</form>
<br>

{if empty($q)}
	{foreach from=$faqs item=faq key=faq_id}
	<table class="result" cellpadding="5">
	  <tbody>
	    <tr>
	      <td nowrap="nowrap" width="0%">
	      <h5>Q:</h5>
	      </td>
	      <td width="100%">
	      	<a style="font-weight: bold;" href="#">{$faq->question}</a><br>
			You have to try real hard, kemosabi.
		  </td>
	    </tr>
	  </tbody>
	</table>
	<br>
	{/foreach}
{else}
	{$results_count} result(s) found:<br>
	<br>
	
	{foreach from=$results item=result key=faq_id}
	<table class="result" cellpadding="5">
	  <tbody>
	    <tr>
	      <td nowrap="nowrap" width="0%">
	      	<h5>Q:</h5>
	      </td>
	      <td width="100%">
	      	<a style="font-weight: bold;" href="#">{$result.f_question}</a><br>
			You have to try real hard, kemosabi.
		  </td>
	    </tr>
	  </tbody>
	</table>
	<br>
	{/foreach}
{/if}

</body>
</html>
