<?php

	//irc settings
	//main nick first and then others are used if nicks are already in use
	//goes down the line untill one works
	$nick = array('evasiveBot', 'evasive_Bot', 'evasiveBOT', 'evasiveB0T', 'evasive807');
	$nickNo = 0;

	//users able to execute protected commands
	$whitelist = array();
	getWhitelist();

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

					$buff = getMsg();

					break;

				case 3:

					$buff = getMsg();

					$nickMsg = 'NICK '. $nick[0]. "\r\n";
					$userMsg = 'USER ' . $nick[0].' 0 * :Fun Bot' . "\r\n";

					sendMsg($nickMsg);
					sendMsg($userMsg);

					break;

				
				default:

					$buff = getMsg();

					break;
			}

			$buff = '';
			$messageCount++;
		}

		if($GLOBALS['restart']){

			exec('/opt/lampp/bin/php restart.php > /dev/null 2>/dev/null &');
		}
	}

	function getMsg(){

		$msg = '';

		//receive message .. if failed print error
		if(socket_recv($GLOBALS['sock'], $msg, 512, 0) === FALSE){

			echo socket_last_error().' - '.socket_strerror(socket_last_error());

			return false;

		} else{

			//no data was received
			if(strlen($msg) != 0){

				//there is a ping request
				if($msg[0] == 'P' && $msg[1] == 'I' && $msg[2] == "N" && $msg[3] == "G"){

					//make pong msg
					$msg = str_replace('PING', 'PONG', $msg);

					//send pong msg
					sendMsg($msg);

				} else{
					//echo $msg;
					parseMsg($msg);
					
				}
			}
		}
	}

	function sendMsg($msg, $channel = 0, $other = null){

		$msg .= "\r\n";

		//send a 
		if($other != null){

			$msg2 = $msg;
			$msg = 'PRIVMSG '. $other . ' :';
			$msg .= $msg2;

		//send to chanel
		} else if($channel == 1){

			$msg2 = $msg;
			$msg = 'PRIVMSG '. $GLOBALS['channel'] . ' :';
			$msg .= $msg2;

		//send to user
		} else if($channel == 2){

			$msg2 = $msg;
			$msg = 'PRIVMSG '. $GLOBALS['username'] . ' :';
			$msg .= $msg2;
		}

		if(socket_send($GLOBALS['sock'], $msg, strlen($msg), 0) !== false){

			return true;

		}

		return false;
	}

	//check if there are appropriate amount of command options
	function checkParameterNo($command, $max, $req = 0){

		$count = count($command);


	}

	function getOptions($msg, $optionNum){

		if($optionNum == '*'){

			return explode(' ', $msg);

		} else{

			return explode(' ', $msg, $optionNum);

		}
		

	}

	function checkWhitelist(){

		if(in_array($GLOBALS['username'], $GLOBALS['whitelist']) == TRUE){

			return TRUE;

		}

		return FALSE;

	}

	function getWhitelist(){

		$count = 0;

		if(($whitelistTxt = file_get_contents('whitelist.txt')) !== FALSE){

			$whitelistTxt = explode("!", $whitelistTxt);

			foreach($whitelistTxt as $nick){

				$GLOBALS['whitelist'][$count++] = $nick;

			}

		}		

	}

	function getUrl($url){

		$curl = curl_init($url);

		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_USERAGENT => 'IRC weather bot function',
			CURLOPT_FOLLOWLOCATION => TRUE
		));

		return curl_exec($curl);


	}

	function getWeather($options){

		$html = getUrl('http://www.wund.com/cgi-bin/findweather/getForecast?query='.$options[0].'+'.$options[1]);
	
		if(strpos($html, '<div id="main">') !== FALSE){

			$dom = new DOMDocument;
			$dom->loadHTML($html);

			$dom2 = $dom->getElementById('main');

			$dom3 = $dom->getElementById('other');

			$temp = trim(str_replace("\r\n", '', $dom2->nodeValue));
			$temp = str_replace("\n", '', $temp);
			$humidity = $dom3->nodeValue;

			$humidity = strstr($humidity, 'Humidity:');
			$humidity = strstr($humidity, '%', 1);
			
			$humidity = explode(' ', $humidity);
			$humidity = $humidity[2];

			$humidity = trim($humidity);
			$temp = trim($temp);

			$temp = explode(' ', $temp, 2);
			$temp = $temp[0] . ', Conditions -' . $temp[1];
			return array('temp' => $temp, 'hum' => $humidity);
		}

		return false;
	}

	function parseMsg($msg){

		echo $msg;

		//actuall message from user
		$message = array();

		//holds the meta data PDF_add_bookmark()qut message
		$msgMeta = array();

		//split the message info from the main message
		$msg[0] = NULL;
		$msg = explode(':', $msg, 2);

		if(isset($msg[1])){

			if($GLOBALS['MOTD'] == TRUE){

				if((strpos($msg[1], '376') || strpos($msg[0], '376')) !== FALSE){

					$GLOBALS['MOTD'] = false;

					sendMsg('JOIN '. $GLOBALS['channel']);

				} else if(strpos($msg[0], '433') !== FALSE){

					sendMsg('NICK '. $GLOBALS['nick'][++$GLOBALS['nickNo']]);

				}

			} else{

				//split all of the options.
				$msg[0] = trim($msg[0]);
				
				$msgMeta = explode(' ', $msg[0]);

				//var_dump($msgMeta);

				if(isset($msg[1])){

					$message = $msg[1];

					if($msgMeta[1] == 'PRIVMSG'){

						//get username message came from
						$GLOBALS['username'] = explode('!', $msgMeta[0]);
						$GLOBALS['username'] = $GLOBALS['username'][0];


						//message is from channel
						if($msgMeta[2][0] == '#'){

							$GLOBALS['messageType'] = 'channel';
							
							//change channel to respond to
							$GLOBALS['channel'] = $msgMeta[2];

						//private message from user
						}else{

							$GLOBALS['messageType'] = 'private';

						}

						$position = 0;

						//is a command
						if($message[0] == $GLOBALS['commandPrefix']){

							//actions for a command
							commands($message);

						//has a link
						}else if($position = ((strpos($message, 'http://') !== false)||((strpos($message, "https://") !== false)))){

							$url = '';

							while($message[$position] != ' ' && $position != strlen($message)){

								$url .= $message[$position++];
							}

							$html = getUrl($url);
							$title = '';

							if(($position = strpos($html, '<title>') + strlen('<title>')) !== FALSE){

								while($html[$position] != '<' && $position <= strlen($html)){

									$title .= $html[$position++];

								}
							}

							sendMsg('Title - \''.$title.'\'' , 1);
						}

					} 
				} else{


				}
			}	
		}
	}

	function commands($command){

		//get command and options if applicable
		$command[0] = null;
		$command = explode(' ', $command, 2);

		$command[0] = trim($command[0]);
		$command[1] = trim($command[1]);

		$options = array();

		switch($command[0]){

			case 'shutdown':

				if(checkWhitelist()){

					sendMsg('see ya!', 1);
					sendMsg('QUIT');

					socket_close($GLOBALS['sock']);

					$GLOBALS['loop'] = FALSE;
				} else{

					sendMsg('You are not authorized', 2);
				}
				break;


			//LEAVE CURRENT CHANNEL
			case 'leave':

				if(checkWhitelist()){

					sendMsg('PART '.$GLOBALS['channel'] . ' :Farewell');

				} else{

					sendMsg('You are not authorized', 2);
				}			

				break;

			//JOIN CHANNEL
			case 'join':

				$options = getOptions($command[1], 1);

				sendMsg('JOIN '. $options[0]);

				break;


			case 'command':

				if(checkWhitelist()){

					$options = getOptions($command[1], 2);

					//CHANGE THE COMMAND PREFIX
					if($options[0] == 'prefix'){

						sendMsg('Command Prefix changed from: \'' . $GLOBALS['commandPrefix'] . '\', to: \''. $options[1][0].'\'' ,1);

						$GLOBALS['commandPrefix'] = $options[1][0];
					}
					
				} else{

					sendMsg('You are not authorized', 2);
				}

				break;

			case 'msg':

				if(checkWhitelist()){

					$options = getOptions($command[1], 2);

					sendMsg($options[1], 2, $options[0]);
					
				} else{

					sendMsg('You are not authorized', 2);
				}

				break;

			case 'weather':

				$options = getOptions($command[1], 2);

				if(count($options) == 2){

					$weather = array();
					$options[1] = str_replace(' ', '+', $options[1]);

					if(($weather = getWeather($options)) != false){

						$message = 'Temperature - '.$weather['temp'].', Humidity - '.$weather['hum']. '%';
						$message = str_replace("\r\n", '', $message);
						$message = trim($message);
						sendMsg($message , 1);

					}else{
						sendMsg($options[0].' '.$options[1].' - is not found', 1);
					}

				} else{

					sendMsg('weather expects 2 paramaters: only one given on line 37', 1);
				}

				break;

			case 'change':

				if(checkWhitelist()){

					$options = getOptions($command[1], 2);

					switch($options[0]){

						case 'nick':

							if(checkWhitelist()){

								sendMsg("NICK $options[1]");

							}

							break;

						case 'channel':

							sendMsg("see ya there!",1);
							sendMsg("PART ".$GLOBALS['channel']);
							sendMsg("JOIN $options[1]");
							break;

						case 'hangout':

							sudoMsg("PART " . $GLOBALS['channel']);

							$channel = $options[1];

							sudoMsg("JOIN ". $channel);

							break;
					} 

				} else{

					sendMsg('You are not authorized', 2);
				}

				break;

			case 'me':

				$options = getOptions($command[1], 1);

				sendMsg("\001ACTION $options[0]\001" , 1);

				break;

			case 'reboot':

				if(checkWhitelist()){

					sendMsg('', 1);
					sendMsg('QUIT');

					socket_close($GLOBALS['sock']);

					$GLOBALS['loop'] = FALSE;
					$GLOBALS['restart'] = 1;
				} else{

					sendMsg('You are not authorized', 2);
				}

				break;

			case 'whitelist':

				$options = getOptions($command[1], '*');

				if(checkWhitelist()){

					switch($options[0]){

						case 'add':

							array_shift($options);
							$whitelistCount = count($GLOBALS['whitelist']) + 1;

							$whitelistFile = fopen('whitelist.txt', 'a');

							foreach($options as $option){

								echo $GLOBALS['whitelist'][$whitelistCount++] = $option;

								fwrite($whitelistFile, '!'.$option);

							}

							break;

						case 'remove':

							array_shift($options);

							var_dump($options);

							$whitelistContents = file_get_contents('whitelist.txt');

							foreach($options as $option){

								if(strstr($whitelistContents, $option) !== FALSE){

									if(count($GLOBALS['whitelist']) > 1){
										
										str_replace($option . '!', '', $whitelistContents);
										

									}else{

										str_replace($option, '',$whitelistContents);

									}
								}
							}

							file_put_contents('whitelist.txt', $whitelistContents);
							getWhitelist();

							break;

						case 'show':

							$whitelistMsg = '';
							$count = 0;

							foreach($GLOBALS['whitelist'] as $user){

								if($count == 0){

									$whitelistMsg .= $user;

								} else{

									$whitelistMsg .= ', '.$user;
								}

								$count++;

							}

							sendMsg($whitelistMsg, 1);

							break;

						
					}

				} else{

					sendMsg('You are not authorized', 2);
				}

				break;

			case 'pat':

				sendMsg('Pur', 1);

				break;

			case 'reversePolish':

				$options = getOptions($command[1], '*');

				break;

			case 'wiki':

				$url = 'http://en.wikipedia.org/wiki/' . $options;

				$html = getUrl($url);

				$dom = new DOMDocument;
				$dom->loadHTML($html);

				$title = $dom->getElementById('fistHeading');
				$title = $title->nodeValue;

				$content = $dom->getElementById('mw-content-text');
				$content = $content->nodeValue;

				//parse through $content to get the first two sentences in the html code.
				break;

			
			case 'help':


				break;
		}
	}
	
?>