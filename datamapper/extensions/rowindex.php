<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * DataMapper extension - rowindex methods
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

class DataMapper_Rowindex
{
	/**
	 * given an already-built query and an object's ID, determine what row
	 * that object has in the query
	 *
	 * @param	DataMapper			$dmobject		the DataMapper object
	 * @param	DataMapper|array	$keys			the keys of the record, or object to look for
	 * @param	array				$leave_select	a list of items to leave in the selection array, overriding the automatic removal
	 * @param	bool				$distinct_on	if TRUE, use DISTINCT ON (not all DBs support this)
	 *
	 * @return	int					returns the index of the item, or FALSE if none are found
	 */
	public static function row_index($dmobject, $keys, $leave_select = array(), $distinct_on = FALSE)
	{
		// get the first row index
		$result = self::get_rowindices($dmobject, $keys, $leave_select, $distinct_on, TRUE);

		// check if we have a result
		if ( empty($result) )
		{
			return FALSE;
		}
		else
		{
			reset($result);
			return key($result);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * given an already-built query and an object's ID, determine what row
	 * that object has in the query
	 *
	 * @param	DataMapper	$dmobject		the DataMapper object
	 * @param	array		$keys 			the keys or object to look for
	 * @param	array		$leave_select	a list of items to leave in the selection array, overriding the automatic removal
	 * @param	bool		#distinct_on	if TRUE, use DISTINCT ON (not all DBs support this)
	 * @param	bool		$first_only		Internal use only. if TRUE, only return the first index
	 *
	 * @return	array	returns an array of row indices.
	 */
	public static function row_indices($dmobject, Array $keys, $leave_select = array(), $distinct_on = FALSE, $first_only = FALSE)
	{
		// some storage for the result
		$row_indices = array();

		// build the array of keys we're looking for
		$new_keys = array();

		foreach ( $keys as $key => $value )
		{
			// is this a DataMapper object?
			if ( $key instanceOf DataMapper )
			{
				// fetch the object's key values
				$new_keys[] = $key->dm_get_keys();
			}

			// is this a keys array?
			elseif ( is_array($value) )
			{
				$new_keys[] = $value;
			}

			// else bail out
			else
			{
				throw new DataMapper_Exception("DataMapper RowIndex extension: incorrect keys definition passed");
			}

		}

		// make sure $leave_select is an array
		is_array($leave_select) OR $leave_select = array();

		// duplicate the DataMapper object to ensure the query isn't wiped out
		$object = $dmobject->get_clone(TRUE);

		// remove the unecessary columns
		$sort_columns = self::orderlist($object->db->ar_orderby);
		$ar_select = array();

		if ( empty($sort_columns) AND empty($leave_select) )
		{
			// no sort columns, so just wipe it out
			$object->db->ar_select = NULL;
		}
		else
		{
			// loop through the ar_select, and remove columns that
			// are not specified by sorting
			$select = self::splitselect(implode(', ', $object->db->ar_select));

			// find all aliases (they are all we care about)
			foreach ( $select as $alias => $sel )
			{
				if ( in_array($alias, $sort_columns) OR in_array($alias, $leave_select) )
				{
					$ar_select[] = $sel;
				}
			}
			$object->db->ar_select = NULL;
		}

		// get the list of key fields
		$keys = '';
		$row_keys = array();
		foreach ( $object->dm_get_config('keys') as $key => $unused )
		{
			$keys .= ( empty($keys) ? '' : ', ' ) . $object->add_table_name($key);
			$row_keys[$key] = NULL;
		}

		// add the DISTINCT ON if needed
		if ( $distinct_on )
		{
			// to ensure unique items we must DISTINCT ON the same list as the ORDER BY list.
			$distinct = 'DISTINCT ON (' . preg_replace("/\s+(asc|desc)/i", "", implode(",", $object->db->ar_orderby)) . ') ';

			// add in the DISTINCT ON and the $table.$keys columns.  The FALSE prevents the items from being escaped
			$object->select($distinct . $keys, FALSE);
		}
		else
		{
			// select the keys only
			$object->select($keys);
		}

		// this ensures that the DISTINCT ON is first, since it must be
		$object->db->ar_select = array_merge($object->db->ar_select, $ar_select);

		// run the query
		$query = $object->get_raw();

		// and process the results
		foreach ( $query->result() as $index => $row )
		{
			// get the key values for this row
			foreach ( $row_keys as $key => $value )
			{
				$row_keys[$key] = $row->{$key};
			}

			// is this a row we're intrested in?
			if ( in_array($row_keys, $new_keys) )
			{
				// store the result
				$row_indices[$index] = $row_keys;

				// bail out if we only need one
				if ( $first_only )
				{
					break;
				}
			}
		}

		// in case the user wants to know
		$dmobject->rowindex_total_rows = $query->num_rows();

		// return results
		return $row_indices;
	}

	// --------------------------------------------------------------------

	/**
	 * processes the order_by array, and converts it into a list
	 * of non-fully-qualified columns. These might be aliases
	 *
	 * @ignore
	 *
	 * @param	array	$order_by	original order_by array
	 *
	 * @return	array	modified array
	 */
	protected static function orderlist($order_by)
	{
		$list = array();
		$impt_parts_regex = '/([\w]+)([^\(]|$)/';

		foreach ( $order_by as $order_by_string )
		{
			$parts = explode(',', $order_by_string);

			foreach ( $parts as $part )
			{
				// remove optional order marker
				$part = preg_replace('/\s+(ASC|DESC)$/i', '', $part);

				// remove all functions (might not work well on recursive)
				$replacements = 1;
				while ( $replacements > 0 )
				{
					$part = preg_replace('/[a-z][\w]*\((.*)\)/i', '$1', $part, -1, $replacements);
				}

				// now remove all fully-qualified elements (those with tables)
				$part = preg_replace('/("[a-z][\w]*"|[a-z][\w]*)\.("[a-z][\w]*"|[a-z][\w]*)/i', '', $part);

				// finally, match all whole words left behind
				preg_match_all('/([a-z][\w]*)/i', $part, $result, PREG_SET_ORDER);

				foreach ( $result as $column )
				{
					$list[] = $column[0];
				}
			}
		}

		return $list;
	}

	// --------------------------------------------------------------------

	/**
	 * splits the select query up into parts
	 *
	 * @ignore
	 *
	 * @param	string	$select	original select string
	 *
	 * @return	array	individual select components
	 */
	protected static function splitselect($select)
	{
		// splits a select into parameters, then stores them as
		// $select[<alias>] = $select_part
		$list = array();
		$last_pos = 0;
		$pos = -1;

		while ( $pos < strlen($select) )
		{
			$pos++;
			if ( $pos == strlen($select) OR $select[$pos] == ',' )
			{
				// we found an item, process it
				$sel = substr($select, $last_pos, $pos-$last_pos);
				if ( preg_match('/\sAS\s+"?([a-z]\w*)"?\s*$/i', $sel, $matches) != 0 )
				{
					$list[$matches[1]] = trim($sel);
				}
				$last_pos = $pos+1;
			}
			elseif ( $select[$pos] == '(' )
			{
				// skip past parenthesized sections
				$pos = self::splitselect_parens($select, $pos);
			}
		}
		return $list;
	}

	// --------------------------------------------------------------------

	/**
	 * recursively processes parentheses in the select string
	 *
	 * @ignore
	 *
	 * @param	string	$select select string
	 * @param	int		$pos	current location in the string
	 *
	 * @return	int	final position after all recursing is complete
	 */
	protected static function splitselect_parens($select, $pos)
	{
		while ( $pos < strlen($select) )
		{
			$pos++;
			if ( $select[$pos] == '(' )
			{
				// skip past recursive parenthesized sections
				$pos = self::splitselect_parens($select, $pos);
			}
			elseif ( $select[$pos] == ')' )
			{
				break;
			}
		}

		return $pos;
	}

}

/* End of file rowindex.php */
/* Location: ./application/datamapper/rowindex.php */
