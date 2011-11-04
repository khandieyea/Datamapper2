<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * DataMapper extension - validation methods
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

class DataMapper_Validation
{
	/**
	 * validates the value of each property against the assigned validation rules
	 *
	 * @param	DataMapper	$dmobject		the object being validation
	 * @param	mixed		$object			objects included with the validation [from save()]
	 * @param	string		$related_field	see save()
	 *
	 * @return	DataMapper	returns $dmobject for method chanining.
	 */
	public static function validate($dmobject, $object = '', $related_field = '')
	{
		// return if validation has already been run
		if ( $dmobject->dm_get_flag('validated') )
		{
			// For method chaining
			return $dmobject;
		}

		// set validated as having been run
		$dmobject->dm_set_flag('validated', TRUE);

		// clear errors
		$dmobject->error = new DataMapper_Errors($dmobject);

		// loop through the save_rules of each field to be validated
		foreach ($dmobject->dm_get_config('validation', 'save_rules') as $field => $validation)
		{
			// skip if no rules are defined for this field
			if ( empty($validation['rules']) )
			{
				continue;
			}
var_dump($field);
var_dump($validation);
die($TODO = 're-code save validation rules');

			// Get validation settings
			$rules = $validation['rules'];

			// Will validate differently if this is for a related item
			$related = (isset($dmobject->has_many[$field]) || isset($dmobject->has_one[$field]));

			// Check if property has changed since validate last ran
			if ($related || $dmobject->_force_validation || ! isset($dmobject->stored->{$field}) || $dmobject->{$field} !== $dmobject->stored->{$field})
			{
				// Only validate if field is related or required or has a value
				if ( ! $related && ! in_array('required', $rules) && ! in_array('always_validate', $rules))
				{
					if ( ! isset($dmobject->{$field}) || $dmobject->{$field} === '')
					{
						continue;
					}
				}

				$label = ( ! empty($validation['label'])) ? $validation['label'] : $field;

				// Loop through each rule to validate this property against
				foreach ($rules as $rule => $param)
				{
					// Check for parameter
					if (is_numeric($rule))
					{
						$rule = $param;
						$param = '';
					}

					// Clear result
					$result = '';
					// Clear message
					$line = FALSE;

					// Check rule exists
					if ($related)
					{
						// Prepare rule to use different language file lines
						$rule = 'related_' . $rule;

						$arg = $object;
						if( ! empty($related_field)) {
							$arg = array($related_field => $object);
						}

						if (method_exists($dmobject, '_' . $rule))
						{
							// Run related rule from DataMapper or the class extending DataMapper
							$line = $result = $dmobject->{'_' . $rule}($arg, $field, $param);
						}
						else if($dmobject->_extension_method_exists('rule_' . $rule))
						{
							$line = $result = $dmobject->{'rule_' . $rule}($arg, $field, $param);
						}
					}
					else if (method_exists($dmobject, '_' . $rule))
					{
						// Run rule from DataMapper or the class extending DataMapper
						$line = $result = $dmobject->{'_' . $rule}($field, $param);
					}
					else if($dmobject->_extension_method_exists('rule_' . $rule))
					{
						// Run an extension-based rule.
						$line = $result = $dmobject->{'rule_' . $rule}($field, $param);
					}
					else if (method_exists($dmobject->form_validation, $rule))
					{
						// Run rule from CI Form Validation
						$result = $dmobject->form_validation->{$rule}($dmobject->{$field}, $param);
					}
					else if (function_exists($rule))
					{
						// Run rule from PHP
						$dmobject->{$field} = $rule($dmobject->{$field});
					}

					// Add an error message if the rule returned FALSE
					if (is_string($line) || $result === FALSE)
					{
						if(!is_string($line))
						{
							if (FALSE === ($line = $dmobject->lang->line($rule)))
							{
								// Get corresponding error from language file
								$line = 'Unable to access an error message corresponding to your rule name: '.$rule.'.';
							}
						}

						// Check if param is an array
						if (is_array($param))
						{
							// Convert into a string so it can be used in the error message
							$param = implode(', ', $param);

							// Replace last ", " with " or "
							if (FALSE !== ($pos = strrpos($param, ', ')))
							{
								$param = substr_replace($param, ' or ', $pos, 2);
							}
						}

						// Check if param is a validation field
						if (isset($dmobject->validation[$param]))
						{
							// Change it to the label value
							$param = $dmobject->validation[$param]['label'];
						}

						// Add error message
						$dmobject->error->message($field, sprintf($line, $label, $param));

						// Escape to prevent further error checks
						break;
					}
				}
			}
		}

		// Set whether validation passed
		$dmobject->valid = empty($dmobject->error->all);

		// For method chaining
		return $dmobject;
	}

