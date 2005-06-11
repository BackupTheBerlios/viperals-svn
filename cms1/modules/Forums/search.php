<?php
// -------------------------------------------------------------
//
// $Id: search.php,v 1.108 2004/10/19 19:20:30 acydburn Exp $
//
// FILENAME  : search.php 
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

//TODO
//Introduce phrase searching?
//Stemmers?
//Find similar?
//Relevancy?

$_CLASS['core_user']->add_lang('search');
$_CLASS['core_user']->add_img();

// Is user able to search? Has search been disabled?
if ($_CLASS['core_user']->is_bot || !$_CLASS['auth']->acl_get('u_search') || !$config['load_search'])
{
	trigger_error('NO_SEARCH');
}

// Define initial vars
$mode		= request_var('mode', '');
$search_id	= request_var('search_id', '');
$start		= request_var('start', 0);
$post_id	= request_var('p', 0);
$view		= request_var('view', '');

$search_keywords	= request_var('search_keywords', '');
$search_author		= request_var('search_author', '');
$show_results		= request_var('show_results', 'posts');
$search_terms		= request_var('search_terms', 'all');
$search_fields		= request_var('search_fields', 'all');
$search_child		= request_var('search_child', true);

$return_chars	= request_var('return_chars', 200);
$search_forum	= request_var('search_forum', 0);

$sort_days	= request_var('st', 0);
$sort_key	= request_var('sk', 't');
$sort_dir	= request_var('sd', 'd');

$_CLASS['core_template']->assign(array(
	'L_SEARCH_QUERY'				=> $_CLASS['core_user']->lang['SEARCH_QUERY'],
	'L_SEARCH_KEYWORDS'				=> $_CLASS['core_user']->lang['SEARCH_KEYWORDS'],
	'L_SEARCH_KEYWORDS_EXPLAIN'		=> $_CLASS['core_user']->lang['SEARCH_KEYWORDS_EXPLAIN'],
	'L_SEARCH_ALL_TERMS'			=> $_CLASS['core_user']->lang['SEARCH_ALL_TERMS'],
	'L_SEARCH_ANY_TERMS'			=> $_CLASS['core_user']->lang['SEARCH_ANY_TERMS'],
	'L_SEARCH_AUTHOR'				=> $_CLASS['core_user']->lang['SEARCH_AUTHOR'],
	'L_SEARCH_AUTHOR_EXPLAIN'		=> $_CLASS['core_user']->lang['SEARCH_AUTHOR_EXPLAIN'],
	'L_SEARCH_FORUMS'				=> $_CLASS['core_user']->lang['SEARCH_FORUMS'],
	'L_SEARCH_FORUMS_EXPLAIN'		=> $_CLASS['core_user']->lang['SEARCH_FORUMS_EXPLAIN'],
	'L_SEARCH_OPTIONS'				=> $_CLASS['core_user']->lang['SEARCH_OPTIONS'],
	'L_SEARCH_SUBFORUMS'			=> $_CLASS['core_user']->lang['SEARCH_SUBFORUMS'],
	'L_SEARCH_WITHIN'				=> $_CLASS['core_user']->lang['SEARCH_WITHIN'],
	'L_YES'							=> $_CLASS['core_user']->lang['YES'],
	'L_NO'							=> $_CLASS['core_user']->lang['NO'],
	'L_SEARCH_TITLE_MSG'			=> $_CLASS['core_user']->lang['SEARCH_TITLE_MSG'],
	'L_SEARCH_MSG_ONLY'				=> $_CLASS['core_user']->lang['SEARCH_MSG_ONLY'],
	'L_SEARCH_TITLE_ONLY'			=> $_CLASS['core_user']->lang['SEARCH_TITLE_ONLY'],
	'L_RESULT_SORT'					=> $_CLASS['core_user']->lang['RESULT_SORT'],
	'L_SORT_ASCENDING'				=> $_CLASS['core_user']->lang['SORT_ASCENDING'],
	'L_SORT_DESCENDING'				=> $_CLASS['core_user']->lang['SORT_DESCENDING'],
	'L_DISPLAY_RESULTS'				=> $_CLASS['core_user']->lang['DISPLAY_RESULTS'],
	'L_POSTS'						=> $_CLASS['core_user']->lang['POSTS'],
	'L_TOPICS'						=> $_CLASS['core_user']->lang['TOPICS'],
	'L_RESULT_DAYS'					=> $_CLASS['core_user']->lang['RESULT_DAYS'],
	'L_RETURN_FIRST'				=> $_CLASS['core_user']->lang['RETURN_FIRST'],
	'L_POST_CHARACTERS'				=> $_CLASS['core_user']->lang['POST_CHARACTERS'],
	'L_RESET'						=> $_CLASS['core_user']->lang['RESET'],
	'L_NO_RECENT_SEARCHES'			=> $_CLASS['core_user']->lang['NO_RECENT_SEARCHES'],

	'L_SEARCHED_FOR'				=> $_CLASS['core_user']->lang['SEARCHED_FOR'],
	'L_IGNORED_TERMS'				=> $_CLASS['core_user']->lang['IGNORED_TERMS'],
	'L_SEARCH_IN_RESULTS'			=> $_CLASS['core_user']->lang['SEARCH_IN_RESULTS'],
	'L_SEARCH_TITLE_ONLY'			=> $_CLASS['core_user']->lang['SEARCH_TITLE_ONLY'],
	'L_AUTHOR'						=> $_CLASS['core_user']->lang['AUTHOR'],
	'L_GO'							=> $_CLASS['core_user']->lang['GO'],
	'L_REPLIES'						=> $_CLASS['core_user']->lang['REPLIES'],
	'L_VIEWS'						=> $_CLASS['core_user']->lang['VIEWS'],
	'L_LAST_POST'					=> $_CLASS['core_user']->lang['LAST_POST'],
	'L_SORT_BY'						=> $_CLASS['core_user']->lang['SORT_BY'],
	'L_JUMP_TO'						=> 'Jump To',

	'L_MESSAGE'						=> $_CLASS['core_user']->lang['MESSAGE'],
	'L_FORUM'						=> $_CLASS['core_user']->lang['FORUM'],
	'L_TOPIC'						=> $_CLASS['core_user']->lang['TOPIC'],
	'L_POST_SUBJECT'				=> $_CLASS['core_user']->lang['POST_SUBJECT'],
	'L_POSTED'						=> $_CLASS['core_user']->lang['POSTED'],
	'L_REPLIES'						=> $_CLASS['core_user']->lang['REPLIES'],
	'L_VIEWS'						=> $_CLASS['core_user']->lang['VIEWS'],
	'L_LAST_POST'					=> $_CLASS['core_user']->lang['LAST_POST'],
	'L_SORT_BY'						=> $_CLASS['core_user']->lang['SORT_BY'],
	
	'L_RESULT_DAYS'					=> $_CLASS['core_user']->lang['RESULT_DAYS'],
	'L_RETURN_FIRST'				=> $_CLASS['core_user']->lang['RETURN_FIRST'],
	'L_POST_CHARACTERS'				=> $_CLASS['core_user']->lang['POST_CHARACTERS'],
	'L_RESET'						=> $_CLASS['core_user']->lang['RESET'],
	'L_NO_RECENT_SEARCHES'			=> $_CLASS['core_user']->lang['NO_RECENT_SEARCHES'],
	'L_RECENT_SEARCHES'				=> $_CLASS['core_user']->lang['RECENT_SEARCHES'])
);

