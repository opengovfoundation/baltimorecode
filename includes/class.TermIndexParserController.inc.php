<?php

/**
 * Term Index Parser Controller
 *
 * PHP version 5
 *
 * @author		Bill Hunt <bill at krues8dr.com>
 * @copyright	2013 Bill Hunt
 * @license		http://www.gnu.org/licenses/gpl.html GPL 3
 * @version		0.7
 * @link		http://www.statedecoded.com/
 * @since		0.7
*/

// TODO: Still need to integrate the pre-processing into this script.
// Not worth the time right now.  Everything you need to do is listed here:
// https://github.com/opengovfoundation/baltimorecode/issues/1

require_once INCLUDE_PATH . '/logger.inc.php';

class TermIndexParserController
{
	public function __construct($args)
	{
		/*
		 * Set our defaults
		 */
		foreach($args as $key=>$value)
		{
			$this->$key = $value;
		}

		/*
		 * Setup a logger.
		 */
		$this->init_logger();

		/*
		 * Connect to the database.
		 */
		$this->db = new PDO( PDO_DSN, PDO_USERNAME, PDO_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT) );
		if ($this->db === FALSE)
		{
			die('Could not connect to the database.');
		}

		/*
		 * Prior to PHP v5.3.6, the PDO does not pass along to MySQL the DSN charset configuration
		 * option, and it must be done manually.
		 */
		if (version_compare(PHP_VERSION, '5.3.6', '<'))
		{
			$db->exec("SET NAMES utf8");
		}

