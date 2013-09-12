<?php

/**
 * The Term class, for retrieving data about terms.
 *
 * PHP version 5
 *
 * @author		Bill Hunt <bill at krues8dr dot com>
 * @copyright	2013 Bill Hunt
 * @license		http://www.gnu.org/licenses/gpl.html GPL 3
 * @version		0.7
 * @link		http://www.statedecoded.com/
 * @since		0.7
 *
 */

class Term
{
	public $db;

	/**
	 * Setup our class
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}

	/**
	 * Get terms.  Takes query parameters to translate.
	 * Return an associative array.
	 */
	public function get_terms($args = array())
	{
		$where = array();
		$limit = '';
		$query_args = array();

		if (isset($args['term']) && strlen($args['term']))
		{
			$where[] = 'term = :where_term';
			$query_args[':where_term'] = $args['term'];
		}
		elseif (isset($args['query']) && strlen($args['query']))
		{
			// Query is a simple like match for now.
			// Solr update (SD v0.9) may change this.
			$where[] = 'term LIKE :where_query';
			$query_args[':where_query'] = '%'.$args['query'].'%';
		}

		if (isset($args['parent_id']) && strlen($args['parent_id']))
		{
			$where[] = '(t3_id = :parent_id OR t2_id = :parent_id)';
			$query_args[':parent_id'] = $args['parent_id'];
		}

		// Handle pagination.
		if (isset($args['page']) && strlen($args['page']) &&
			isset($args['per_page']) && strlen($args['per_page']))
		{
			$limit = 'LIMIT :start, :per_page ';
			$query_args[':start'] = ((int) $args['page'] - 1) * ((int) $args['per_page']);
			$query_args[':per_page'] = $args['per_page'];
		}


		// TODO: later, we're going to want to join this to the permalinks table.
		$query = 'SELECT term_index.*, term_index_unified.*,
		laws.section AS laws_section, laws.catch_line AS laws_catch_line
		FROM term_index
			LEFT JOIN term_index_unified on term_index.id = t1_id
			LEFT JOIN laws ON term_index.law_id = laws.id ';
		if (count($where) > 0)
		{
			$query .= 'WHERE ' . join(' AND ', $where) . ' ';
		}

		$query .= 'ORDER BY order_by ASC ';

		if (strlen($limit))
		{
			$query .= $limit;
		}

		$statement = $this->db->prepare($query);

		$result = $statement->execute($query_args);

		if ($result === FALSE)
		{

			return FALSE;
		}
		else
		{
			return $statement->fetchAll(PDO::FETCH_ASSOC);
		}
	}


	/**
	 * Wrapper to get terms in a nested fashion.
	 * This gets a little complicated.  Basically, we're creating a placeholder in
	 * $children to hold the child terms of each parent term.  Then we create a
	 * reference to that element in the parent term.  So nesting happens via magic.
	 */
	public function get_terms_nested($args = array())
	{
		$temp_terms = $this->get_terms($args);

		$terms = array();
		$terms_done = array();
		$children = array();


		foreach ($temp_terms as $term)
		{
			// We need an indicator that this is a thing we were looking for.
			if(
				(isset($args['query']) && strlen($args['query'])) ||
				(isset($args['term']) && strlen($args['term']))
			)
			{
				$term['match'] = true;
			}


			// If we have an t3_id, it is the oldest parent.
			// We don't show this if we're doing a "parent_id"
			// lookup and it matches t2
			if (
				strlen($term['t3_id']) &&
				(!isset($args['parent_id']) ||
				$args['parent_id'] != $term['t2_id'])
			)
			{
				if (!is_array($children[$term['t3_id']]))
				{
					$children[$term['t3_id']] = array();
				}

				if (!in_array($term['t3_id'], $terms_done))
				{

					$terms[] = array(
						'id' => $term['t3_id'],
						'term' => $term['t3_term'],
						'article' => $term['t3_article'],
						'section' => $term['t3_section'],
						'children' => &$children[$term['t3_id']]
					);

					$terms_done[] = $term['t3_id'];
				}
			}
			// ... or, if there's an t2_id, that's the oldest parent.
			if (strlen($term['t2_id'])) {
				if (!is_array($children[$term['t2_id']]))
				{
					$children[$term['t2_id']] = array();
				}

				if (!in_array($term['t2_id'], $terms_done))
				{

					$t2_term = array(
						'id' => $term['t2_id'],
						'term' => $term['t2_term'],
						'article' => $term['t2_article'],
						'section' => $term['t2_section'],
						'children' => &$children[$term['t2_id']]
					);

					if (
						isset($children[$term['t3_id']])
					)
					{
						$children[$term['t3_id']][] = $t2_term;
					}
					else
					{
						$terms[] = $t2_term;
					}

					$terms_done[] = $term['t2_id'];
				}
			}

			// ... otherwise, this is a top-level element.
			if (!in_array($term['id'], $terms_done))
			{
				if (!isset($children[$term['id']]))
				{
					$children[$term['id']] = array();
				}
				$term['children'] = &$children[$term['id']];

				if(strlen($term['t2_id']))
				{
					$children[$term['t2_id']][] = $term;
				}
				else
				{
					$terms[] = $term;
				}

				$terms_done[] = $term['id'];
			}
		}

		return $terms;
	}

	/**
	 * Get a single term by it's id.
	 */
	public function get_term_by_id($id)
	{
		$query = 'SELECT * FROM term_index WHERE id = :id';
		$query_args = array(':id' => $id);

		// We're using a prepared statement, so we only need one instance of it here.
		static $statement;
		if (!$statement)
		{
			$statement = $this->db->prepare($query);
		}

		$result = $statement->execute($query_args);

		if ($result === FALSE)
		{
			return FALSE;
		}
		else
		{
			return $statement->fetch();
		}
	}
}