// Define some vars
$limit_days		= array(0 => $_CLASS['core_user']->lang['ALL_RESULTS'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 364 => $_CLASS['core_user']->lang['1_YEAR']);
$sort_by_text	= array('a' => $_CLASS['core_user']->lang['SORT_AUTHOR'], 't' => $_CLASS['core_user']->lang['SORT_TIME'], 'f' => $_CLASS['core_user']->lang['SORT_FORUM'], 'i' => $_CLASS['core_user']->lang['SORT_TOPIC_TITLE'], 's' => $_CLASS['core_user']->lang['SORT_POST_SUBJECT']);

$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

$store_vars		= array('sort_key', 'sort_dir', 'sort_days', 'show_results', 'return_chars', 'total_match_count');
$current_time	= time();

// Check last search time ... if applicable
if ($config['search_interval'])
{
	$sql = 'SELECT MAX(search_time) as last_time
		FROM ' . SEARCH_TABLE."
			WHERE session_id = '" . $_CLASS['core_db']->sql_escape($_CLASS['core_user']->data['session_id']) . '\'';
	$result = $_CLASS['core_db']->sql_query($sql);

	if ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		if ($row['last_time'] > time() - $config['search_interval'])
		{
			trigger_error('NO_SEARCH_TIME');
		}
	}
}

