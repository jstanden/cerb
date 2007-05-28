<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1"
 http-equiv="content-type">
  <title>Discussions</title>
  <style type="text/css">
{literal}
body { background-image: url({/literal}{devblocks_url}c=resource&p=usermeet.forums&f=images/body_bg.jpg{/devblocks_url}{literal});
background-repeat: repeat-x;
}
form { margin:0px; 
}
#search_box { border: 1px solid rgb(189, 76, 13);
background-image: url({/literal}{devblocks_url}c=resource&p=usermeet.forums&f=images/bg_search.jpg{/devblocks_url}{literal});
background-repeat: repeat-x;
background-color: rgb(255, 255, 255);
font-weight: normal;
color: rgb(191, 75, 34);
font-size: 18px;
}
#recent_box { border: 1px solid rgb(43, 148, 6);
background-color: rgb(255, 255, 255);
background-image: url({/literal}{devblocks_url}c=resource&p=usermeet.forums&f=images/bg_recent.jpg{/devblocks_url}{literal});
background-repeat: repeat-x;
font-weight: normal;
color: rgb(43, 148, 6);
font-size: 18px;
}
body, td { font-family: Arial,Helvetica,sans-serif;
font-size: 12px;
}
#search_input { 
border: 1px solid rgb(189, 76, 13);
font-size: 18px;
}
table.message { 
border-top: 1px solid rgb(200,200,200);
background-color: rgb(255, 255, 255);
background-image: url({/literal}{devblocks_url}c=resource&p=usermeet.forums&f=images/bg_message.jpg{/devblocks_url}{literal});
background-repeat: repeat-x;
}
table.message td { color: rgb(51, 51, 51);
}
table.message a { color: rgb(37, 125, 255);
font-weight: bold;
font-size: 16px;
}
table.message table.count { border: 1px solid rgb(127, 127, 127);
background-color: rgb(255, 255, 255);
background-image: url({/literal}{devblocks_url}c=resource&p=usermeet.forums&f=images/bg_count.jpg{/devblocks_url}{literal});
background-repeat: repeat-x;
color: rgb(102, 102, 102);
text-align: center;
width:85%;
}
table.message table.count b { font-weight: bold;
font-size: 24px;
color: rgb(237, 72, 20);
}
{/literal}
  </style>
</head>
<body>
<img style="width: 201px; height: 78px;" alt="Logo" src="{devblocks_url}c=resource&p=usermeet.forums&f=images/logo.jpg{/devblocks_url}"><br>
&nbsp;
<br>

<table style="text-align: left; width: 100%;" id="search_box" border="0" cellpadding="5" cellspacing="0">
  <tbody>
    <tr>
      <td style="font-weight: bold;"><big><big>Search the Discussion Logs</big></big></td>
    </tr>
  </tbody>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST">
  <div style="padding: 10px;">
  	<input id="search_input" name="q" size="40" value="{$q}">
  	<select name="range">
  		<option value="7">Past week
  		<option value="30">Past month
  		<option value="365">Past year
  		<option value="0">All time
  	</select>
  	<button>Search</button>
  </div>
</form>

<table style="text-align: left; width: 100%;" id="recent_box" border="0" cellpadding="5" cellspacing="0">
  <tbody>
    <tr>
      <td style="font-weight: bold;"><big><big>Recent Messages</big></big></td>
    </tr>
  </tbody>
</table>
<label><input name="only" value="1" type="checkbox">Only show replies</label><br>
&nbsp;
<br>

{section name=messages loop=5 start=0}
<table style="text-align: left; width: 100%;" border="0" cellpadding="5" cellspacing="0" class="message">
    <tr>
      <td nowrap="nowrap" valign="top" width="0%" align="center">
      <table class="count">
      	<tr><td nowrap="nowrap"><b>50</b><br>comments</td></tr>
	  </table>
      </td>
      <td style="width: 100%;"><a href="#">Looking for information on writing a couple custom plugins...</a><br>
		45 minutes ago <span style="color: rgb(85, 148, 6); font-weight: bold;">Jeff Standen</span> wrote:<br>
		<span style="background-color:rgb(240,240,240);">
		<span style="font-weight: bold; font-size: 24px;">&ldquo;</span>
		A good place to look would be the Devblocks wiki, it has a lot of examples on the extension points that... 
		<span style="font-weight: bold; font-size: 24px;">&rdquo;</span>
		</span>
		<br>
		<a href="#" style="font-size:10px;color:rgb(85, 148, 6);">Cerberus Helpdesk</a> 
	  </td>
    </tr>
</table>
{/section}

</body>
</html>
