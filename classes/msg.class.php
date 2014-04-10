<?php

	class msg{

		public $commands;
		private $misc;

		public function __construct($misc){

			$this->misc = $misc;
		}

		public function getMsg(){

			$msg = '';

			//receive message .. if failed print error
			if(socket_recv($GLOBALS['sock'], $msg, 512, 0) === FALSE){

				echo socket_last_error().' - '.socket_strerror(socket_last_error());

				return false;

			} else{

				//no data was received
				if(strlen($msg) != 0){

					//there is a ping request
					if($msg[0] == 'P' && $msg[1] == 'I' && $msg[2] == 'N' && $msg[3] == 'G'){

						//make pong msg
						$msg = str_replace('PING', 'PONG', $msg);

						//send pong msg
						$this->sendMsg($msg);

					} else{
						//echo $msg;
						$this->parseMsg($msg);
						
					}
				}
			}
		}

		public function sendMsg($msg, $channel = 0, $other = null){
			
			$msg = preg_replace( '/[^[:print:]]/', '',$msg);

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

			$msg .= "\r\n";

			if(socket_send($GLOBALS['sock'], $msg, strlen($msg), 0) !== false){	

				return true;

			}

			return false;
		}

		public function parseMsg($msg){

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

						$this->sendMsg('JOIN '. $GLOBALS['channel']);

					} else if(strpos($msg[0], '433') !== FALSE){

						$this->sendMsg('NICK '. $GLOBALS['nick'][++$GLOBALS['nickNo']]);

					}

				} else{

					//split all of the options.
					$msg[0] = trim($msg[0]);
					$msgMeta = explode(' ', $msg[0]);

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
								$this->commands->commands($message);

							//has a link
							}else if(strpos($message, 'http://') !== false ||(strpos($message, "https://") !== false)){

								$continue = true;

								if(strpos($message, 'http://') !== false){

									$position = strpos($message, 'http://');

								}else{

									$position = strpos($message, 'https://');

								}
								$url = '';

								while($message[$position] != ' ' && $position != strlen($message) && ctype_graph($message[$position]) != FALSE){

										$url .= $message[$position++];
									
								}
								
									$url2 = $url;

									$url2 = str_replace('http://', '', $url2);
									$url2 = str_replace('https://', '' , $url2);

									if(strpos($url2, '/') !== FALSE){

										$url2 = explode('.', $url2, 2);								

										
										if(strpos($url2[1], '.') !== FALSE){
											
											$url2 = explode('.', $url2[1]);

											$count = count($url2);
											
											$url2 = $url2[$count - 1];
											
											switch($url2){

												case 'html':
												case 'xml':
												case 'htm':
												case 'php':
												case 'asp':
												case 'aspx':

													$continue = true;
													break;

												default:

													$continue = false;

											}
										}

									}

									
									if($continue == true){
										$url;

										$html = $this->misc->getUrl($url);
										$title = '';

										if(($position = strpos($html, '<title>')) !== FALSE){

											$position += strlen('<title>');
											while($html[$position] != '<' && $position <= strlen($html)){

												$html[$position];
												$title .= $html[$position++];


											}
										}
										if($title != ''){
											
											$this->sendMsg('Title - \''.$title.'\'' , 1);

										}
									}

								

								
							}

						//This is a server message
						} else if(is_numeric($msgMeta[1])){

							$this->serverMessage($msgMeta, $msg[1]);
						}


					} else{


					}
				}	
			}
		}

		public function serverMessage($msgMeta, $message){

			$serverCode = $msgMeta[1];

			switch($serverCode){

				case '356':

					break;
			}


		}

	}

?>