<?php
/**
 * Template DataMapper Model
 *
 * Use this basic model as a template for creating new models.
 * It is not recommended that you include this file with your application,
 * especially if you use a Template library (as the classes may collide).
 *
 * To use:
 * 1) Copy this file to the lowercase name of your new model.
 * 2) Find-and-replace (case-sensitive) 'Datamapper_model' with 'Your_model'
 * 3) Find-and-replace (case-sensitive) 'template' with 'your_model'
 * 4) Find-and-replace (case-sensitive) 'templates' with 'your_models'
 * 5) Edit the file as desired.
 *
 * @license		MIT License
 * @category	Models
 * @author		Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu
 */

// -------------------------------------------------------------------------
// model class definition
// -------------------------------------------------------------------------

class Datamapper_model extends DataMapper {

	/*
	 * Uncomment and edit these two if the class has a model name that
	 * doesn't convert properly using the inflector_helper.
	 */

//	protected $model = 'Datamapper_model';		// name of this model when referencing it
// 	protected $table = 'Datamapper_model';		// name of the table that this model maps to

	/*
	 * You can override the default primary key by editing this.
	 * A table can have multiple columns in the primary key.
	 */

//	protected $primary_key = array('id' => 'integer');

	/*
	 * You can override the database connections with this option
	 */

//	protected $db_params = 'db_config_name';

	/*
	 * Relationships - Configure your relationships below
	 *
	 * "Has" relations go downwards, like "A Parent has_many Children"
	 * A "Belongs_to" relation goes upward, like "A child belongs to a Parent"
	 *
	 * This has meaning when you activate cascading deletes, where a belongs_to
	 * related record will not be deleted, where as has relations will.
	 */

	// Insert related models that Datamapper_model can have just one of.
	protected $has_one = array();

	// Insert related models that Datamapper_model can have more than one of.
	protected $has_many = array();

	// Insert models that Datamapper_model belongs to.
	protected $belongs_to = array();

	/* Relationship Examples
	 * For normal relationships, simply add the model name to the array:
	 *   $has_one = array('user'); // Datamapper_model has one User
	 *   $has_many = array('item'); // Datamapper_model has many Item
	 *
	 * For complex relationships, such as having a Creator and Editor for
	 * Datamapper_model, use this form:
	 */

//	protected $has_one = array(
//		'creator' => array(
//			'class' => 'user',
//			'other_field' => 'created_template'
//		),
//		'editor' => array(
//			'class' => 'user',
//			'other_field' => 'edited_template'
//		)
//	);

	 /*
	 * Don't forget to add 'created_template' and 'edited_template' to the
	 * model "User", with class set to 'datamapper_model', and the other_field
	 * set to 'creator' and 'editor' to maintain the relation both ways!
	 */

	/*
	 * Validation
	 *
	 * Define the validation rules for each column you want DataMapper to
	 * validate. Rules are the same as for CI's Form_Validation class
	 *
	 * The label is used when displaying error messages. Prefix the value
	 * by 'lang:' to have the label fetched from the loaded language strings
	 * at runtime.
	 */

	protected $validation = array();

//	protected $validation = array(
//		'example' => array(
//			// example is required, and cannot be more than 120 characters long.
//			'rules' => array('required', 'max_length' => 120),
//			'label' => 'Example'
//		)
//	);

	/*
	 * Default Ordering
	 *
	 * Uncomment this to always sort by 'name', then by id descending
	 * This is overridden by using the order_by() method
	 */

//	protected $default_order_by = array('name', 'id' => 'desc');

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

	// --------------------------------------------------------------------
	// Custom Methods
	//   Add your own custom methods here to enhance the model.
	// --------------------------------------------------------------------

	/* Example Custom Method
	function get_open_templates()
	{
		return $this->where('status <>', 'closed')->get();
	}
	*/

	// --------------------------------------------------------------------
	// Custom Validation Rules
	//   Add custom validation rules for this model here.
	// --------------------------------------------------------------------

	/* Example Rule
	function _convert_written_numbers($field, $parameter)
	{
	 	$nums = array('one' => 1, 'two' => 2, 'three' => 3);
	 	if(in_array($this->{$field}, $nums))
		{
			$this->{$field} = $nums[$this->{$field}];
	 	}
	}
	*/
}

/* End of file datamapper_model.php */
/* Location: ./application/models/datamapper_model.php */
