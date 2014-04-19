<?php
	
	class commands{

		private $msg;
		private $misc;

		public function __construct($msg, $misc){

			$this->msg = $msg;
			$this->misc = $misc;

		}

	 	public function commands($command){

		//get command and options if applicable
		$command[0] = null;
		$command = explode(' ', $command, 2);

		$command[0] = trim($command[0]);
		$command[1] = trim($command[1]);

		$options = array();

		switch($command[0]){

			case '?':

				$options = explode($command[1]);
				
				$count = count($options);

				$seed = srand(openssl_random_pseudo_bytes(16));
				$location = $seed % $count;

				$selected = $options[$location];

				$this->msg->sendMsg("No. ".$location." - $selected");

				break;
			case 'shutdown':

				if($this->misc->checkWhitelist()){

					$this->msg->sendMsg('see ya!', 1);
					$this->msg->sendMsg('QUIT');

					socket_close($GLOBALS['sock']);

					$GLOBALS['loop'] = FALSE;
				} else{

					$this->msg->sendMsg('You are not authorized', 2);
				}
				break;


			//LEAVE CURRENT CHANNEL
			case 'leave':

				if($this->misc->checkWhitelist()){

					$this->msg->sendMsg('PART '.$GLOBALS['channel'] . ' :Farewell');

				} else{

					$this->msg->sendMsg('You are not authorized', 2);
				}			

				break;

			//JOIN CHANNEL
			case 'join':

				if($this->misc->checkWhitelist()){
					
					$options = $this->misc->getOptions($command[1], 1);

					$this->msg->sendMsg('JOIN '. $options[0]);

				} else{

					$this->msg->sendMsg('You are not authorized', 2);
				}

				break;

			case 'command':

				if($this->misc->checkWhitelist()){

					$options = $this->misc->getOptions($command[1], 2);

					//CHANGE THE COMMAND PREFIX
					if($options[0] == 'prefix'){

						$this->msg->sendMsg('Command Prefix changed from: \'' . $GLOBALS['commandPrefix'] . '\', to: \''. $options[1][0].'\'' ,1);

						$GLOBALS['commandPrefix'] = $options[1][0];
					}
					
				} else{

					$this->msg->sendMsg('You are not authorized', 2);
				}

				break;

			case 'msg':

				if($this->misc->checkWhitelist()){

					$options = $this->misc->getOptions($command[1], 2);

					$this->msg->sendMsg($options[1], 2, $options[0]);
					
				} else{

					$this->msg->sendMsg('You are not authorized', 2);
				}

				break;

			case 'weather':

				$options = $this->misc->getOptions($command[1], 2);

				if(count($options) == 2){

					$weather = array();
					$options[1] = str_replace(' ', '+', $options[1]);

					if(($weather = $this->misc->getWeather($options)) != false){

						$message = 'Temperature - '.$weather['temp'].', Humidity - '.$weather['hum']. '%';
						$message = str_replace("\r\n", '', $message);
						$message = trim($message);
						$this->msg->sendMsg($message , 1);

					}else{
						$this->msg->sendMsg($options[0].' '.$options[1].' - is not found', 1);
					}

				} else{

					$this->msg->sendMsg('weather expects 2 paramaters: only one given on line 37', 1);
				}

				break;

			case 'change':

				if($this->misc->checkWhitelist()){

					$options = $this->misc->getOptions($command[1], 2);

					switch($options[0]){

						case 'nick':

							if($this->misc->checkWhitelist()){

								$this->msg->sendMsg("NICK $options[1]");

							}

							break;

						case 'channel':

							$this->msg->sendMsg("see ya there!",1);
							$this->msg->sendMsg("PART ".$GLOBALS['channel']);
							$this->msg->sendMsg("JOIN $options[1]");
							break;

						case 'hangout':

							$this->msg->sendMsg("PART " . $GLOBALS['channel']);

							$channel = $options[1];

							$this->msg->sendMsg("JOIN ". $channel);

							break;
					} 

				} else{

					$this->msg->sendMsg('You are not authorized', 2);
				}

				break;

			case 'me':

				$options = $this->misc->getOptions($command[1], 1);

				$this->msg->sendMsg("\001ACTION $options[0]\001" , 1);

				break;

			case 'reboot':

				if($this->misc->checkWhitelist()){

					$this->msg->sendMsg('', 1);
					$this->msg->sendMsg('QUIT');

					socket_close($GLOBALS['sock']);

					$GLOBALS['loop'] = FALSE;
					$GLOBALS['restart'] = 1;
				} else{

					$this->msg->sendMsg('You are not authorized', 2);
				}

				break;

			case 'whitelist':

				$options = $this->misc->getOptions($command[1], '*');

				if($this->misc->checkWhitelist()){

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
							$this->misc->getWhitelist();

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

							$this->msg->sendMsg($whitelistMsg, 1);

							break;
						
					}

				} else{

					$this->msg->sendMsg('You are not authorized', 2);
				}

				break;

			case 'pat':

				$this->msg->sendMsg('Pur', 1);

				break;

			//reversePolish calculator
			case 'reversePolish':

				$options = $this->misc->getOptions($command[1], '*');

				break;

			//search wikipedia for a phrase or word
			case 'wiki':

				$sentence = '';

				//retrieve the HTML
				$url = 'http://en.wikipedia.org/wiki/' . $command[1];
				$html = $this->misc->getUrl($url);

				//load the html into a html parser
				$dom = new DOMDocument;
				$dom->loadHTML($html);

				//get title
				$title = $dom->getElementById('firstHeading');
				$title = $title->nodeValue;
				$title = str_replace(' ', '_', $title);

				$content = $dom->getElementById('mw-content-text');
				$content = $content->nodeValue;

				
				//the search did not find the right results
				if(strpos($content, 'Wikipedia does not have an article with this exact name.') == FALSE){

					//more than one result
					if(strpos($content, 'may refer to either one of these things:') == FALSE && strpos($content, 'can refer to:') == FALSE  && 	strpos($content, 'may refer to:') == FALSE && strpos($title, '(disambiguation)') == FALSE){
						//
						//ARTICLE WAS FOUND
						//
						$content = $dom->getElementsByTagName('p');

						if($content->length !== 0){

							$contents = '';
							//searching through each <p> tag
							foreach($content as $cont){

								//contents = the value of one of the <p> tags
								$contents = $cont->nodeValue;

								//if contents the first pargraph
								if(strpos($contents, $title) !== FALSE){

									break;

								}
							}

							$fullStop = 0;
							$count = 0;
							$contentLen = strlen($contents);
							//untill there has been 2 full stops (2 sentences)
							//or until the end of the characters if its before 2 full stops
							while($fullStop != 2 && $count <= $contentLen){

								if($contents[$count] == '.'){

									$fullStop++;
								}

								$sentence .= $contents[$count++];
							}
								
							//link to the article
							$this->msg->sendMsg('http://en.wikipedia.org/wiki/'.$title,1);
							//send first couple of sentences
							$this->msg->sendMsg($sentence, 1);	
						}

					} else{


						$this->msg->sendMsg('more than one result found, refer to following link', 1);
						$this->msg->sendMsg('http://en.wikipedia.org/wiki/'.$title, 1);

					}

				} else{

					$this->msg->sendMsg('\''. $title .'\' was not found.', 1);
				}
				break;

			case 'google':
					$options = str_replace(' ', '+', $command[1]);

					$this->msg->sendMsg('https://google.com/search?q=' . $options, 3);
				break;

			case 'googleIt':

					$options = str_replace(' ', '+', $command[1]);

					$this->msg->sendMsg('http://letmegooglethat.com/?q='. $options, 3);

				break;
			
			case 'help':

					$this->msg->sendMsg('!wiki (query) - returns a link to the article found of (query) and a small synopsis.', 2);
					$this->msg->sendMsg('!google (query) - returns link to a google search for (query)', 2);
					$this->msg->sendMsg('!googleIt (query) - returns link to letmegooglethat.com to search for (query)', 2);
					$this->msg->sendMsg('!weather (city) (country) - returns the temperature, conditions and humidity.', 2);
					$this->msg->sendMsg('-- e.g !weather brisbane australia', 2);
					$this->msg->sendMsg('-- e.g Temperature - 22 C, Conditions - cloudy, Humidity - 80%', 2);


				break;
		}
	}
	
	}
?>