	// --------------------------------------------------------------------

	/**
	 * force revalidation for the next call to save.
	 * this allows you to run validation rules on fields that haven't been modified
	 *
	 * @param	DataMapper	$dmobject	the object being validation
	 * @param	bool		$force		if TRUE, forces validation on all fields
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public static function force_validation($dmobject, $force = TRUE)
	{
		$dmobject->dm_set_flag('force_validation', $force);

		// for method chaining
		return $dmobject;
	}

	// --------------------------------------------------------------------

	/**
	 * skips validation for the next call to save
	 *
	 * note that this also prevents the validation routine from running until the next get
	 *
	 * @param	DataMapper	$dmobject	the object being validation
	 * @param	bool		$skip		if FALSE, re-enables validation
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public static function skip_validation($dmobject, $skip = TRUE)
	{
		$dmobject->dm_set_flag('validated', $skip);
		$dmobject->dm_set_flag('valid', $skip);

		// for method chaining
		return $dmobject;
	}

	// --------------------------------------------------------------------

	/**
	 * processes values loaded from the database
	 *
	 * @param	DataMapper	$dmobject		the object being validation
	 *
	 * @return	void
	 */
	public static function run_get_rules($dmobject)
	{
		// make sure we have form validation loaded
		if ( ! isset(DataMapper::$CI->form_validation) )
		{
			DataMapper::$CI->load->library('form_validation');
		}

		// loop through each property to be validated
		foreach ($dmobject->dm_get_config('validation', 'get_rules') as $field => $rules)
		{
			// only process non-empty fields that are not specifically set to be null
			if ( ! isset($dmobject->{$field}) AND ! in_array('allow_null', $rules) )
			{
				continue;
			}

			// loop through each rule to validate this field against
			foreach ($rules as $rule => $param)
			{
				// check for parameter
				if ( is_numeric($rule) )
				{
					$rule = $param;
					$param = '';
				}

				// rules to skip
				if ( $rule == 'allow_null' )
				{
					continue;
				}

				// is the rule in the datamapper object itself?
				if ( method_exists($dmobject, 'rule_'.$rule) )
				{
					$dmobject->{'rule_'.$rule}($field, $param);
				}

				// is the rule a datamapper extension validation method?
				elseif ( $method = DataMapper::dm_is_extension_method('rule_'.$rule) )
				{
					$dmobject->{$method}($dmobject, $field, $param);
				}

				// is the rule a datamapper core validation method?
				elseif ( is_callable('self::rule_'.$rule) )
				{
					call_user_func_array('self::rule_'.$rule, array($dmobject, $field, $param));
				}

				// is the rule a CI form validation method?
				elseif ( method_exists(DataMapper::$CI->form_validation, $rule) )
				{
					// run rule from CI Form Validation
					$dmobject->{$field} = DataMapper::$CI->form_validation->{$rule}($dmobject->{$field}, $param);
				}

				// is the rule a defined function?
				elseif ( function_exists( $rule) )
				{
					$dmobject->{$field} = $rule( $dmobject->{$field} );
				}
				else
				{
					$TODO = 'Trigger an error if the validation rule can not be found?';
				}
			}

		}
	}

	// --------------------------------------------------------------------
	// built-in validation rules
	// --------------------------------------------------------------------

	/**
	 * does nothing, but forces a validation even if empty (for non-required fields)
	 *
	 * @ignore
	 */
	protected static function rule_always_validate($object, $field, $param = array())
	{
	}

	// --------------------------------------------------------------------

	/**
	 * checks whether the field value matches one of the specified array values
	 *
	 * @ignore
	 */
	protected static function rule_valid_match($object, $field, $param = array())
	{
		return in_array($object->{$field}, $param);
	}

	// --------------------------------------------------------------------

	/**
	 * checks whether the field value is a valid DateTime
	 *
	 * @ignore
	 */
	protected static function rule_valid_date($object, $field, $param = array())
	{
		// ignore if empty
		if ( empty($object->{$field}) )
		{
			return TRUE;
		}

		$date = date_parse($field);

		return checkdate($date['month'], $date['day'],$date['year']);
	}

	// --------------------------------------------------------------------

	/**
	 * checks whether the field value, grouped with other field values, is a valid DateTime
	 *
	 * @ignore
	 */
	protected static function rule_valid_date_group($object, $field, $param = array())
	{
		// ignore if empty
		if ( empty($object->{$field}) )
		{
			return TRUE;
		}

		$date = date_parse($fields['year'] . '-' . $fields['month'] . '-' . $fields['day']);

		return checkdate($date['month'], $date['day'],$date['year']);
	}

