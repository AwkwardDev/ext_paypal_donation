<?php
/**
 *
 * PayPal Donation extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2015 Skouat
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace skouat\ppde\entity;

/**
 * Entity for a currency
 */
class currency extends main implements currency_interface
{
	/**
	 * Data for this entity
	 *
	 * @var array
	 *    currency_id
	 *    currency_name
	 *    currency_iso_code
	 *    currency_symbol
	 *    currency_enable
	 *    currency_order
	 * @access protected
	 */
	protected $data;
	protected $u_action;

	protected $db;
	protected $user;
	protected $currency_table;

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver_interface $db         Database object
	 * @param \phpbb\user                       $user       User object
	 * @param string                            $table_name Name of the table used to store data
	 *
	 * @access public
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\user $user, $table_name)
	{
		$this->db = $db;
		$this->user = $user;
		$this->currency_table = $table_name;
		parent::__construct(
			$table_name,
			'CURRENCY',
			array(
				'item_id' => 'currency_id',
			)
		);
	}

	/**
	 * Check the currency_id exist from the database for this currency
	 *
	 * @return int $this->data['currency_id'] Currency identifier; 0 if the currency doesn't exist
	 * @access public
	 */
	public function currency_exists()
	{
		$sql = 'SELECT currency_id
			FROM ' . $this->currency_table . "
			WHERE currency_iso_code = '" . $this->db->sql_escape($this->data['currency_iso_code']) . "'
			AND currency_symbol = '" . $this->db->sql_escape($this->data['currency_symbol']) . "'";
		$this->db->sql_query($sql);

		return $this->db->sql_fetchfield('currency_id');
	}

	/**
	 * Import and validate data for currency
	 *
	 * Used when the data is already loaded externally.
	 * Any existing data on this page is over-written.
	 * All data is validated and an exception is thrown if any data is invalid.
	 *
	 * @param  array $data Data array, typically from the database
	 *
	 * @return currency_interface $this->data object
	 * @access public
	 */
	public function import($data)
	{
		// Clear out any saved data
		$this->data = array();

		// All of our fields
		$fields = array(
			// column			=> data type (see settype())
			'currency_id'       => 'integer',
			'currency_name'     => 'string',
			'currency_iso_code' => 'string',
			'currency_symbol'   => 'string',
			'currency_enable'   => 'boolean',
			'currency_order'    => 'integer',
		);

		// Go through the basic fields and set them to our data array
		foreach ($fields as $field => $type)
		{
			// If the data wasn't sent to us, throw an exception
			if (!isset($data[$field]))
			{
				$this->display_error_message('PPDE_FIELD_MISSING');
			}

			// settype passes values by reference
			$value = $data[$field];

			// We're using settype to enforce data types
			settype($value, $type);

			$this->data[$field] = $value;
			$this->data[$field] = $value;
		}

		return $this->data;
	}

	/**
	 * Insert the item for the first time
	 *
	 * Will throw an exception if the item was already inserted (call save() instead)
	 *
	 * @return currency_interface $this object for chaining calls; load()->set()->save()
	 * @access public
	 */
	public function insert()
	{
		if (!empty($this->data['currency_id']))
		{
			// The page already exists
			$this->display_error_message('PPDE_CURRENCY_EXIST');
		}

		// Make extra sure there is no currency_id set
		unset($this->data['currency_id']);

		// Set the Order value before insert new data
		$this->set_order();

		// Insert data to the database
		$sql = 'INSERT INTO ' . $this->currency_table . ' ' . $this->db->sql_build_array('INSERT', $this->data);
		$this->db->sql_query($sql);

		// Set the currency_id using the id created by the SQL insert
		$this->data['currency_id'] = (int) $this->db->sql_nextid();

		return $this;
	}

	/**
	 * Set Currency order number
	 *
	 * @return currency_interface $this object for chaining calls; load()->set()->save()
	 * @access private
	 */
	private function set_order()
	{
		$this->data['currency_order'] = (int) $this->get_max_order() + 1;

		return $this;
	}

	/**
	 * Get max currency order value
	 *
	 * @return int Order identifier
	 * @access private
	 */
	private function get_max_order()
	{
		$sql = 'SELECT MAX(currency_order) AS max_order
			FROM ' . $this->currency_table;
		$this->db->sql_query($sql);

		return $this->db->sql_fetchfield('max_order');
	}

	/**
	 * Save the current settings to the database
	 *
	 * This must be called before closing or any changes will not be saved!
	 * If adding a page (saving for the first time), you must call insert() or an exception will be thrown
	 *
	 * @return currency_interface $this object for chaining calls; load()->set()->save()
	 * @access public
	 */
	public function save()
	{
		if (empty($this->data['currency_name']) || empty($this->data['currency_iso_code']) || empty($this->data['currency_symbol']))
		{
			// The currency field missing
			$this->display_error_message('PPDE_NO_CURRENCY');
		}

		$sql = 'UPDATE ' . $this->currency_table . '
			SET ' . $this->db->sql_build_array('UPDATE', $this->data) . '
			WHERE currency_id = ' . $this->get_id();
		$this->db->sql_query($sql);

		return $this;
	}

	/**
	 * Get id
	 *
	 * @return int Currency identifier
	 * @access public
	 */
	public function get_id()
	{
		return (isset($this->data['currency_id'])) ? (int) $this->data['currency_id'] : 0;
	}

	/**
	 * Get Currency ISO code
	 *
	 * @return string ISO code name
	 * @access public
	 */
	public function get_iso_code()
	{
		return (isset($this->data['currency_iso_code'])) ? (string) $this->data['currency_iso_code'] : '';
	}

	/**
	 * Set Currency symbol
	 *
	 * @param string $symbol
	 *
	 * @return currency_interface $this object for chaining calls; load()->set()->save()
	 * @access public
	 */
	public function set_symbol($symbol)
	{
		// Set the lang_id on our data array
		$this->data['currency_symbol'] = (string) $symbol;

		return $this;
	}

	/**
	 * Get Currency Symbol
	 *
	 * @return string Currency symbol
	 * @access public
	 */
	public function get_symbol()
	{
		return (isset($this->data['currency_symbol'])) ? (string) $this->data['currency_symbol'] : '';
	}

	/**
	 * Set Currency ISO code name
	 *
	 * @param string $iso_code
	 *
	 * @return currency_interface $this object for chaining calls; load()->set()->save()
	 * @access public
	 */
	public function set_iso_code($iso_code)
	{
		// Set the lang_id on our data array
		$this->data['currency_iso_code'] = (string) $iso_code;

		return $this;
	}

	/**
	 * Get Currency name
	 *
	 * @return string Currency name
	 * @access public
	 */
	public function get_name()
	{
		return (isset($this->data['currency_name'])) ? (string) $this->data['currency_name'] : '';
	}

	/**
	 * Set Currency name
	 *
	 * @param string $name
	 *
	 * @return currency_interface $this object for chaining calls; load()->set()->save()
	 * @access public
	 */
	public function set_name($name)
	{
		// Set the item type on our data array
		$this->data['currency_name'] = (string) $name;

		return $this;
	}

	/**
	 * Get Currency status
	 *
	 * @return boolean
	 * @access public
	 */
	public function get_currency_enable()
	{
		return (isset($this->data['currency_enable'])) ? (bool) $this->data['currency_enable'] : false;
	}

	/**
	 * Set Currency status
	 *
	 * @param bool $enable
	 *
	 * @return bool
	 * @access public
	 */
	public function set_currency_enable($enable)
	{
		// Set the item type on our data array
		$this->data['currency_enable'] = (bool) $enable;

		return $this;
	}

	/**
	 * Set page url
	 *
	 * @param string $u_action Custom form action
	 *
	 * @return null
	 * @access public
	 */
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}

	/**
	 * Get the order number of the currency
	 *
	 * @return int Order identifier
	 * @access public
	 */
	public function get_currency_order()
	{
		return (isset($this->data['currency_order'])) ? (int) $this->data['currency_order'] : 0;
	}
}