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

class DataMapper_Validation {

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
			// only process non-empty keys that are not specifically set to be null
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
					 $dmobject->{'rule_'.$rule}($dmobject->{$field}, $param);
				}

				// is the rule a datamapper extension validation method?
				elseif ( $method = DataMapper::dm_is_extension_method('rule_'.$rule) )
				{
					$dmobject->{$method}($dmobject->{$field}, $param);
				}

				// is the rule a datamapper core validation method?
				elseif ( is_callable('self::rule_'.$rule) )
				{
					call_user_func_array('self::rule_'.$rule, array($dmobject->{$field}, $param));
				}

				// is the rule a CI form validation method?
				elseif ( method_exists(DataMapper::$CI->form_validation, $rule) )
				{
					// Run rule from CI Form Validation
					DataMapper::$CI->form_validation->{$rule}($dmobject->{$field}, $param);
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

}

/* End of file validation.php */
/* Location: ./application/third_party/datamapper/datamapper/validation.php */
