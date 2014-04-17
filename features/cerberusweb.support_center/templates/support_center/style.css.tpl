BODY, TD {
	font-family: Arial, Helvetica, sans-serif;
	font-size:12px;
	color: rgb(60,60,60);
}

FORM {
	margin:0px;
}

A {
	color:rgb(50,50,50);
}

H1 {
	font-size:20px;
	font-weight:bold;
	color: rgb(0, 120, 0);
	margin-top:0px;
	margin-bottom:3px;
}

H2 {
	font-size:14px;
	color: rgb(60,60,60);
	margin-top:0px;
	margin-bottom:3px;
}

BUTTON {
	background-color:rgb(230,230,230);
	background: linear-gradient(top, rgb(240,240,240), rgb(210,210,210));
	background: -webkit-gradient(linear, left top, left bottom, from(rgb(240,240,240)), to(rgb(210,210,210)));
	background: -moz-linear-gradient(top, rgb(240,240,240), rgb(210,210,210));
	background: -o-linear-gradient(top, rgb(240,240,240), rgb(210,210,210));
	background: -ms-linear-gradient(top, rgb(240,240,240), rgb(210,210,210));
	cursor:pointer;
	
	color:rgb(50,50,50);
	border:0;
	
	margin-right:1px;
	vertical-align:middle;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
	
	padding:5px;
	margin:0px;
}

BUTTON:hover {
	background:none;
	background-color:rgb(160,198,254);
	background: linear-gradient(top, rgb(238,242,245), rgb(160,198,254));
	background: -webkit-gradient(linear, left top, left bottom, from(rgb(238,242,245)), to(rgb(160,198,254)));
	background: -moz-linear-gradient(top, rgb(238,242,245), rgb(160,198,254));
	background: -o-linear-gradient(top, rgb(238,242,245), rgb(160,198,254));
	background: -ms-linear-gradient(top, rgb(238,242,245), rgb(160,198,254));
	cursor:pointer;
}

INPUT[type=text], INPUT[type=password], SELECT, TEXTAREA {
	border:1px solid rgb(150,150,150);
	padding:2px;
}

INPUT[type=text]:focus, INPUT[type=password]:focus, SELECT:focus, TEXTAREA:focus {
	border:1px solid rgb(121,183,231);
}

INPUT[type=text]:focus, INPUT[type=password]:focus {
	/*background-color:rgb(245,245,245);*/
}

FIELDSET {
	border:1px solid rgb(230,230,230);
	margin-bottom:10px;
	
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
}

FIELDSET LEGEND {
	color:rgb(0,120,0);
	font-size:120%;
	font-weight:bold;
}

FIELDSET LEGEND A {
	color:rgb(0,120,0);
}

FIELDSET.minimal {
	border: 0;
	border-top: 1px solid rgb(230,230,230);
}

DIV.header {
}

DIV.header H1 {
	margin-bottom:0px;
}

UL.menu {
	padding:0px;
	margin:0px;
	list-style:none;
}

UL.menu LI {
	padding:8px 8px;
	margin:0px 2px;
	float:left;
	background-color:rgb(240,240,240);
	-moz-border-radius: 5px 5px 0px 0px;
	-webkit-border-radius: 5px 5px 0px 0px;
	border-radius: 5px 5px 0px 0px;
}

UL.menu LI:hover {
	background-color:rgb(220,220,220);
}

UL.menu LI A {
	color:rgb(80,80,80);
	padding:5px 0px 5px 0px;
	text-decoration:none;
	font-weight:normal;
}

UL.menu LI.selected {
	background-color:rgb(69,85,96);
}

UL.menu LI.selected A {
	color:rgb(255,255,255);
	font-weight:bold;
	text-decoration:none;
}

/* Sidebar */

TABLE.sidebar {
	width:220px;
	border-radius: 5px;
}

TABLE.sidebar TH {
	background-color:rgb(140,140,140);
	font-size:10pt;
	font-weight:bold;
	line-height: 22px;
	padding-left: 6px;
	text-align: left;
	color: rgb(255,255,255);
	border-radius: 5px 5px 0px 0px;
}

TABLE.sidebar TD {
	background-color: rgb(240,240,240);
	padding: 5px;
}

TABLE.sidebar TD A {
}

TABLE.sidebar TD INPUT {
	border:1px solid rgb(200,200,200);
	margin-bottom:1px;
}

/* Worklists */

TABLE.worklistBody TH {
	background-color: rgb(200,200,200);
	background: linear-gradient(top, rgb(240,240,240), rgb(200,200,200));
	background: -webkit-gradient(linear, left top, left bottom, from(rgb(240,240,240)), to(rgb(200,200,200)));
	background: -moz-linear-gradient(top, rgb(240,240,240), rgb(200,200,200));
	background: -o-linear-gradient(top, rgb(240,240,240), rgb(200,200,200));
	background: -ms-linear-gradient(top, rgb(240,240,240), rgb(200,200,200));
	padding:2px 0px 2px 5px;
	border-right:1px solid rgb(175,175,175);
	text-align: left;
	cursor:pointer;
}