if ($search_keywords || $search_author || $search_id)
{
	$post_id_ary = $split_words = $old_split_words = $common_words = array();

	// Which forums can we view?
	$sql_where = (sizeof($search_forum) && !$search_child) ? 'WHERE f.forum_id IN (' . implode(', ', $search_forum) . ')' : '';
	$sql = 'SELECT f.forum_id, f.forum_name, f.parent_id, f.forum_type, f.right_id, f.forum_password, fa.user_id
		FROM (' . FORUMS_TABLE . ' f 
		LEFT JOIN ' . FORUMS_ACCESS_TABLE . " fa ON  (fa.forum_id = f.forum_id 
			AND fa.session_id = '" . $_CLASS['core_db']->sql_escape($_CLASS['core_user']->data['session_id']) . "')) 
		$sql_where
		ORDER BY f.left_id";
	$result = $_CLASS['core_db']->sql_query($sql);

	$right_id = 0;
	$sql_forums = array();
	while ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		if ($search_child)
		{
			if (!$search_forum || (in_array($row['forum_id'], $search_forum) && $row['right_id'] > $right_id))
			{
				$right_id = $row['right_id'];
			}
			else if ($row['right_id'] > $right_id)
			{
				continue;
			}
		}

		if ($_CLASS['auth']->acl_get('f_read', $row['forum_id']) && (!$row['forum_password'] || $row['user_id'] == $_CLASS['core_user']->data['user_id']))
		{
			$sql_forums[] = $row['forum_id'];
		}
	}
	$_CLASS['core_db']->sql_freeresult($result);

	if (!sizeof($sql_forums))
	{
		trigger_error('NO_SEARCH_RESULTS');
	}

	$sql_forums = ' AND p.forum_id IN (' . implode(', ', $sql_forums) . ')';
	unset($search_forum);


	if ($search_id == 'egosearch')
	{
		$search_author = $_CLASS['core_user']->data['username'];
	}


	// Are we looking for a user?
	$sql_author = '';
	if ($search_author)
	{
		$sql_where = (strstr($search_author, '*') !== false) ? ' LIKE ' : ' = ';
		$sql = 'SELECT user_id 
			FROM ' . USERS_TABLE . "
			WHERE username $sql_where '" . $_CLASS['core_db']->sql_escape(preg_replace('#\*+#', '%', $search_author)) . "'
				AND user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')';
		$result = $_CLASS['core_db']->sql_query($sql);

		if (!$row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			trigger_error('NO_SEARCH_RESULTS');
		}
		$_CLASS['core_db']->sql_freeresult($result);

		$sql_author = ' p.poster_id = ' . $row['user_id'];
	}

	
	if ($search_id)
	{
		$stopped_words = array();

		switch ($search_id)
		{
			case 'egosearch':
				break;

			case 'unanswered':
				if ($show_results == 'posts')
				{
					$sql = 'SELECT p.post_id 
						FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . " t 
						WHERE t.topic_replies = 0
							AND p.topic_id = t.topic_id
							$sql_forums";
					$field = 'post_id';
				}
				else
				{
					$sql = 'SELECT t.topic_id 
						FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . " t 
						WHERE t.topic_replies = 0 
							AND p.topic_id = t.topic_id
							$sql_forums
						GROUP BY p.topic_id";
					$field = 'topic_id';
				}
				$result = $_CLASS['core_db']->sql_query($sql);

				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$post_id_ary[] = $row[$field];
				}
				$_CLASS['core_db']->sql_freeresult($result);

				if (!sizeof($post_id_ary))
				{
					trigger_error('NO_SEARCH_RESULTS');
				}
				break;

			case 'newposts':
				if ($show_results == 'posts')
				{
					$sql = 'SELECT p.post_id 
						FROM ' . POSTS_TABLE . ' p 
						WHERE p.post_time > ' . $_CLASS['core_user']->data['user_lastvisit'] . "
							$sql_forums";
					$field = 'post_id';
				}
				else
				{
					$sql = 'SELECT t.topic_id
						FROM ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . ' p 
						WHERE p.post_time > ' . $_CLASS['core_user']->data['user_lastvisit'] . " 
							AND t.topic_id = p.topic_id 
							$sql_forums 
						GROUP by p.topic_id";
					$field = 'topic_id';
				}
				$result = $_CLASS['core_db']->sql_query($sql);

				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$post_id_ary[] = $row[$field];
				}
				$_CLASS['core_db']->sql_freeresult($result);

				if (!sizeof($post_id_ary))
				{
					trigger_error('NO_SEARCH_RESULTS');
				}
				break;

			default:
				$search_id = (int) $search_id;

				$sql = 'SELECT search_array
					FROM ' . SEARCH_TABLE . "
					WHERE search_id = $search_id
						AND session_id = '" . $_CLASS['core_db']->sql_escape($_CLASS['core_user']->data['session_id']) . "'";
				$result = $_CLASS['core_db']->sql_query($sql);

				if ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$data = explode('#', $row['search_array']);

					$split_words = unserialize(array_shift($data));
					if ($search_keywords)
					{
						// If we're wanting to search on these results we store the existing split word array
						$old_split_words = $split_words;
					}
					$stopped_words = unserialize(array_shift($data));
					foreach ($store_vars as $var)
					{
						$$var = array_shift($data);
					}

					$sql_where = (($show_results == 'posts') ? 'p.post_id' : 't.topic_id') . ' IN (' . implode(', ', $data) . ')';
					unset($data);
				}
				$_CLASS['core_db']->sql_freeresult($result);
		}
	}


	// Are we looking for words
	if ($search_keywords)
	{
		$sql_author = ($sql_author) ? ' AND ' . $sql_author : '';

		$split_words = $stopped_words = $smllrg_words = array();
		$drop_char_match =   array('-', '^', '$', ';', '#', '&', '(', ')', '<', '>', '`', '\'', '"', '|', ',', '@', '_', '?', '%', '~', '.', '[', ']', '{', '}', ':', '\\', '/', '=', '\'', '!', '*');
		$drop_char_replace = array(' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', '',  '',   ' ', ' ', ' ', ' ', '',  ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', '' ,  ' ', ' ', ' ',  ' ', ' ');

		if ($fp = @fopen($_CLASS['core_user']->lang_path . '/search_stopwords.txt', 'rb'))
		{
			$stopwords = explode("\n", str_replace("\r\n", "\n", fread($fp, filesize($_CLASS['core_user']->lang_path . '/search_stopwords.txt'))));
		}
		fclose($fp);

		if ($fp = @fopen($_CLASS['core_user']->lang_path . '/search_synonyms.txt', 'rb'))
		{
			preg_match_all('#^(.*?) (.*?)$#ms', fread($fp, filesize($_CLASS['core_user']->lang_path . '/search_synonyms.txt')), $match);
			$replace_synonym = &$match[1];
			$match_synonym = &$match[2];
		}
		fclose($fp);

		$match		= array('#\sand\s#i', '#\sor\s#i', '#\snot\s#i', '#\+#', '#-#', '#\|#');
		$replace	= array(' + ',        ' | ',       ' - ',        ' + ',  ' - ', ' | ');

		$search_keywords = preg_replace($match, $replace, $search_keywords);

		$match = array();
		// Comments for hardcoded bbcode elements (urls, smilies, html)
		$match[] = '#<!\-\- .* \-\->(.*?)<!\-\- .* \-\->#is';
		// New lines, carriage returns
		$match[] = "#[\n\r]+#";
		// NCRs like &nbsp; etc.
		$match[] = '#(&amp;|&)[\#a-z0-9]+?;#i';
		// BBcode
		$match[] = '#\[\/?[a-z\*\+\-]+(=.*)?(\:?[0-9a-z]{5,})\]#';

		// Filter out as above
		$search_keywords = preg_replace($match, ' ', strtolower(trim($search_keywords)));
		$search_keywords = str_replace($drop_char_match, $drop_char_replace, $search_keywords);

		// Split words
		$split_words = explode(' ', preg_replace('#\s+#', ' ', $search_keywords));

		if (sizeof($stopwords))
		{
			$stopped_words = array_intersect($split_words, $stopwords);
			$split_words = array_diff($split_words, $stopwords);
		}

		if (sizeof($replace_synonym))
		{
			$split_words = str_replace($replace_synonym, $match_synonym, $split_words);
		}
	}

	if (isset($old_split_words) && sizeof($old_split_words))
	{
		$split_words = (sizeof($split_words)) ? array_diff($split_words, $old_split_words) : $old_split_words;
	}
	
	if (sizeof($split_words))
	{


		// This "entire" section may be switched out to allow for alternative search systems
		// such as that built-in to MySQL, MSSQL, etc. or external solutions which provide
		// an appropriate API

		$bool = ($search_terms == 'all') ? 'AND' : 'OR';
		$sql_words = '';
		foreach ($split_words as $word)
		{
			switch ($word)
			{
				case '-':
					$bool = 'NOT';
					continue;
				case '+':
					$bool = 'AND';
					continue;
				case '|':
					$bool = 'OR';
					continue;
				default:
					$bool = ($search_terms != 'all') ? 'OR' : $bool;
					$sql_words[$bool][] = "'" . preg_replace('#\*+#', '%', trim($word)) . "'";
					$bool = ($search_terms == 'all') ? 'AND' : 'OR';
			}
		}

		switch ($search_fields)
		{
			case 'titleonly':
				$sql_match = ' AND m.title_match = 1';
				break;
			case 'msgonly':
				$sql_match = ' AND m.title_match = 0';
				break;
			default:
				$sql_match = '';
		}

		// Build some display specific variable strings
		$sql_select = ($show_results == 'posts') ? 'm.post_id' : 'DISTINCT t.topic_id';
		$sql_from = ($show_results == 'posts') ? '' : TOPICS_TABLE . ' t, ';
		$sql_topic = ($show_results == 'posts') ? '' : 'AND t.topic_id = p.topic_id';
		$sql_time = ($sort_days) ? 'AND p.post_time >= ' . ($current_time - ($sort_days * 86400)) : '';
		$field = ($show_results == 'posts') ? 'm.post_id' : 't.topic_id';

		// Are we searching within an existing search set? Yes, then include the old ids
		$sql_find_in = ($sql_where) ? "AND $sql_where" : '';

		$result_ary = array();
		foreach (array('AND', 'OR', 'NOT') as $bool)
		{
			if (isset($sql_words[$bool]) && is_array($sql_words[$bool]))
			{
				switch ($bool)
				{
					case 'AND':
					case 'NOT':
						foreach ($sql_words[$bool] as $word)
						{
							if (strlen($word) < 4)
							{
								continue;
							}

							$sql_where = (strstr($word, '%')) ? "LIKE $word" : "= $word";

							$sql_and = (isset($result_ary['AND']) && sizeof($result_ary['AND'])) ? "AND $field IN (" . implode(', ', $result_ary['AND']) . ')' : '';

							$sql = "SELECT $sql_select 
								FROM $sql_from" . POSTS_TABLE . ' p, ' . SEARCH_MATCH_TABLE . ' m, ' . SEARCH_WORD_TABLE . " w 
								WHERE w.word_text $sql_where 
									AND m.word_id = w.word_id 
									AND w.word_common <> 1 
									AND p.post_id = m.post_id
									$sql_topic 
									$sql_forums 
									$sql_author 
									$sql_and 
									$sql_time 
									$sql_match
									$sql_find_in";
							$result = $_CLASS['core_db']->sql_query($sql);

							if (!($row = $_CLASS['core_db']->sql_fetchrow($result)) && $bool == 'AND')
							{
								trigger_error('NO_SEARCH_RESULTS');
							}

							if ($bool == 'AND')
							{
								$result_ary['AND'] = array();
							}

							do
							{
								$result_ary[$bool][] = ($show_results == 'topics') ? $row['topic_id'] : $row['post_id'];
							}
							while ($row = $_CLASS['core_db']->sql_fetchrow($result));
							$_CLASS['core_db']->sql_freeresult($result);
						}
						break;

					case 'OR':
						$sql_where = $sql_in = '';
						foreach ($sql_words[$bool] as $word)
						{
							if (strlen($word) < 4)
							{
								continue;
							}

							if (strstr($word, '%'))
							{
								$sql_where .= (($sql_where) ? ' OR w.word_text ' : 'w.word_text ') . "LIKE $word";
							}
							else
							{
								$sql_in .= (($sql_in) ? ', ' : '') . $word;
							}
						}
						$sql_where = ($sql_in) ? (($sql_where) ? ' OR ' : '') . 'w.word_text IN (' . $sql_in . ')' : $sql_where;

						$sql_and = (sizeof($result_ary['AND'])) ? "AND $field IN (" . implode(', ', $result_ary['AND']) . ')' : '';
						$sql = "SELECT $sql_select 
							FROM $sql_from" . POSTS_TABLE . ' p, ' . SEARCH_MATCH_TABLE . ' m, ' . SEARCH_WORD_TABLE . " w 
							WHERE ($sql_where) 
								AND m.word_id = w.word_id 
								AND w.word_common <> 1 
								AND p.post_id = m.post_id
								$sql_topic 
								$sql_forums 
								$sql_author 
								$sql_and 
								$sql_time 
								$sql_match 
								$sql_find_in";
						$result = $_CLASS['core_db']->sql_query($sql);

						while ($row = $_CLASS['core_db']->sql_fetchrow($result))
						{
							$result_ary[$bool][] = ($show_results == 'topics') ? $row['topic_id'] : $row['post_id'];
						}
						$_CLASS['core_db']->sql_freeresult($result);
						break;
				}
			}
			else
			{
				$sql_words[$bool] = array();
			}
		}

		if (isset($result_ary['OR']) && sizeof($result_ary['OR']))
		{
			$post_id_ary = (isset($result_ary['AND']) && sizeof($result_ary['AND'])) ? array_diff($result_ary['AND'], $result_ary['OR']) : $result_ary['OR'];
		}
		else
		{
			$post_id_ary = (isset($result_ary['AND'])) ? $result_ary['AND'] : array();
		}

		if (isset($result_ary['NOT']) && sizeof($result_ary['NOT']))
		{
			$post_id_ary = (sizeof($post_id_ary)) ? array_diff($post_id_ary, $result_ary['NOT']) : array();
		}
		unset($result_ary);

		$post_id_ary = array_unique($post_id_ary);


		if (!sizeof($post_id_ary))
		{
			trigger_error('NO_SEARCH_RESULTS');
		}

		$sql = 'SELECT word_text 
			FROM ' . SEARCH_WORD_TABLE . ' 
			WHERE word_text IN (' . implode(', ', array_unique(array_merge($sql_words['AND'], $sql_words['OR'], $sql_words['NOT']))) . ')
				AND word_common = 1';
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$common_words[] = $row['word_text'];
		}
		$_CLASS['core_db']->sql_freeresult($result);
	}
	else if ($search_author)
	{
		if ($show_results == 'posts')
		{
			$sql = 'SELECT p.post_id 
				FROM ' . POSTS_TABLE . " p 
				WHERE $sql_author 
					$sql_forums";
			$field = 'post_id';
		}
		else
		{
			$sql = 'SELECT t.topic_id 
				FROM ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . " p 
				WHERE $sql_author
					$sql_forums
					AND t.topic_id = p.topic_id 
				GROUP BY t.topic_id";
			$field = 'topic_id';
		}
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$post_id_ary[] = $row[$field];
		}
		$_CLASS['core_db']->sql_freeresult($result);
	}


	if ($post_id_ary)
	{
		// Finish building query (for all combinations) and run it ...
		$sql = 'SELECT session_id
			FROM ' . SESSIONS_TABLE;
		if ($result = $_CLASS['core_db']->sql_query($sql))
		{
			$delete_search_ids = array();
			while ($row = $_CLASS['core_db']->sql_fetchrow($result))
			{
				$delete_search_ids[] = "'" . $_CLASS['core_db']->sql_escape($row['session_id']) . "'";
			}

			if (sizeof($delete_search_ids))
			{
				$sql = 'DELETE FROM ' . SEARCH_TABLE . '
					WHERE session_id NOT IN (' . implode(", ", $delete_search_ids) . ')';
				$_CLASS['core_db']->sql_query($sql);
			}
		}

		$total_match_count = sizeof($post_id_ary);
		$sql_where = (($show_results == 'posts') ? 'p.post_id' : 't.topic_id') . ' IN (' . implode(', ', $post_id_ary) . ')';

		if (sizeof($old_split_words) && array_diff($split_words, $old_split_words))
		{
			$split_words = array_merge($split_words, $old_split_words);
		}
		$data = serialize(array_diff($split_words, $common_words));
		$data .= '#' . serialize(array_merge($stopped_words, $common_words));
		foreach ($store_vars as $var)
		{
			$data .= '#' . $$var;
		}
		$data .= '#' . implode('#', $post_id_ary);
		unset($post_id_ary);

		srand ((double) microtime() * 1000000);
		$search_id = rand();

		$sql_ary = array(
			'search_id'		=> $search_id,
			'session_id'	=> $_CLASS['core_user']->data['session_id'],
			'search_time'	=> $current_time,
			'search_array'	=> $data
		);

		$sql = 'INSERT INTO ' . SEARCH_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_ary);
		$_CLASS['core_db']->sql_query($sql);
		unset($data);
	}

	if ($show_results == 'posts')
	{
		require($site_file_root.'includes/forums/functions_posting.php');
	}
	else
	{
		require($site_file_root.'includes/forums/functions_display.php');
	}

	// Look up data ...
	$per_page = ($show_results == 'posts') ? $config['posts_per_page'] : $config['topics_per_page'];

	// Grab icons
	$icons = array();
	obtain_icons($icons);

	// Output header
	$l_search_matches = ($total_match_count == 1) ? sprintf($_CLASS['core_user']->lang['FOUND_SEARCH_MATCH'], $total_match_count) : sprintf($_CLASS['core_user']->lang['FOUND_SEARCH_MATCHES'], $total_match_count);

	$hilit = htmlspecialchars(implode('|', str_replace(array('+', '-', '|'), '', $split_words)));

	$split_words = htmlspecialchars(implode(' ', $split_words));
	$ignored_words = htmlspecialchars(implode(' ', $stopped_words));

	$_CLASS['core_template']->assign(array(
		'SEARCH_MATCHES'	=> $l_search_matches,
		'SEARCH_WORDS'		=> $split_words, 
		'IGNORED_WORDS'		=> ($ignored_words) ? $ignored_words : '', 
		'PAGINATION'		=> generate_pagination("Forums&amp;file=&amp;search_id=$search_id&amp;hilit=$hilit&amp;$u_sort_param", $total_match_count, $per_page, $start),
		'PAGE_NUMBER'		=> on_page($total_match_count, $per_page, $start),
		'TOTAL_MATCHES'		=> $total_match_count,

		'S_SELECT_SORT_DIR'		=> $s_sort_dir,
		'S_SELECT_SORT_KEY'		=> $s_sort_key,
		'S_SEARCH_ACTION'		=> generate_link('Forums&amp;file=&amp;search_id='.$search_id),
		'S_SHOW_TOPICS'			=> ($show_results == 'posts') ? false : true,

		'REPORTED_IMG'			=> $_CLASS['core_user']->img('icon_reported', 'TOPIC_REPORTED'),
		'UNAPPROVED_IMG'		=> $_CLASS['core_user']->img('icon_unapproved', 'TOPIC_UNAPPROVED'),
		'GOTO_PAGE_IMG'			=> $_CLASS['core_user']->img('icon_post', 'GOTO_PAGE'),

		'U_SEARCH_WORDS'	=> generate_link("Forums&amp;file=&amp;show_results=$show_results&amp;search_keywords=" . urlencode($split_words)))
	);

	$u_hilit = urlencode($split_words);

	// Define ordering sql field, do it here because the order may be defined
	// within an existing search result set
	$sort_by_sql	= array('a' => (($show_results == 'posts') ? 'u.username' : 't.topic_poster'), 't' => (($show_results == 'posts') ? 'p.post_time' : 't.topic_last_post_time'), 'f' => 'f.forum_id', 'i' => 't.topic_title', 's' => (($show_results == 'posts') ? 'pt.post_subject' : 't.topic_title'));

	if ($show_results == 'posts')
	{
		// Not joining this query to the one below at present ... may do in future
		$sql = 'SELECT zebra_id, friend, foe
			FROM ' . ZEBRA_TABLE . ' 
			WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
		$result = $_CLASS['core_db']->sql_query($sql);

		$zebra = array();
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			if ($row['friend'])
			{
				$zebra['friend'][] = $row['zebra_id'];
			}
			else
			{
				$zebra['foe'][] = $row['zebra_id'];
			}
		}
		$_CLASS['core_db']->sql_freeresult($result);

		$sql = 'SELECT p.*, f.forum_id, f.forum_name, t.*, u.username, u.user_sig, u.user_sig_bbcode_uid
			FROM ' . FORUMS_TABLE . ' f, ' . TOPICS_TABLE . ' t, ' . USERS_TABLE . ' u, ' . POSTS_TABLE . " p 
			WHERE $sql_where 
				AND f.forum_id = p.forum_id
				AND p.topic_id = t.topic_id
				AND p.poster_id = u.user_id";
	}
	else
	{
		$sql = 'SELECT t.*, f.forum_id, f.forum_name
			FROM ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . " f 
			WHERE $sql_where 
				AND f.forum_id = t.forum_id";
	}
	$sql .= ' ORDER BY ' . $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC') . " LIMIT $start, $per_page";
	$result = $_CLASS['core_db']->sql_query($sql);

	while ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		$forum_id = $row['forum_id'];
		$topic_id = $row['topic_id'];

		$view_topic_url = "Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;hilit=$u_hilit";

		if ($show_results == 'topics')
		{
			$replies = ($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? $row['topic_replies_real'] : $row['topic_replies'];

			$folder_img = $folder_alt = $topic_type = '';
			topic_status($row, $replies, time(), time(), $folder_img, $folder_alt, $topic_type);

			$tpl_ary = array(
				'TOPIC_AUTHOR' 		=> topic_topic_author($row),
				'FIRST_POST_TIME' 	=> $_CLASS['core_user']->format_date($row['topic_time']),
				'LAST_POST_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_post_time']),
				'LAST_VIEW_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_view_time']),
				'LAST_POST_AUTHOR' 	=> ($row['topic_last_poster_name'] != '') ? $row['topic_last_poster_name'] : $_CLASS['core_user']->lang['GUEST'],
				'PAGINATION' 		=> topic_generate_pagination($replies, $view_topic_url),
				'REPLIES' 			=> $replies,
				'VIEWS' 			=> $row['topic_views'],
				'TOPIC_TYPE' 		=> $topic_type,

				'LAST_POST_IMG' 	=> $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST'),
				'TOPIC_FOLDER_IMG' 	=> $_CLASS['core_user']->img($folder_img, $folder_alt),
				'TOPIC_ICON_IMG'		=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['img'] : '',
				'TOPIC_ICON_IMG_WIDTH'	=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['width'] : '',
				'TOPIC_ICON_IMG_HEIGHT'	=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['height'] : '',
				'ATTACH_ICON_IMG'       => ($_CLASS['auth']->acl_gets('f_download', 'u_download', $forum_id) && $row['topic_attachment']) ? $_CLASS['core_user']->img('icon_attach', $_CLASS['core_user']->lang['TOTAL_ATTACHMENTS']) : '',
				'S_TOPIC_TYPE'			=> $row['topic_type'],
				'S_USER_POSTED'			=> (!empty($row['mark_type'])) ? true : false,

				'S_TOPIC_REPORTED'		=> (!empty($row['topic_reported']) && $_CLASS['auth']->acl_gets('m_', $forum_id)) ? true : false,
				'S_TOPIC_UNAPPROVED'	=> (!$row['topic_approved'] && $_CLASS['auth']->acl_gets('m_approve', $forum_id)) ? true : false,

				'U_LAST_POST'		=> generate_link($view_topic_url . '&amp;p=' . $row['topic_last_post_id'] . '#' . $row['topic_last_post_id'], false),
				'U_LAST_POST_AUTHOR'=> ($row['topic_last_poster_id'] != ANONYMOUS && $row['topic_last_poster_id']) ? generate_link('Members_List&amp;mode=viewprofile&amp;u='.$row['topic_last_poster_id']) : '',
				'U_MCP_REPORT'		=> generate_link('Forums&amp;file=mcp&amp;mode=reports&amp;t='.$topic_id),
				'U_MCP_QUEUE'		=> generate_link('Forums&amp;file=mcp&amp;i=queue&amp;mode=approve_details&amp;t='.$topic_id)
			);
		}
		else
		{
			if ((isset($zebra['foe']) && in_array($row['poster_id'], $zebra['foe'])) && (!$view || $view != 'show' || $post_id != $row['post_id']))
			{
				$_CLASS['core_template']->assign_vars_array('searchresults', array(
					'S_IGNORE_POST' => true, 

					'L_IGNORE_POST' => sprintf($_CLASS['core_user']->lang['POST_BY_FOE'], $row['username'], '<a href="'.generate_link("Forums&amp;file=search&amp;search_id=$search_id&amp;$u_sort_param&amp;p=" . $row['post_id'] . '&amp;view=show#' . $row['post_id']) . '">', '</a>'))
				);

				continue;
			}

			if ($row['enable_html'])
			{
				$row['post_text'] = preg_replace('#(<!\-\- h \-\-><)([\/]?.*?)(><!\-\- h \-\->)#is', "&lt;\\2&gt;", $row['post_text']);
			}

			$row['post_text'] = censor_text($row['post_text']);

			decode_message($row['post_text'], $row['bbcode_uid']);
		
			if ($return_chars)
			{
				$row['post_text'] = (strlen($row['post_text']) < $return_chars + 3) ? $row['post_text'] : substr($row['post_text'], 0, $return_chars) . '...';
			}

			// This was shamelessly 'borrowed' from volker at multiartstudio dot de
			// via php.net's annotated manual
			$row['post_text'] = str_replace('\"', '"', substr(preg_replace('#(\>(((?>([^><]+|(?R)))*)\<))#se', "preg_replace('#\b(" . $hilit . ")\b#i', '<span class=\"posthilit\">\\\\1</span>', '\\0')", '>' . $row['post_text'] . '<'), 1, -1));

			$row['post_text'] = smiley_text($row['post_text']);

			$tpl_ary = array(
				'POSTER_NAME'		=> ($row['poster_id'] == ANONYMOUS) ? ((!empty($row['post_username'])) ? $row['post_username'] : $_CLASS['core_user']->lang['GUEST']) : $row['username'], 
				'POST_SUBJECT'		=> censor_text($row['post_subject']), 
				'POST_DATE'			=> (!empty($row['post_time'])) ? $_CLASS['core_user']->format_date($row['post_time']) : '', 
				'MESSAGE' 			=> (!empty($row['post_text'])) ? str_replace("\n", '<br />', $row['post_text']) : ''
			);
		}

		$_CLASS['core_template']->assign_vars_array('searchresults', array_merge($tpl_ary, array(
			'FORUM_ID' 			=> $forum_id,
			'TOPIC_ID' 			=> $topic_id,
			'POST_ID'			=> ($show_results == 'posts') ? $row['post_id'] : false, 

			'FORUM_TITLE'		=> $row['forum_name'], 
			'TOPIC_TITLE' 		=> censor_text($row['topic_title']),

			'U_VIEW_TOPIC'		=> generate_link($view_topic_url),
			'U_VIEW_FORUM'		=> generate_link('Forums&amp;file=viewforum&amp;f='.$forum_id), 
			'U_VIEW_POST'		=> (!empty($row['post_id'])) ? generate_link("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=" . $row['topic_id'] . '&amp;p=' . $row['post_id'] . '&amp;hilit=' . $u_hilit . '#' . $row['post_id'], false, false) : '')
		));
	}
	$_CLASS['core_db']->sql_freeresult($result);

	$_CLASS['core_display']->display_head($_CLASS['core_user']->lang['SEARCH']);
	
	page_header();
	
	make_jumpbox(generate_link('Forums&amp;file=viewforum'));
	
	$_CLASS['core_template']->display('modules/Forums/search_results.html');
	 
	$_CLASS['core_display']->display_footer();

}


