<?php	if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper Configuration
 *
 * Global configuration settings that apply to all DataMapped models.
 */

$config = array(
	'prefix'					=> '',
	'join_prefix'				=> '',
	'error_prefix'				=> '<p>',
	'error_suffix'				=> '</p>',
	'created_field'				=> 'created',
	'updated_field'				=> 'updated',
	'local_time'				=> FALSE,
	'unix_timestamp'			=> TRUE,
	'auto_transaction'			=> FALSE,
	'auto_populate_has_many'	=> FALSE,
	'auto_populate_has_one'		=> FALSE,
	'auto_populate_belongs_to'	=> FALSE,
	'cache_path'				=> 'cache',
	'cache_expiration'			=> FALSE,
	'extensions'				=> array('array'),
	'extensions_path'			=> array(),
	'all_array_uses_keys'		=> FALSE,
	'db_params'					=> FALSE,
	'timestamp_format'			=> 'Y-m-d H:i:s O',
	'lang_file_format'			=> 'model_${model}',
	'field_label_lang_format'	=> '${model}_${field}',
	'cascade_delete'			=> TRUE,
	'model_prefix'				=> '',
	'model_suffix'				=> '',
	'free_result_threshold'		=> 100,
);

/* End of file datamapper.php */
/* Location: ./application/third_party/datamapper/config/datamapper.php */
