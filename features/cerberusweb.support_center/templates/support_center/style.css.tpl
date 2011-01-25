BODY, TD {
	font-family: Tahoma, Verdana, Arial;
	font-size:12px;
	color: rgb(60,60,60);
}

FORM {
	margin:0px;
}

H1 {
	font-size:20px;
	font-weight:bold;
	color: rgb(8,90,173);
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
	background-color: rgb(238, 238, 238);
	color: rgb(30, 30, 30);
	border: 1px solid rgb(150, 150, 150);
	margin-right:1px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
}

BUTTON:hover {
	background-color: rgb(160,198,254);
	border: 1px solid rgb(36,111,223);
	cursor: hand;
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
}

FIELDSET LEGEND {
	color:rgb(0,150,0);
	font-size:120%;
	font-weight:bold;
}

DIV.header {
	border-bottom:1px solid rgb(180,180,180);
	margin-bottom:5px;
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
	padding:5px 8px;
	margin:0px 2px;
	float:left;
	background-color:rgb(240,240,240);
	-moz-border-radius: 5px 5px 0px 0px;
	-webkit-border-radius: 5px 5px 0px 0px;
	border-radius: 5px 5px 0px 0px;
}

UL.menu LI A {
	color:rgb(80,80,80);
	padding:5px 0px 5px 0px;
	text-decoration:underline;
	font-weight:normal;
}

UL.menu LI.selected {
	background-color:rgb(8,90,173);
}

UL.menu LI.selected A {
	color:rgb(255,255,255);
	font-weight:bold;
	text-decoration:none;
}

#content {
}

#content A {
	color:rgb(50,50,50);
}

TABLE.sidebar {
	width:220px;
	border: 1px solid rgb(8,90,173);
}

TABLE.sidebar TH {
	background-color: rgb(8,90,173);
	font-size:10pt;
	font-weight:bold;
	line-height: 22px;
	padding-left: 6px;
	text-align: left;
	color: rgb(255,255,255);
}

TABLE.sidebar TD {
	background-color: rgb(240,240,240);
	padding: 3px;
}

TABLE.sidebar TD A {
	color: rgb(7,39,115);
}

TABLE.sidebar TD INPUT {
	border:1px solid rgb(200,200,200);
	margin-bottom:1px;
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

/* Modules */
#account {
	border:1px solid rgb(204,204,204);
	padding:5px;
}

#home {
	padding:5px;
}

#history DIV.message {
	margin:5px;
	margin-bottom:10px;
	border:1px solid rgb(200,200,200);
	padding:5px;
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