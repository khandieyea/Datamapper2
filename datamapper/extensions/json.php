<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * DataMapper extension - json methods
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

class DataMapper_Json
{
	/**
	 * convert a DataMapper model into JSON code
	 *
	 * @param	DataMapper	$dmobject		the DataMapper object to convert
	 * @param	array		$fields			array of fields to include.  if empty, includes all database columns
	 * @param	boolean		$pretty_print	format the JSON code for legibility
	 * @param	boolean		$no_encode		Internal use only. if true, return the result without encoding
	 *
	 * @return	string		a JSON formatted String, or FALSE if an error occurs
	 */
	public static function to_json($dmobject, $fields = '', $pretty_print = FALSE, $no_encode = FALSE)
	{
		// determine the correct field set
		empty($fields) AND $fields = $dmobject->dm_get_config('fields');

		$result = array();

		foreach ( $fields as $f )
		{
			// handle related objects
			if ( $dmobject->{$f} instanceOf DataMapper )
			{
				// each related item is stored as an array of ids
				// Note: this method will NOT get() the related object.
				$rels = array();

				foreach ( $dmobject->{$f} as $item )
				{
					// get the keys for this object
					$keys = $item->dm_get_config('keys');

					// and store them
					$relkeys = array();
					foreach ( $keys as $key => $unused )
					{
						$relkeys[$key] = $item->{$key};
					}
					$rels[] = $relkeys;
				}

				// store all related keys
				$result[$f] = $rels;
			}
			else
			{
				// just store the field value
				$result[$f] = $object->{$f};
			}
		}

		// encode the result
		if ( $no_encode )
		{
			return $result;
		}
		else
		{
			$json = json_encode($result);
		}

		// deal with encoding errors
		if ( $json === FALSE )
		{
			return FALSE;
		}

		// beautify if needed
		$pretty_print AND $json = self::dm_json_format($json);

		// return the result
		return $json;
	}

	// --------------------------------------------------------------------

	/**
	 * Convert the entire $object->all array result set into JSON code.
	 *
	 * @param	DataMapper	$dmobject		the DataMapper Object to convert
	 * @param	array		$fields			array of fields to include.  If empty, includes all database columns
	 * @param	boolean		$pretty_print	format the JSON code for legibility
	 *
	 * @return	string		a JSON formatted String, or FALSE if an error occurs
	 */
	public static function all_to_json($dmobject, $fields = '', $pretty_print = FALSE)
	{
		// determine the correct field set
		empty($fields) AND $fields = $dmobject->dm_get_config('fields');

		$result = array();

		foreach($dmobject as $o)
		{
			$result[] = self::to_json($o, $fields, FALSE, TRUE);
		}

		$json = json_encode($result);

		if ( $json === FALSE )
		{
			return FALSE;
		}

		$pretty_print AND $json = self::dm_json_format($json);

		return $json;
	}

	// --------------------------------------------------------------------

	/**
	 * convert a JSON object back into a DataMapper model
	 *
	 * @param	DataMapper	$object		the DataMapper Object to save to
	 * @param	string		$json_code	a string that contains JSON code
	 * @param	array		$fields		array of 'safe' fields.  if empty, only include the database columns
	 *
	 * @return	bool		TRUE or FALSE on success or failure of converting the JSON string
	 */
	public static function from_json($dmobject, $json_code, $fields = '')
	{
		// determine the correct field set
		empty($fields) AND $fields = $dmobject->dm_get_config('fields');

		// decode the json string
		$data = json_decode($json_code);

		if ( $data === FALSE )
		{
			return FALSE;
		}

		// store the results
		foreach ( $data as $k => $v )
		{
			in_array($k, $fields) AND $dmobject->{$k} = $v;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the HTTP Content-Type header to application/json
	 *
	 * @param	DataMapper	$object
	 */
	public static function set_json_content_type($dmobject)
	{
		DataMapper::$CI->output->set_header('Content-Type: application/json');
	}

	// --------------------------------------------------------------------

	/**
	 * formats a JSON string for readability
	 *
	 * taken from @link http://php.net/manual/en/function.json-encode.php
	 *
	 * @param string	$json	unformatted JSON
	 *
	 * @return string	formatted JSON
	 */
	protected static function dm_json_format($json)
	{
		$tab = "  ";
		$new_json = "";
		$indent_level = 0;
		$in_string = false;

		$json_obj = json_decode($json);

		if ( $json_obj === FALSE )
		{
			return FALSE;
		}

		$json = json_encode($json_obj);
		$len = strlen($json);

		for ( $c = 0; $c < $len; $c++ )
		{
			$char = $json[$c];
			switch ( $char )
			{
				case '{':
				case '[':
					if ( ! $in_string )
					{
						$new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
						$indent_level++;
					}
					else
					{
						$new_json .= $char;
					}
					break;

				case '}':
				case ']':
					if ( ! $in_string )
					{
						$indent_level--;
						$new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
					}
					else
					{
						$new_json .= $char;
					}
					break;

				case ',':
					if ( ! $in_string )
					{
						$new_json .= ",\n" . str_repeat($tab, $indent_level);
					}
					else
					{
						$new_json .= $char;
					}
					break;

				case ':':
					if ( ! $in_string )
					{
						$new_json .= ": ";
					}
					else
					{
						$new_json .= $char;
					}
					break;

				case '"':
					if ( $c > 0 AND $json[$c-1] != '\\' )
					{
						$in_string = !$in_string;
					}
					// break intentionally ommitted

				default:
					$new_json .= $char;
					break;
			}
		}

		return $new_json;
	}

}

/* End of file json.php */
/* Location: ./application/third-party/datamapper/extensions/json.php */
