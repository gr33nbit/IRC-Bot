<?php

	set_time_limit(0);

	require("classes/commands.class.php");
	require("classes/misc.class.php");
	require("classes/msg.class.php");

	
	$miscFunctions = new miscFunctions();
	$msg = new msg($miscFunctions);
	$commands = new commands($msg, $miscFunctions);

	$msg->commands = $commands;
	//irc settings
	//main nick first and then others are used if nicks are already in use
	//goes down the line untill one works
	$nick = array('evasiveBot', 'evasive_Bot', 'evasiveBOT', 'evasiveB0T', 'evasive807');
	$nickNo = 0;

	//users able to execute protected commands
	$whitelist = array();
	$miscFunctions->getWhitelist();

	$MOTD = true;

	//determins if message is normal message or a private message
	$messageType = '';
	//username of message sender
	$username = '';
	$channel = '#gr33n-bot';
	$commandPrefix = '!';

	$channelsJoined = array();
	$channelNicks = array();

	$restart = false;

	

	//gets ip of the irc server
	$serverIp = gethostbyname('irc.freenode.net');
	//socket connections and configuration
	$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	
	//make sure socket is connected
	if(socket_connect($sock, $serverIp, 6667)){

		$messageCount = 1;

		//message Storage
		$buff = '';

		$loop = TRUE;

		//main loop
		while($loop){

			switch($messageCount){

				case 1:
				case 2:
				case 4:

					$buff = $msg->getMsg();

					break;

				case 3:

					$buff = $msg->getMsg();

					$nickMsg = 'NICK '. $nick[0];
					$userMsg = 'USER ' . $nick[0].' 0 * :Fun Bot';

					$msg->sendMsg($nickMsg);
					$msg->sendMsg($userMsg);

					break;

				
				default:

					$buff = $msg->getMsg();

					break;
			}

			$buff = '';
			$messageCount++;
		}

		if($restart){

			exec('/opt/lampp/bin/php restart.php > /dev/null 2>/dev/null &');
		}
	}

	

	

	

	
?>