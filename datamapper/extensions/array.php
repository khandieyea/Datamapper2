<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * DataMapper extension - array methods
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

class DataMapper_Array
{
	/**
	 * convert a DataMapper model into an associative array
	 *
	 * if the specified fields includes a related object, the keys from the
	 * objects are collected into an array and stored on that key
	 *
	 * this method does not recursively add objects
	 *
	 * @param	DataMapper	$dmobject	the DataMapper object to convert
	 * @param	array		$fields	array of fields to include.  if empty, includes all database columns
	 *
	 * @return	array	an associative array of the requested fields and related object keys
	 */
	public static function to_array($dmobject, $fields = '')
	{
		// assume all database columns if $fields is not provided.
		if ( empty($fields) )
		{
			$fields = $dmobject->dm_get_config('fields');
		}
		else
		{
			$fields = (array) $fields;
		}

		$result = array();

		foreach ( $fields as $f )
		{
			// handle related fields
			$relations = $dmobject->dm_get_config('relations');
			if ( array_key_exists($f, $relations['has_one']) OR array_key_exists($f, $relations['has_many']) )
			{
				// each related item is stored as an array of ids
				// note: this method will NOT get() the related object.
				$rels = array();
				foreach( $dmobject->{$f} as $item )
				{
					$keys = array();
					foreach ( $item->dm_get_config('keys') as $key => $type )
					{
						$keys[$key] = $item->{$key};
					}
					$rels[] = $keys;
				}
				$result[$f] = $rels;
			}
			else
			{
				// just the field.
				$result[$f] = $dmobject->{$f};
			}
		}

		return $result;
	}

	// -------------------------------------------------------------------------

	/**
	 * convert the entire $dmobject->all array result set into an array of
	 * associative arrays
	 *
	 * @see		to_array
	 *
	 * @param	DataMapper	$dmobject	the DataMapper Object to convert
	 * @param	array		$fields	array of fields to include. if empty, includes all database columns
	 *
	 * @return	array	an array of associative arrays
	 */
	public static function all_to_array($dmobject, $fields = '')
	{
		// loop through each object in the $all array, convert them to
		// an array, and add them to a new array.
		$result = array();

		foreach ( $dmobject as $o )
		{
			$result[] = DataMapper_Array::to_array($o, $fields);
		}
		return $result;
	}

	// -------------------------------------------------------------------------

	/**
	 * convert a single field from the entire $dmobject->all array result set into an a single array
	 * with the objects' key field value as key
	 *
	 * note that this only works for models with a single key column!
	 *
	 * @param	DataMapper	$dmobject	the DataMapper Object to convert
	 * @param	string		$field	to include
	 *
	 * @return	array	an array of associative arrays
	 */
	public static function all_to_single_array($dmobject, $field = '')
	{
		if ( count($dmobject->dm_get_config('keys')) != 1 )
		{
			throw new DataMapperException("DataMapper: all_to_single_array() does not support tables with multi-column primary keys");
		}
		$key = key($dmobject->dm_get_config('keys'));

		// loop through each object in the $all array, convert them to
		// an array, and add them to a new array.
		$result = array();
		if ( ! empty($field) )
		{
			foreach ( $dmobject as $o )
			{
				isset($o->{$field}) AND $result[$o->{$key}] = $o->{$field};
			}
		}
		return $result;
	}

	// -------------------------------------------------------------------------

	/**
	 * convert an associative array back into a DataMapper model
	 *
	 * If $fields is provided, missing fields are assumed to be empty checkboxes
	 *
	 * @param	DataMapper	$dmobject	The DataMapper Object to save to
	 * @param	array		$data	a an associative array of fields to convert
	 * @param	array		$fields	array of 'safe' fields.  if empty, only includes the database columns
	 * @param	bool		$save	if TRUE, then attempt to save the object automatically
	 *
	 * @return	array|bool	a list of newly related objects, or the result of the save if $save is TRUE
	 */
	public static function from_array($dmobject, $data, $fields = '', $save = FALSE)
	{
		// keep track of newly related objects
		$new_related_objects = array();

		// assume all database columns.
		// in this case, simply store $fields that are in the $data array
		if ( empty($fields) )
		{
			$fields = $dmobject->dm_get_config('fields');

			foreach ( $data as $k => $v )
			{
				if ( in_array($k, $fields) )
				{
					$dmobject->{$k} = $v;
				}
			}
		}
		else
		{
			// if $fields is provided, assume all $fields should exist
			foreach ( $fields as $f )
			{
				$relations = $dmobject->dm_get_config('relations');
				if ( array_key_exists($f, $relations['has_one']) )
				{
					// Store $has_one relationships
					$c = get_class($dmobject->{$f});
					$rel = new $c();

					if ( count($rel->dm_get_config('keys')) != 1 )
					{
						throw new DataMapperException("DataMapper: from_array() does not support related models with multi-column primary keys");
					}

					$key = key($dmobject->dm_get_config('keys'));
					$value = isset($data[$f]) ? $data[$f] : NULL;

					$rel->where($key, $value)->get();

					if ( $rel->exists() )
					{
						// the new relationship exists, save it
						$new_related_objects[$f] = $rel;
					}
					else
					{
						// the new relationship does not exist, delete the old one
						 $dmobject->delete($dmobject->{$f}->get());
					}
				}
				elseif ( array_key_exists($f, $relations['has_many']) )
				{
					// store $has_many relationships
					$c = get_class($dmobject->{$f});
					$rels = new $c();

					if ( count($rel->dm_get_config('keys')) != 1 )
					{
						throw new DataMapperException("DataMapper: from_array() does not support related models with multi-column primary keys");
					}

					$key = key($dmobject->dm_get_config('keys'));
					$values = isset($data[$f]) ? $data[$f] : NULL;

					if ( empty($values) )
					{
						// if no key values were provided, delete all old relationships.
						$dmobject->delete($dmobject->{$f}->select($key)->get()->all);
					}
					else
					{
						// otherwise, get the new ones...
						$rels->where_in($key, $values)->select($key)->get();
						// store them...
						$new_related_objects[$f] = $rels->all;
						// and delete any old ones that do not exist.
						$old_rels = $dmobject->{$f}->where_not_in($key, $values)->select($key)->get();
						$dmobject->delete($old_rels->all);
					}
				}
				else
				{
					// otherwise, if the $data was set, store it...
					if(isset($data[$f]))
					{
						$v = $data[$f];
					}
					else
					{
						// or assume it was an unchecked checkbox, and clear it.
						$v = FALSE;
					}
					$dmobject->{$f} = $v;
				}
			}
		}

		if($save)
		{
			// auto save
			return $dmobject->save($new_related_objects);
		}
		else
		{
			// return new objects
			return $new_related_objects;
		}
	}

}

/* End of file array.php */
/* Location: ./application/third_party/datamapper/datamapper/array.php */