		/*
		 * Set our default execution limits.
		 */
		$this->set_execution_limits();
	}

    /**
     * Get a logger
     */
	public function init_logger()
	{
		if (!isset($this->logger))
		{
			$this->logger = new Logger();
		}
	}

	/**
	 * Let this script run for as long as is necessary to finish.
     * @access public
     * @static
     * @since Method available since Release 0.7
     */

	public function set_execution_limits()
	{
		/*
		 * Let this script run for as long as is necessary to finish.
		 */
		set_time_limit(0);

		/*
		 * Give PHP lots of RAM.
		 */
		ini_set('memory_limit', '128M');
	}

	/**
	 * Parse the files
	 */
	public function parse()
	{
		$query = 'DELETE FROM term_index';
		$statement = $this->db->prepare($query);
		$statement->execute();

		/*
		 * Prepare statements.
		 */
		$query = array();
		$query['parent_insert'] = 'INSERT INTO term_index SET term = :term, order_by = :order_by';
		$query['child_insert'] = 'INSERT INTO term_index SET term = :term,
			parent_id = :parent_id, order_by = :order_by';
		$query['term_insert'] = 'INSERT INTO term_index SET term = :term, law_id = :law_id,
			parent_id = :parent_id, section = :section, article = :article, order_by = :order_by';
		// TODO add edition here.

		$query['see_update'] = 'UPDATE term_index SET see = :see WHERE id = :id';
		$query['see_append'] = 'UPDATE term_index SET see = CONCAT(see, ", ", :see) WHERE id = :id';
		$query['see_also_update'] = 'UPDATE term_index SET see_also = :see_also
			WHERE id = :id';
		$query['see_also_append'] = 'UPDATE term_index SET see_also = CONCAT(see_also, ", ", :see_also)
			WHERE id = :id';

		$query['find_law'] = 'SELECT id FROM laws LEFT JOIN structure_unified
			ON laws.structure_id = structure_unified.s1_id
			WHERE s2_identifier = :article AND laws.section = :section';

		$statement = array();
		foreach($query as $key => $value) {
			$statement[$key] = $this->db->prepare($value);
		}

		$temp_term = '';
		$see_active = false;

		$indent = array();
		$parents = array();


		$files = glob($this->directory . '*_fixed.txt');
		//$files = glob($this->directory . '0005_{1,2}_fixed.txt', GLOB_BRACE);

		$i = 1;

		foreach ($files as $file)
		{

			$file_data = file($file);
			foreach ($file_data as $line)
			{

//var_dump($line);
				// Skip bad data that cleanup didn't catch.
				if (
					preg_match ('/^ *Article *Section *$/', $line) ||
					preg_match('/^ *-?[A-Z0-9]{0,3}- *$/', $line) ||
					preg_match('/^ *-[A-Z0-9]{0,3}-? *$/', $line)
				)
				{
					continue;
				}


				if($see_active)
				{
					preg_match('/^(?P<offset> *)/', $line, $matches);
					if(strlen($matches['offset']) < end($indent))
					{
						$see_active = false;
//var_dump('Resetting see_active', strlen($matches['offset']), end($indent));
					}
				}

				// Main headings
				if (preg_match('/^(?P<offset> *)(?P<term>[A-Z :,\-"\']+) *- *$/', $line, $matches))
				{
					// We have a new heading, so start a new top-level section;
					$parents = array();
//var_dump('Resetting parents for heading.', $parents);

					// If we've already encountered a prefix, we don't need to do the offset.
					if(!strlen($temp_term)) {
						$indent = array(strlen($matches['offset']));
//var_dump('Resetting indent for heading', $indent);
					}


					$see_active = false;

					$term = array(
						':term' => $temp_term . trim($matches['term']),
						':order_by' => $i
					);

					$statement['parent_insert']->execute($term);
					$id = $this->db->lastInsertID();

					$parents[] = $id;
//var_dump('Adding parent for heading.', $parents);

					$term[':id'] = $id;

					$temp_term = '';
				}

				// Subheadings
				elseif (preg_match('/^(?P<offset> *)(?P<term>.+?) - *$/', $line, $matches))
				{
					// If we've already encountered a prefix, we don't need to do the offset.
					if(!strlen($temp_term))
					{
						// figure out the proper offset.
						while(
							count($indent) > 0 &&
							strlen($matches['offset']) <= end($indent)
						)
						{
							array_pop($indent);
							array_pop($parents);
//var_dump('subheadings: popping', strlen($matches['offset']), end($indent));
						}

						$indent[] = strlen($matches['offset']);
//var_dump('Setting indent for subheading.', $indent);
					}

					$see_active = false;

					$term = array(
						':term' => $temp_term . trim($matches['term']),
						':parent_id' => end($parents),
						':order_by' => $i
					);

					$statement['child_insert']->execute($term);
					$id = $this->db->lastInsertID();

					$parents[] = $id;
//var_dump('Adding parent for subheading.', $parents);

					$term[':id'] = $id;

					$temp_term = '';
				}

				// See & See Also
				elseif (preg_match('/^(?P<offset> *)(?P<see>See( also)?) (?P<term>.+?) *$/', $line, $matches))
				{
					if(!strlen($temp_term))
					{
						// figure out the proper offset.
						while(
							count($indent) > 0 &&
							strlen($matches['offset']) <= end($indent)
						)
						{
							array_pop($indent);
							array_pop($parents);
//var_dump('see also: popping', strlen($matches['offset']), end($indent));
						}

//var_dump('Setting indent and parents for See/See Also', $indent, $parents);
					}

					if (count($parents))
					{
//var_dump('See parents', $parents);
						if ($matches['see'] == 'See')
						{
							$see_active = 'see';

							$term = array(
								':id' => end($parents),
								':see' => $matches['term']
							);
//var_dump($term);

							$statement['see_update']->execute($term);
							$id = $this->db->lastInsertID();
						}
						elseif ($matches['see'] == 'See also')
						{
							$see_active = 'see_also';
							$term = array(
								':id' => end($parents),
								':see_also' => $matches['term']
							);

							$statement['see_also_update']->execute($term);
							$id = $this->db->lastInsertID();
						}


					}
				}
				// Individual terms.
				elseif (preg_match('/^(?P<offset> *)(?P<term>.*?) ?(\. ?)+ *(?P<article>[A-Za-z0-9]{1,5}) *(\. ?)+ *(?P<section>([A-Za-z0-9-\.]{1,8}, ?)*[A-Za-z0-9-\.]{1,8}) *$/', $line, $matches))
				{
					// If this is not just appending to a previous term.
					if(strlen(trim($matches['term'])) && !strlen($temp_term))
					{
						// Figure out the proper offset.
						while(
							count($indent) > 0 &&
							strlen($matches['offset']) <= end($indent)
						)
						{
//var_dump('terms: popping', strlen($matches['offset']), end($indent));
							array_pop($indent);
							array_pop($parents);
						}
//var_dump('Updating parent and indent for individual terms', strlen($matches['offset']), $indent, $parents);
					}

					if(strlen(trim($matches['term'])))
					{
						$term = array(
							':term' => $temp_term . $matches['term'],
							':parent_id' => end($parents),
							':order_by' => $i
						);
					}
					else
					{
						unset($term[':id']);
					}

					$sections = explode(',', $matches['section']);

					foreach($sections as $section)
					{
						$term[':article'] = $matches['article'];
						$term[':section'] = trim($section);

						// Find the parent law.
						$find_args = array(
							':article' => $term[':article'],
							':section' => $term[':section']
						);

						// We may need to left-pad the section.
						if(is_numeric($find_args[':section']) && (int) $find_args[':section'] < 10)
						{
							$find_args[':section'] = '0' . ((int) $find_args[':section']);
						}

						$result = $statement['find_law']->execute($find_args);

						// If we cannot find a matching law, set null instead.
						if($result === FALSE || $statement['find_law']->rowCount() < 1)
						{
							$term[':law_id'] = null;
							$statement['term_insert']->bindValue(':law_id', null, PDO::PARAM_INT);
						}
						// Otherwise, use that law.
						else
						{
							$law = $statement['find_law']->fetch(PDO::FETCH_ASSOC);
							$term[':law_id'] = $law['id'];
						}

//var_dump('Insert term', $term);
						$result = $statement['term_insert']->execute($term);

						if($result === FALSE)
						{
							echo '<p>Failure: ' . $query['term_insert'] . '</p>';
							var_dump($statement['term_insert']->errorInfo(), $term);
						}
					}

					$term[':id'] = $this->db->lastInsertID();

					$temp_term = '';
				}
				// For everything else we have two possible cases.
				else
				{
					preg_match('/^(?P<offset> *)/', $line, $matches);

					// We need to append this to the parent's "See" or "See Also"
					if($see_active && strlen($matches['offset']) >= end($indent))
					{
//var_dump('everything else: see active!', trim($line));
						if($see_active == 'see')
						{
							$query_type = 'see_append';
						}
						elseif($see_active == 'see_also')
						{
							$query_type = 'see_also_append';
						}

						if($query_type)
						{
							$term = array(
								':'.$see_active => ' ' . trim($line),
								':id' => end($parents)
							);
//var_dump($term, $query_type, $query[$query_type]);
							$result = $statement[$query_type]->execute($term);

							if($result === FALSE)
							{
								echo '<p>Failure: ' . $query[$query_type] . '</p>';
								var_dump($statement[$query_type]->errorInfo(), $term);
							}


							$id = $this->db->lastInsertID();
						}
					}
					// Or we need to prepend this to the next element that's matched.
					else
					{
						$see_active = FALSE;
						// If we don't already have a prefix, we need to calculate the depth.
						if(!strlen($temp_term)) {
							// figure out the proper offset.
							while(
								count($indent) > 0 &&
								strlen($matches['offset']) <= end($indent)
							)
							{
								array_pop($indent);
								array_pop($parents);
//var_dump('everything else: popping', strlen($matches['offset']), end($indent));
							}
						}

						$temp_term .= trim($line) . ' ';
					}
				}

				$i++;
			}

		}
	}

}