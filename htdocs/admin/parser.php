<?php
	$structure = array('@\n\s*(\([a-z]+\))\s.*@', '@\n\s*(\(\d+\)).*@', '@\n\s*(\([ixv]+\)).*@', '@\n\s*(\d+)\..*@');

	ini_set("error_reporting", E_ALL);
	file_put_contents('error.log', '');

	/**
	 * 	Autoload Decoder Classes
	 */
	function autoload_decoder($classname){
		$path = str_replace('\\', '/', $classname);
		$path = __DIR__ . '/' . $path . '.php';

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
		foreach(glob('./original/Art*.xml') as $filename){
			print "Parsing $filename\n";

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
			$text = strip_tags($src, '<para>');
			$text = str_replace('<para></para>', '', $text);

			//Split content on 'Subtitle 1'
			$subtitles = preg_split("@^<para>\s*(Subtitle\s1\s*)(?:</para>)?$@m", $text);

			if(!isset($subtitles[2])){
				throw new Exception("Couldn't get body of text for $filename in article: $article_index");
			}

			//Drop the table of contents
			$subtitles = $subtitles[2];

			//Split the remaining content into Subtitles
			$subtitles = preg_split('@^<para>(Subtitles?\s[0-9A]+\.?\s?[to]*\s?[0-9A]*)@m', $subtitles, -1, PREG_SPLIT_DELIM_CAPTURE);

			//Loop through the subtitles
			foreach($subtitles as $index => $content){

				// Parts are sub-subtitles (?);
				unset($part);

				if($index % 2 != 0){//Odd indexes are the subtitle labels
					$ret = preg_match_all('@Subtitles?\s([0-9A]+)\.?\s?[to]*\s?([0-9A]*)@', $content, $matches);

					if($ret === 0 || $ret === false){
						throw new Exception("Subtitle index not found.\n".
							"Index: $index\n" .
							"Content: " . substr($content, 0, 78) . "\n");
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
					$sections = preg_split('@\n<para>&#167;+ ([0-9A-Z]+-?[0-9A-Z]*\.?\s+)@', $subtitle_content, -1, PREG_SPLIT_DELIM_CAPTURE);

					// We have no way to keep the section identifier numbers from being
					// split as well, so we recombine those back in here:
					foreach($sections as $i => $section)
					{
						if(($i % 2) == 1)
						{
							$sections[$i+1] = $sections[$i] . $sections[$i+1];
							$sections[$i] = '';
						}
					}
					$sections = array_filter($sections);

					if($article_index == '01'){
						customLog($sections);
					}
					//Loop through the sections
					foreach($sections as $index => $section){

						//Create the section object
						$tempSection = new Decoder\Section();



						//If the index is zero, we have the Subtitle name
						if($index == 0){
							//Part 1 almost always comes right after the section title.
							if(preg_match("/<para>(Part\.? +([0-9]+)\.)(.*?)<\/para>/m", $section, $matches))
							{
								$pieces = preg_split("/(<para>Part\.? +[0-9]+\. )/", $section, 2, PREG_SPLIT_DELIM_CAPTURE);



								$subtitle_title = array_shift($pieces);
								$subtitle_title = str_replace("\n", " ",
									trim(strip_tags($subtitle_title)));

								$section = join($pieces);

								$section = trim(preg_replace(
									"/(<para>(Part\.? +([0-9]+)\.)(.*?)<\/para>)/m", '',
									$section));

								$part_name = trim(strip_tags($matches[3]));

								$part = array(
									'index' => $matches[2],
									'title' => $part_name
								);

								if(!strlen($section))
								{
									continue;
								}

							}
							else{
								//if($article_index == '22') var_dump('Section', $article_index, substr($section, 0, 40));
								$subtitle_title = str_replace("\n", " ", trim(strip_tags($section)));
								continue;
							}

						}

						// Parts may appear at the bottom.
						// If that's the case, we add it to the *next* section.

						// Note: We're making the assumption here that parts only come at
						// the *top* or *bottom* of sections.  That's a pretty safe bet,
						// but keep an eye on this!

						if(preg_match("/<para>Part\.? +([0-9]+)\. +/", $section, $matches))
						{
							$pieces = preg_split("/(<para>Part\.? +[0-9]+\. +.*?<\/para>)/m", $section, -1, PREG_SPLIT_DELIM_CAPTURE);
							$pieces = array_filter($pieces);
							$section = '';

							foreach($pieces as $piece)
							{
								$piece = trim($piece);
								if(preg_match('/<para>Part\.? +([0-9]+)\. +(.*)<\/para>/m', $piece, $matches))
								{

									$temp_part = array(
										'index' => $matches[1],
										'title' => strip_tags($matches[2])
									);
								}
							}

							$section = trim(preg_replace(
								"/(<para>(Part\.? +([0-9]+)\.)(.*?)<\/para>)/m", '',
								$section));

							if(!strlen($section))
							{
								continue;
							}
						}

						//Split the first line (Section top-level information)
						$lines = preg_split('@</para>@', $section, 2);

						if(isset($lines[1]))
						{
							$lines[1] = strip_tags($lines[1]);

							// Grab any editor's notes.
							if(strlen($lines[1]))
							{
								if(strpos($lines[1], 'Editor&#8217;s Note:') === 0)
								{
									list($note, $lines[1]) = preg_split('/(\r\n|\r|\n)+/', $lines[1], 2);
									$lines[0] .= "\n\n" . $note;

									$lines[1] = trim($lines[1]);
									if(!strlen($lines[1]))
									{
										unset($lines[1]);
									}
								}
							}
							else
							{
								unset($lines[1]);
							}
						}

						$lines[0] = strip_tags($lines[0]);

						// Article 22 does it's own thing.
						if($article_index == '22')
						{
							//grab the section catch title
							$catch_line = preg_replace('@[0-9A]+( (to|-) [0-9A]+)?\.?@', '', $lines[0]);

							//grab the section number
							$ret = preg_match('@(([0-9A]+)( (?:to|-) ([0-9A]+))?)\.?@', $lines[0], $identifier);

							$order_by = $identifier[2];
						}
						else
						{
							//grab the section catch title
							$catch_line = preg_replace('@(\d+[A-Z]?(\s?-\s?\d+(\s?(to)\s?\d*[A-Z]?-?\d*)?)?)\.?@', '', $lines[0]);

							//grab the section number
							$ret = preg_match('@(\d+[A-Z]?-?(\d+)\s?(to)*\s?\d*[A-Z]?-?\d*)\.?@', $lines[0], $identifier);

							$order_by = $identifier[2];
						}

						if($ret === 0 || $ret === false){
							throw new Exception("Error getting section number in $filename " .
								"for line: " . print_r($lines[0], true) . "\n" .
								"****\n" .
								"Subtitle: " .
								substr($section, 0, 78));
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

						if(isset($part))
						{
							$tempSection->addParent(3, 'Part', $part['index'], $part['title']);
						}

						$tempSection->setIdentifier($identifier[1]);
						$tempSection->setContent($lines[1]);
						$tempSection->setCatchLine($catch_line);
						$tempSection->setOrderBy($order_by);

						$tempSection->setDebug(true);

						$tempSection->setPatterns($structure);
						//$tempSection->parseChildren();

						$tempSection->toXML();
						$tempSection->saveXML('./data/' . 'Art' . $article_index . '-' . str_replace(' ', '-', $identifier[1]) . '.xml');

						// Add any parts we'd stored earlier.
						if(isset($temp_part))
						{
							$part = $temp_part;
							unset($temp_part);
						}
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
