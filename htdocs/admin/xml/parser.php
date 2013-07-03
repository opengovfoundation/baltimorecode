<?php
	$structure = array('@\n\s*(\([a-z]+\))\s.*@', '@\n\s*(\(\d+\)).*@', '@\n\s*(\([ixv]+\)).*@', '@\n\s*(\d+)\..*@');

	ini_set("error_reporting", E_ALL);
	file_put_contents('error.log', '');
	
	/**
	 * 	Autoload Decoder Classes
	 */	
	function autoload_decoder($classname){
		$path = str_replace('\\', '/', $classname);
		$path = dirname(__DIR__) . '/xml/' . $path . '.php';
		if(is_file($path)){
			require_once($path);
		}
	}
	spl_autoload_register('autoload_decoder');
	
	/**
	 * 	Custom log function to ./error.log
	 */
	function customLog($msg){
		 file_put_contents('error.log', "\n------------------\n" . print_r($msg, true) . "\n------------------\n", FILE_APPEND);
	}
	
	/**
	 * 	Main try block
	 */
	try{
		//Grab files
		foreach(glob('../original/Art*.xml') as $filename){
		
			$src = file_get_contents($filename);
			
			//Grab article information
			$ret = preg_match_all('@(\d+)\s?-\s(.*)\.xml@', $filename, $article_info);
			
			if($ret === 0 || $ret === false){
				throw new Exception("Couldn't grab article info for:\nFilename: $filename\n");
			}
			
			$article_index = $article_info[1][0];
			$article_title = $article_info[2][0];
			
			//Skip unusual articles
			if($article_index == '00'){
				continue;
			}
			
			//strip useless XML tags
			$text = strip_tags($src);
		
			//Split content on 'Subtitle 1'
			$subtitles = preg_split("@^(Subtitle\s1\s*)$@m", $text);

			if(!isset($subtitles[2])){
				throw new Exception("Couldn't get body of text for article: $article_index");
			}

			//Drop the table of contents
			$subtitles = $subtitles[2];
		
			//Split the remaining content into Subtitles
			$subtitles = preg_split('@^(Subtitles?\s\d+\s?[to]*\s?\d*)\s*$@m', $subtitles, -1, PREG_SPLIT_DELIM_CAPTURE);
		
			//Loop through the subtitles
			foreach($subtitles as $index => $content){
			
				if($index % 2 != 0){//Odd indexes are the subtitle labels
					$ret = preg_match_all('@Subtitles?\s(\d+)\s?[to]*\s?(\d*)@', $content, $matches);
				
					if($ret === 0 || $ret === false){
						throw new Exception("Subtitle index not found.\n Index: $index\nContent: $content\n");
					}
					$subtitle_index = $matches[1][0];
				}
				//Even indexes are the subtitle contents
					//After grabbing the content, we have the title and content
					//Now we need to create the Sections and set their parents
				else{
					if($index == 0){
						$subtitle_index = 1;
					}
				
					$subtitle_content = $content;
				
					//Split the subtitle content on the Section identifiers
					$sections = preg_split('@\n\n&#167;@', $subtitle_content);
					if($article_index == '01'){
						customLog($sections);
					}
					//Loop through the sections 
					foreach($sections as $index => $section){
					
						//Create the section object
						$tempSection = new Decoder\Section();
					
						//If the index is zero, we have the Subtitle name
						if($index == 0){
							$subtitle_title = $section;
							continue;
						}
					
						//Split the first line (Section top-level information)
						$lines = preg_split('/\r\n|\r|\n/', $section, 2);
					
						//grab the section catch title
						$catch_line = preg_replace('@(\d+[A-Z]?-\d+\s?[to]*\s?\d*-?\d*)\.?@', '', $lines[0]);
					
						//grab the section number
						$ret = preg_match('@(\d+[A-Z]?-?\d*\s?[to]*\s?\d*-?\d*)\.?@', $lines[0], $identifier);
						
						if($ret === 0 || $ret === false){
							throw new Exception("Error getting section number for line: " . print_r($lines[0], true) . "\n****\n Subtitle: $subtitle_content");
						}
					
						//throw an error if we can't grab the section number
						if(count($identifier) < 2){
							throw new Exception("Error with section: " . print_r($section, true));
						}
					
						if(!isset($lines[1])){
							$lines[1] = 'No Content';
						}
					
						$tempSection->addParent(1, 'Article', $article_index, $article_title);
					
						$tempSection->addParent(2, 'Subtitle', $subtitle_index, $subtitle_title);
						$tempSection->setIdentifier($identifier[1]);
						$tempSection->setContent($lines[1]);
						$tempSection->setCatchLine($catch_line);
					
						$tempSection->setDebug(true);
					
						$tempSection->setPatterns($structure);
						//$tempSection->parseChildren();
					
						$tempSection->toXML();
						$tempSection->saveXML('Art' . $article_index . '-' . str_replace(' ', '-', $identifier[1]) . '.xml');
					}
				}	
			}
		}

	}catch(Exception $e){
		echo "*** ERROR ***\n";
		echo $e->getMessage();
		echo "*************\n";
		exit;
	}
	