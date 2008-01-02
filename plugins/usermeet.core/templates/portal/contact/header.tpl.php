<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1"
 http-equiv="content-type">
  <title>{$page_title}</title>
  <style type="text/css">
  {literal}
body, td { font-family: Arial,Helvetica,sans-serif;
}
body { background-image: url({/literal}{devblocks_url}c=resource&p=usermeet.core&f=images/back.jpg{/devblocks_url}{literal});
background-repeat: repeat-x;
}
.title { font-family: Arial,Helvetica,sans-serif;
font-size: 36px;
font-weight: bold;
color: rgb(255, 255, 255);
}
.result { border: 1px solid rgb(105, 161, 13);
background-color: rgb(255, 255, 255);
background-image: url({/literal}{devblocks_url}c=resource&p=usermeet.core&f=images/bg_message.jpg{/devblocks_url}{literal});
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
.search h3 { margin-bottom: 5px;
font-weight: bold;
color: rgb(80,150,0);
font-size: 22px;
}
.search h4 { margin-bottom: 5px;
font-weight: bold;
color: rgb(253, 71, 3);
font-size: 18px;
}
DIV.error {
border:1px solid rgb(180,0,0);
background-color:rgb(255,235,235);
color:rgb(180,0,0);
font-weight:bold;
margin:10px;
padding:5px;
}
form { margin: 0px;
}
{/literal}
  </style>
</head>
<body>

<!-- <span class="title">Cerberus Helpdesk Q&amp;A</span><br> -->
{if empty($logo_url)}
<img src="{devblocks_url}c=resource&p=usermeet.core&f=images/logo.jpg{/devblocks_url}" style="margin-bottom:10px;" alt="Logo"><br>
{else}
<img src="{$logo_url}" style="margin-bottom:10px;" alt="Logo"><br>
{/if}
