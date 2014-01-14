<?php
set_time_limit(0);
date_default_timezone_set('America/Los_Angeles');

$address = '127.0.0.1';
$port = 110;

$clients = array();

$sock = socket_create(AF_INET, SOCK_STREAM, 0);

@socket_bind($sock, $address, $port) or die ("Could not bind to address.\n");

socket_listen($sock);

$client = socket_accept($sock);

socket_write($client, "+OK Mock POP3 ready.\r\n");

while(false != ($input = socket_read($client, 1024))) {
    if($input === NULL)
        break;

    echo $input;

    if(preg_match("#^CAPA#", $input, $matches)) {
        socket_write($client, "+OK I can do this stuff:\r\n");
        socket_write($client, "TOP\r\n");
        socket_write($client, "USER\r\n");
        socket_write($client, ".\r\n");

    } elseif(preg_match("#^USER (.*)#", $input, $matches)) {
        socket_write($client, "+OK Yeah, you look familiar.\r\n");

    } elseif(preg_match("#^PASS (.*)#", $input, $matches)) {
        socket_write($client, "+OK I would have accepted anything.\r\n");

    } elseif(preg_match("#^STAT#", $input, $matches)) {
        socket_write($client, "+OK 1 152\r\n");
        //socket_write($client, "+OK 2 304\r\n");

    } elseif(preg_match("#^NOOP#", $input, $matches)) {
        socket_write($client, "+OK\r\n");

    } elseif(preg_match("#^TOP (\d+) (\d+)#", $input, $matches)) {
        socket_write($client, "+OK 121 octets\r\n");
        socket_write($client, "From: customer@example.com\r\n");
        socket_write($client, "To: support@example.com\r\n");
        socket_write($client, "Subject: I need some help\r\n");
        socket_write($client, "Date: " . date('r') . "\r\n");
        socket_write($client, "\r\n");
        socket_write($client, ".\r\n");

    } elseif(preg_match("#^RETR (.*)#", $input, $matches)) {
        socket_write($client, "+OK 152 octets\r\n");
        socket_write($client, "From: customer@example.com\r\n");
        socket_write($client, "To: support@example.com");
        socket_write($client, "Subject: I need some help\r\n");
        //if($matches[1] == 3)
        //    sleep(2);
        socket_write($client, "Date: " . date('r') . "\r\n");
        socket_write($client, "\r\n");
        socket_write($client, "This is some message content.\r\n");
        socket_write($client, ".\r\n");
    
    } elseif(preg_match("#^LIST#", $input, $matches)) {
        socket_write($client, "+OK 1 message (152 octets)\r\n");
        //socket_write($client, "+OK 2 messages (304 octets)\r\n");
        socket_write($client, "1 152\r\n");
        //socket_write($client, "2 152\r\n");
        socket_write($client, ".\r\n");

    } elseif(preg_match("#^QUIT#", $input, $matches)) {
        socket_write($client, "+OK Later.\r\n");
        break;
    
    } else {
        socket_write($client, "-ERR I have no idea what you're asking me.\r\n");
    }
}

socket_close($client);

socket_close($sock);