	// --------------------------------------------------------------------

	/**
	 * checks if the value of a property is unique in the table
 	 *
	 * @ignore
	 */
	protected static function rule_unique($object, $field, $param = array())
	{
		$match = TRUE;

		if ( ! empty($object->{$field}) )
		{
			// run the query to check
			$query = $object->db->get_where($object->dm_get_config('table'), array( $field => $object->{$field} ), 1, 0);

			if ($query->num_rows() > 0)
			{
				$row = $query->row();

				// if unique, the keys should not match
				foreach ( $object->dm_get_config('keys') as $key => $unused )
				{
					if ( $object->{$key} != $row->{$key} )
					{
						$match = FALSE;
						break;
					}
				}
			}
		}

		return $match;
	}

	// --------------------------------------------------------------------

	/**
	 * checks if the value of a property, paired with another, is unique in the table
	 *
	 * @ignore
	 */
	protected static function rule_unique_pair($object, $field, $other_field = '')
	{
		$match = TRUE;

		if ( ! empty($object->{$field}) AND ! empty($object->{$other_field}) )
		{
			// run the query to check
			$query = $object->db->get_where($object->dm_get_config('table'), array($field => $object->{$field}, $other_field => $object->{$other_field}), 1, 0);

			if ($query->num_rows() > 0)
			{
				$row = $query->row();

				// if unique, the keys should not match
				foreach ( $object->dm_get_config('keys') as $key => $unused )
				{
					if ( $object->{$key} != $row->{$key} )
					{
						$match = FALSE;
						break;
					}
				}
			}
		}

		return $match;
	}

	// --------------------------------------------------------------------

	/**
	 * alpha-numeric with underscores, dashes and full stops
	 *
	 * @ignore
	 */
	protected static function rule_alpha_dash_dot($object, $field, $param = array())
	{
		return ( ! preg_match('/^([\.-a-z0-9_-])+$/i', $object->{$field})) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * alpha-numeric with underscores, dashes, forward slashes and full stops
	 *
	 * @ignore
	 */
	protected static function rule_alpha_slash_dot($object, $field, $param = array())
	{
		return ( ! preg_match('/^([\.\/-a-z0-9_-])+$/i', $object->{$field})) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * match one field to another
	 *
	 * @ignore
	 */
	protected static function rule_matches($object, $field, $other_field)
	{
		return ($object->{$field} !== $object->{$other_field}) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * checks if the value of a property is at least the minimum date.
	 *
	 * @ignore
	 */
	protected static function rule_min_date($object, $field, $date)
	{
		return (strtotime($object->{$field}) < strtotime($date)) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * checks if the value of a property is at most the maximum date
	 *
	 * @ignore
	 */
	protected static function rule_max_date($object, $field, $date)
	{
		return (strtotime($object->{$field}) > strtotime($date)) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * checks if the value of a property is at least the minimum size
	 *
	 * @ignore
	 */
	protected static function rule_min_size($object, $field, $size)
	{
		return ($object->{$field} < $size) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Max Size (pre-process)
	 *
	 * Checks if the value of a property is at most the maximum size.
	 *
	 * @ignore
	 */
	protected static function rule_max_size($object, $field, $size)
	{
		return ($object->{$field} > $size) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------
	// built-in prep rules (which alter the field value)
	// --------------------------------------------------------------------

	/**
	 * runs the data through the XSS filtering function, described in the Security Class page
	 *
	 * @ignore
	 */
	protected static function rule_xss_clean($object, $field, $is_image = FALSE)
	{
		// make sure the security library is loaded
		isset(DataMapper::$CI->security) OR DataMapper::$CI->load->library('security');

		$object->{$field} = DataMapper::$CI->security->xss_clean($object->{$field}, $is_image);
	}

	// --------------------------------------------------------------------

	/**
	 * custom trim rule that ignores NULL values
	 *
	 * @ignore
	 */
	protected static function rule_trim($object, $field, $param = array())
	{
		empty($object->{$field}) OR $object->{$field} = trim($object->{$field});
	}

	// --------------------------------------------------------------------

	/**
	 * strips the HTML from image tags leaving the raw URL
	 *
	 * @ignore
	 */
	protected static function rule_strip_image_tags($object, $field, $param = array())
	{
		$object->{$field} = strip_image_tags($object->{$field});
	}

	// --------------------------------------------------------------------

	/**
	 * forces a field to be either TRUE or FALSE
	 *
	 * @ignore
	 */
	protected static function rule_boolean($object, $field, $param = array())
	{
		$object->{$field} = (bool) $object->{$field};
	}
}

/* End of file validation.php */
/* Location: ./application/third_party/datamapper/datamapper/validation.php */
