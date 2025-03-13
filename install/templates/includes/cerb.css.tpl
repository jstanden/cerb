<style>
	@CHARSET "UTF-8";

	HTML, BODY {
		background-color: rgb(32,32,32);
	}

	BODY, TD, TH { 
		font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
		font-size: 14px;
		color: rgb(220,220,220);
	}

	TH {
		font-weight: bold;
	}

	FORM {
		margin: 0;
	}

	INPUT::placeholder {
		color: rgb(117,117,117);
	}


	H3 {
		color: rgb(100,180,35);
	}

	A {
		color: rgb(220,220,220);
	}

	FORM INPUT, TEXTAREA, SELECT {
		font-size: 120%;
	}

	INPUT[type=text], INPUT[type=password] {
		border: 1px solid rgb(107,107,107);
		background-color: rgb(22,22,22);
		color: rgb(220,220,220);
		outline: none;
		padding: 3px;
	}

	BUTTON {
		height: 2.4em;
		vertical-align: middle;
		color: rgb(210,210,210);
		padding: 0.5em;
		font-weight: bold;
		margin: 0 1px 0 0;
		border: 0;
		cursor: pointer;

		background: linear-gradient(to bottom, rgb(70,70,70), rgb(40,40,40));
		border-radius: 0.5em;
	}

	BUTTON[type=submit] {
		font-size: 150%;
	}

	SELECT {
		background-color: rgb(22,22,22);
		background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20256%20448%22%20enable-background%3D%22new%200%200%20256%20448%22%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E.arrow%7Bfill%3Argb(128,128,128)%3B%7D%3C%2Fstyle%3E%3Cpath%20class%3D%22arrow%22%20d%3D%22M255.9%20168c0-4.2-1.6-7.9-4.8-11.2-3.2-3.2-6.9-4.8-11.2-4.8H16c-4.2%200-7.9%201.6-11.2%204.8S0%20163.8%200%20168c0%204.4%201.6%208.2%204.8%2011.4l112%20112c3.1%203.1%206.8%204.6%2011.2%204.6%204.4%200%208.2-1.5%2011.4-4.6l112-112c3-3.2%204.5-7%204.5-11.4z%22%2F%3E%3C%2Fsvg%3E%0A");
		background-position: right 10px center;
		background-repeat: no-repeat;
		background-size: auto 50%;
		border: 1px solid rgb(107,107,107);
		border-radius:2px;
		color: rgb(220,220,220);
		padding: 2px 30px 2px 4px;

		outline: none;
		-moz-appearance: none;
		-webkit-appearance: none;
		appearance: none;
	}

	UL {
		margin: 5px 0;
		padding: 0 0 0 20px;
	}

	.progress_complete {
		background: orange;
	}

	.progress_incomplete {
		background: rgb(100,100,100);
	}

	.good {
		font-weight: bold;
		color: rgb(40,200,40);
	}

	.bad {
		font-weight: bold;
		color:rgb(230,60,60);
	}

	.warning {
		font-weight: bold;
		color: rgb(255,128,0);
	}

	.cerb-license-box {
		width:90%;
		height:400px;
		border:1px solid rgb(70,70,70);
		overflow:auto;
		margin:5px;
		padding:5px;
	}

	DIV.error {
		color:rgb(240,100,100);
		font-weight:bold;
		padding:10px;
		margin:5px;
		background-color:rgb(15,15,15);
		border-radius:10px;
	}
</style>