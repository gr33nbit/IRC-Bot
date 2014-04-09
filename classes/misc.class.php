<?php

	class miscFunctions{


		//check if there are appropriate amount of command options
		public function checkParameterNo($command, $max, $req = 0){

			$count = count($command);


		}

		public function getOptions($msg, $optionNum){

			if($optionNum == '*'){

				return explode(' ', $msg);

			} else{

				return explode(' ', $msg, $optionNum);

			}
			

		}

		public function checkWhitelist(){

			if(in_array($GLOBALS['username'], $GLOBALS['whitelist']) == TRUE){

				return TRUE;

			}

			return FALSE;

		}

		public function getWhitelist(){

			$count = 0;

			if(($whitelistTxt = file_get_contents('whitelist.txt')) !== FALSE){

				$whitelistTxt = explode("!", $whitelistTxt);

				foreach($whitelistTxt as $nick){

					$GLOBALS['whitelist'][$count++] = $nick;

				}

			}		

		}

		public function getUrl($url){

			$curl = curl_init($url);

			curl_setopt_array($curl, array(
				CURLOPT_SSL_VERIFYPEER => FALSE,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_USERAGENT => 'IRC weather bot function',
				CURLOPT_FOLLOWLOCATION => TRUE
			));

			return curl_exec($curl);


		}

		public function getWeather($options){

			$html = $this->getUrl('http://www.wund.com/cgi-bin/findweather/getForecast?query='.$options[0].'+'.$options[1]);
		
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
	}
?>