// Search forum
$s_forums = '';
$sql = 'SELECT f.forum_id, f.forum_name, f.parent_id, f.forum_type, f.left_id, f.right_id, f.forum_password, fa.user_id
	FROM (' . FORUMS_TABLE . ' f 
	LEFT JOIN ' . FORUMS_ACCESS_TABLE . " fa ON  (fa.forum_id = f.forum_id 
		AND fa.session_id = '" . $_CLASS['core_db']->sql_escape($_CLASS['core_user']->data['session_id']) . "')) 
	ORDER BY f.left_id ASC";
$result = $_CLASS['core_db']->sql_query($sql);

$right = $cat_right = $padding_inc = 0;
$padding = $forum_list = $holding = '';
$pad_store = array('0' => '');
$search_forums = array();
while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	if ($row['forum_type'] == FORUM_CAT && ($row['left_id'] + 1 == $row['right_id']))
	{
		// Non-postable forum with no subforums, don't display
		continue;
	}

	if (!$_CLASS['auth']->acl_get('f_list', $row['forum_id']) || $row['forum_type'] == FORUM_LINK || ($row['forum_password'] && !$row['user_id']))
	{
		// if the user does not have permissions to list this forum skip
		continue;
	}

	if ($row['left_id'] < $right)
	{
		$padding .= '&nbsp; &nbsp;';
		$pad_store[$row['parent_id']] = $padding;
	}
	else if ($row['left_id'] > $right + 1)
	{
		$padding = $pad_store[$row['parent_id']];
	}

	$right = $row['right_id'];

	$selected = (!sizeof($search_forums) || in_array($row['forum_id'], $search_forums)) ? ' selected="selected"' : '';

	if ($row['left_id'] > $cat_right)
	{
		$holding = '';
	}

	if ($row['right_id'] - $row['left_id'] > 1)
	{
		$cat_right = max($cat_right, $row['right_id']);

		$holding .= '<option value="' . $row['forum_id'] . '"' . $selected . '>' . $padding . $row['forum_name'] . '</option>';
	}
	else
	{
		$s_forums .= $holding . '<option value="' . $row['forum_id'] . '"' . $selected . '>' . $padding . $row['forum_name'] . '</option>';
		$holding = '';
	}
}
$_CLASS['core_db']->sql_freeresult($result);
unset($pad_store);

