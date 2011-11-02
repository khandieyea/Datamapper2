<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * ORM unit tests : C table model - join table
 *
 * @license     MIT License
 * @package     DataMapper ORM
 * @category    DataMapper ORM
 * @author      Harro "WanWizard" Verton
 * @link        http://datamapper.wanwizard.eu
 * @version     2.0.0
 */

class Dmtestc extends DataMapper
{
	// define the model name for this model
	protected $model = 'dmtestc';

	// define the tablename for this model
	protected $table = 'dmtests_C';

	// define the primary key(s) for this model
	protected $primary_key = array('id' => 'integer');

	// insert related models that this model can have just one of
	protected $has_one = array();

	// insert related models that this model can have more than one of
	protected $has_many = array();

	// insert models that this model belongs to
	protected $belongs_to = array(
		'dmtestsa' => array(),
		'dmtestsb' => array(),
	);

	// define validation rules for each column
	protected $validation = array(
		'id' => array(
			'get_rules' => array(
				'intval',
			),
		),
		'fk_id_A' => array(
			'get_rules' => array(
				'intval',
			),
		),
		'fk_id_B' => array(
			'get_rules' => array(
				'intval',
			),
		),
	);

	// define the default ordering for this model
	protected $default_order_by = array('id' => 'asc');

	// -------------------------------------------------------------------------
	// Dynamic class definition
	// -------------------------------------------------------------------------

	/*
	 * Constructor
	 *
	 * custom model initialisation. Do NOT forget to call the parent
	 * constructor, otherwise the model class will not be initialized!
	 *
	 * Note that if you don't need a constructor here, remove this, as it
	 * only introduces additional overhead.
	 */
	public function __construct($id = NULL)
	{
		// call the parent constructor to initialize the model
		parent::__construct($id);
    }

	// --------------------------------------------------------------------

	/*
	 * Post Model Initialisation
	 *
	 * add your own custom initialisation code to the model. this method is called
	 * after the configuration for this model has been processed
	 *
	 * @param	boolean	$from_cache	if true the current config came from cache
	 */
	protected function post_model_init($from_cache = FALSE)
	{
	}
}

/* End of file parent.php */
/* Location: ./application/models/parent.php */