TABLE.worklistBody TH:hover {
	background-color: rgb(150,150,150);
	background: linear-gradient(top, rgb(200,200,200), rgb(150,150,150));
	background: -webkit-gradient(linear, left top, left bottom, from(rgb(200,200,200)), to(rgb(150,150,150)));
	background: -moz-linear-gradient(top, rgb(200,200,200), rgb(150,150,150));
	background: -o-linear-gradient(top, rgb(200,200,200), rgb(150,150,150));
	background: -ms-linear-gradient(top, rgb(200,200,200), rgb(150,150,150));
	border-right:1px solid rgb(175,175,175);
}

TABLE.worklistBody TH A {
	font-size: 12px;
	vertical-align:middle;
	font-weight: bold;
	text-decoration:none;
	color: rgb(80,80,80);
}

TABLE.worklistBody TH:hover A {
	text-decoration:none;
	color:white;
}

TABLE.worklistBody TD A.record-link {
	font-weight: bold;
}

#footer {
	padding-bottom:10px;
	text-align:center;
}

#tagline {
	padding-top:5px;
	width:98%;
	padding:5px;
	text-align:right;
}

.tableRowBg {
	background-color: rgb(254, 254, 254);
}

.tableRowAltBg {
	background-color: rgb(244, 244, 244);
}

.hover {
	background-color: rgb(255, 255, 206);
}

#content {
	padding-top: 5px;
}

/* Modules */
#account {
	border:1px solid rgb(204,204,204);
	padding:5px;
}

#home {
	padding:5px;
}

#account_sidebar UL LI {
	padding: 3px;
}

#history DIV.message {
	margin:5px;
	margin-bottom:10px;
	border:1px solid rgb(230,230,230);
	padding:5px;
	border-radius: 5px;
}

#history DIV.message SPAN.header {
}

#history DIV.outbound_message {
}

#history DIV.outbound_message SPAN.header {
}

#history DIV.inbound_message {
}

#history DIV.reply {
	margin:10px;
}

#history DIV.reply TEXTAREA {
}

#history PRE.email {
	white-space:pre-wrap;
	white-space:-moz-pre-wrap;
	white-space:-pre-wrap;
	white-space:-o-pre-wrap;
	word-wrap:break-word;
	_white-space:pre;
}

/* KB */

#kb h1.title {
	font-size: 200%;
	color: rgb(50,50,50);
	font-weight: bold;
	text-align: left;
	border: none;
	margin:0;
}

#kb div.content {
	margin: 10px 5px 10px 5px;
}

#kb div.content { 
	color: rgb(50,50,50);
	font-family: Arial, Helvetica, Verdana, sans-serif;
	font-size: 100%;
	line-height: 140%;
}

#kb div.content h1, #kb div.content h2, #kb div.content h3, #kb div.content h4, #kb div.content h5, #kb div.content h6 { 
	font-weight: bold;
	color: rgb(0,120,0);
	margin:20px 0px;
}

#kb div.content h1 {
	font-size: 190%;
	color: rgb(0,120,0);
	border-bottom: 1px solid rgb(180,180,180);
	padding-bottom: 5px;
	margin-bottom: 5px;
}

#kb div.content h2 {
	font-size: 170%;
	margin-bottom: 5px;
	color: rgb(50,50,50);
}

#kb div.content h3 {
	font-size: 145%;
	color: rgb(74,110,158);
	border-bottom: 1px solid rgb(180,180,180);
	padding-bottom: 5px;
}

#kb div.content h4 {
	font-size: 130%;
	color: rgb(50,50,50);
}

#kb div.content h5 {
	font-size: 110%;
	font-style: italic;
	color: rgb(50,50,50);
}

#kb div.content h6 {
	font-size: 100%;
	font-style: italic;
	color: rgb(50,50,50);
}

#kb div.content pre {
	border-top: 1px solid rgb(200,200,200);
	border-right: 1px solid rgb(200,200,200);
	border-left: 3px solid rgb(150,150,150);
	border-bottom: 3px solid rgb(150,150,150);
	background-color: rgb(240,240,240);
	color: #1111111;
	padding: 0.5em;
}

#kb div.content code {
	background-color:rgb(240,240,240);
	color:rgb(0,0,0);
	padding:0px 3px;
	font-weight:bold;
}

#kb div.content blockquote {
	font-style:italic;
	color:rgb(50,50,50);
	padding:0px 3px;
	margin-left:20px;
	border-left:solid 5px rgb(240,240,240);
	padding-left:5px;
}

#kb div.content ul li {
	margin: 5px 0px 10px 0px;
}

#kb div.content ol li {
	margin: 5px 0px 10px 0px;
}

#kb div.content li img {
	margin-top: 10px;
}

/* Labels */

LABEL.error {
	background-color:rgb(255,235,235);
	color:rgb(180,0,0);
	font-weight:bold;
}

DIV.error {
	border:1px solid rgb(180,0,0);
	background-color:rgb(255,235,235);
	color:rgb(180,0,0);
	font-weight:bold;
	margin:10px;
	padding:5px;
}

DIV.success {
	border:1px solid rgb(0,180,0);
	background-color:rgb(235,255,235);
	color:rgb(0,180,0);
	font-weight:bold;
	margin:10px;
	padding:5px;
}