// Number of chars returned
$s_characters = '<option value="-1">' . $_CLASS['core_user']->lang['ALL_AVAILABLE'] . '</option>';
$s_characters .= '<option value="0">0</option>';
$s_characters .= '<option value="25">25</option>';
$s_characters .= '<option value="50">50</option>';

for($i = 100; $i <= 1000 ; $i += 100)
{
	$selected = ($i == 200) ? ' selected="selected"' : '';
	$s_characters .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
}

$_CLASS['core_template']->assign(array(
	'S_SEARCH_ACTION'		=> generate_link('Forums&amp;file=search&amp;mode=results'),
	'S_CHARACTER_OPTIONS'	=> $s_characters,
	'S_FORUM_OPTIONS'		=> $s_forums,
	'S_SELECT_SORT_DIR'		=> $s_sort_dir,
	'S_SELECT_SORT_KEY'		=> $s_sort_key,
	'S_SELECT_SORT_DAYS'	=> $s_limit_days)
);

$sql = 'SELECT search_id, search_time, search_array 
	FROM ' . SEARCH_TABLE . '
	ORDER BY search_time DESC';
$result = $_CLASS['core_db']->sql_query($sql);

$i = 0;
while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	if ($i == 5)
	{
		break;
	}

	$data = explode('#', $row['search_array']);
	$split_words = htmlspecialchars(implode(' ', unserialize(array_shift($data))));

	if (!$split_words)
	{
		continue;
	}

	$stopped_words = htmlspecialchars(implode(' ', unserialize(array_shift($data))));
	unset($data);

	$_CLASS['core_template']->assign_vars_array('recentsearch', array(
		'KEYWORDS'	=> $split_words,
		'TIME'		=> $_CLASS['core_user']->format_date($row['search_time']), 

		'U_KEYWORDS'	=> generate_link('Forums&amp;file=search&amp;search_keywords=' . urlencode($split_words)))
	);

	$i++;
}
$_CLASS['core_db']->sql_freeresult($result);

// Output the basic page
$_CLASS['core_display']->display_head($_CLASS['core_user']->lang['SEARCH']);

page_header();

make_jumpbox(generate_link('Forums&amp;file=viewforum'));

$_CLASS['core_template']->display('modules/Forums/search_body.html');
 
$_CLASS['core_display']->display_footer();
	
?>