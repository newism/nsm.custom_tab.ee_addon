<?php

/**
 * NSM Custom Tab extension file
 * 
 * This file must be placed in the
 * /system/extensions/ folder in your ExpressionEngine installation.
 *
 * @package NSMCustomTab
 * @version 1.0.0
 * @author Leevi Graham <http://leevigraham.com>
 * @copyright Copyright (c) 2009 Leevi Graham
 * @license {@link http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-Share Alike 3.0 Unported} All source code commenting and attribution must not be removed. This is a condition of the attribution clause of the license.
 */

if ( ! defined('EXT')) exit('Invalid file request');

/**
 * This extension adds a new tab to the CP publish / edit pages.
 *
 * @package NSMCustomTab
 * @version 1.0.0
 * @author Leevi Graham <http://leevigraham.com>
 * @copyright Copyright (c) 2009 Leevi Graham
 * @license {@link http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-Share Alike 3.0 Unported} All source code commenting and attribution must not be removed. This is a condition of the attribution clause of the license.
 *
 */
class Nsm_custom_tab_ext{

	/**
	 * The addon name
	 * 
	 * @var		string
	 * @since	Version 1.0.0
	 */
	public $addon_name = 'NSM Custom Tab';

	/**
	 * The addon_id
	 *
	 * Used for folder paths
	 * 
	 * @var		string
	 * @since	Version 1.0.0
	 */
	public $addon_id = 'nsm_custom_tab';

	/**
	 * The xml source for this addons latest version
	 * 
	 * @var		string
	 * @since	Version 1.0.0
	 */
	public $versions_xml_source = 'http://newism.com.au/versions.xml';

	/**
	 * Extension name
	 * 
	 * @var		string
	 * @since	Version 1.0.0
	 */
	public $name = 'NSM Addon Tab: Master Controller';

	/**
	 * Extension version
	 * 
	 * @var		string
	 * @since	Version 1.0.0
	 */
	public $version = '1.0.0';

	/**
	 * Extension description
	 * 
	 * @var		string
	 * @since	Version 1.0.0
	 */
	public $description = '';

	/**
	 * If $settings_exist = 'y' then a settings page will be shown in the ExpressionEngine admin
	 * 
	 * @since  	Version 1.0.0
	 * @var 	string
	 */
	public $settings_exist = 'y';

	/**
	 * Link to extension documentation
	 * 
	 * @since  	Version 1.0.0
	 * @var 	string
	 */
	public $docs_url = "";

	/**
	 * Default settings
	 * 
	 * @var 	array
	 * @since	Version 1.0.0
	 */
	private $default_settings = array(
		'enabled' => TRUE,
		'check_for_updates' => TRUE,
		'weblogs' => array(),
		'member_groups' => array()
	);

	/**
	 * Extension hooks
	 * 
	 * @var 	array
	 * @since	Version 1.0.0
	 */
	private $hooks = array(
		'lg_addon_update_register_source',
		'lg_addon_update_register_addon',
		'publish_form_new_tabs',
		'publish_form_new_tabs_block',
		'submit_new_entry_start',
		'submit_new_entry_end',
		'publish_form_start'
	);

	/**
	 * Paypal details for donate button
	 * 
	 * @var 	array
	 * @since	Version 1.0.0
	 */
	private $paypal 			=  array(
		"account"				=> "sales@newism.com.au",
		"donations_accepted"	=> TRUE,
		"donation_amount"		=> "20.00",
		"currency_code"			=> "USD",
		"return_url"			=> "http://newism.com.au/donate/thanks/",
		"cancel_url"			=> "http://newism.com.au/donate/cancel/"
	);

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	array|string $settings Extension settings associative array or an empty string
	 * @since	Version 1.0.0
	 */
	public function __construct($settings='')
	{
		global $IN, $SESS, $LANG, $PREFS;

		// define a constant for the current site_id rather than calling $PREFS->ini() all the time
		if(defined('SITE_ID') == FALSE)
			define('SITE_ID', $PREFS->ini("site_id"));

		// set the extension settings
		// the $settings string will be blank for all utility actions
		// enable, disable and update settings
		// also we might need to load the settings manually if another addon component
		// creates a new instance of the object
		$this->settings = ($settings == FALSE) ? $this->get_settings() : $this->save_settings_to_session($settings);
		// set the current site settings for convienience.
		$this->site_settings = $this->settings[SITE_ID];
	}

	// Hooks

	/**
	 * Method for the submit_new_entry_start hook
	 * 
	 * Runs before a new entry is submitted to the DB and checks if the 
	 * custom field value is valid. If the value is not valid the script is 
	 * stopped and the publish form is rendered with an error message. If the 
	 * value is valid {@link submit_new_entry_end()} is called otherwise continue
	 * to {@link publish_form_start()}
	 * 
	 * @see http://expressionengine.com/developers/extension_hooks/submit_new_entry_start/
	 */
	function submit_new_entry_start()
	{
		global $IN, $EE, $EXT;
		if($this->show_tab() == FALSE) return;
		$errors = FALSE;
		$entry_id = $IN->GBL("entry_id");
		$data = $IN->GBL(__CLASS__, 'POST');
		if($data['data_1'] == '') $errors = 'Data 1 is required in the custom tab!';
		if($errors){
			$EE->new_entry_form('preview', $errors, $entry_id);
			$EXT->end_script = TRUE;
			
		}
	}

	/**
	 * Method for the submit_new_entry_end hook
	 * 
	 * - Runs after a new entry has been validated and created in the database
	 * - Manipulates data from the posted custom field ready for DB insert
	 * - Checks to see if the record was created properly
	 *
	 * @param	int		$entry_id 		The saved entry id
	 * @param	array	$data 			Array of data about entry (title, url_title)
	 * @param	string	$ping_message	Error message if trackbacks or pings have failed to be sent
	 * @see		http://expressionengine.com/developers/extension_hooks/submit_new_entry_end/
	 * @since	Version 1.0.0
	 */
	function submit_new_entry_end( $entry_id, $data = array(), $ping_message = "" )
	{
		global $IN;
		$cookie = $IN->GBL(__CLASS__, 'COOKIE');
		$data = ($cookie) ? $this->unserialize($cookie) : array();
		$data[$entry_id] = $IN->GBL(__CLASS__, 'POST');
		setcookie(__CLASS__, $this->serialize($data), time() + 30000000, '/');
	}

	/**
	 * Method for the publish_form_start hook
	 *
	 * - Runs before any data is processed
	 *
	 * @access	public
	 * @param	string $which The current action (new, preview, edit, or save)
	 * @param	string $submission_error A submission error if any
	 * @param	string $entry_id The current entries id
	 * @since	Version 1.0.0
	 * @see		http://expressionengine.com/developers/extension_hooks/publish_form_start/
	 */
	public function publish_form_start( &$which, $submission_error, $entry_id, $hidden )
	{
		global $EE, $EXT, $IN;

		if(empty($entry_id)) $entry_id = $IN->GBL("entry_id");

		// here's where we validate the form submission
		// for now we will just set the errors to false
		// but they could be an array
		$errors = FALSE;
		if($errors != FALSE)
		{
			$EE->new_entry_form('preview', implode("<br />" . $errors), $entry_id);
			$EXT->end_script = TRUE;
		}

		// support quicksave
		if($which == "save" && !empty($entry_id))
			$this->submit_new_entry_end($entry_id);

	}

	/**
	 * Register a new Addon
	 *
	 * @access	public
	 * @param	array $addons The existing sources
	 * @return	array The new addon list
	 * @see		http://leevigraham.com/cms-customisation/expressionengine/lg-addon-updater/
	 * @since	Version 1.0.0
	 */
	public function lg_addon_update_register_addon($addons)
	{
		// -- Check if we're not the only one using this hook
		$this->get_last_call($addons);

		// add a new addon
		// the key must match the id attribute in the source xml
		// the value must be the addons current version
		if($this->site_settings['check_for_updates'] == TRUE)
			$addons[$this->addon_name] = $this->version;

		return $addons;
	}

	/**
	 * Register a new Addon Source
	 *
	 * @access	public
	 * @param	array $sources The existing sources
	 * @return	array The new source list
	 * @see 	http://leevigraham.com/cms-customisation/expressionengine/lg-addon-updater/
	 * @since	Version 1.0.0
	 */
	public function lg_addon_update_register_source($sources)
	{
		// -- Check if we're not the only one using this hook
		$this->get_last_call($sources);

		// add a new source
		// must be in the following format:
		/*
		<versions>
			<addon id='LG Addon Updater' version='2.0.0' last_updated="1218852797" docs_url="http://leevigraham.com/" />
		</versions>
		*/
		if($this->site_settings['check_for_updates'] == TRUE)
			$sources[] = $this->versions_xml_source;

		return $sources;

	}

	// TAB administration

	/**
	* Adds a new tab to the publish / edit form
	*
	* @param	array $publish_tabs Array of existing tabs
	* @param	int $weblog_id Current weblog id
	* @param	int $entry_id Current entry id
	* @param	array $hidden Hidden form fields
	* @return	array Modified tab list
	* @see		http://expressionengine.com/developers/extension_hooks/publish_form_new_tabs/
	* @since 	Version 2.0.0
	*/
	function publish_form_new_tabs($publish_tabs, $weblog_id, $entry_id, $hidden)
	{
		global $EXT, $PREFS, $SESS;

		$this->get_last_call($publish_tabs);

		if($this->show_tab($weblog_id))
			$publish_tabs['nsm_ct'] = 'Custom Tab';

		return $publish_tabs;
	}

	/**
	* Adds the tab content containing all Twitter Options
	*
	* @param	int $weblog_id The weblog ID for this Publish form
	* @return	string Content of the new tab
	* @see		http://expressionengine.com/developers/extension_hooks/publish_form_new_tabs_block/
	* @since 	Version 2.0.0
	*/
	function publish_form_new_tabs_block($weblog_id)
	{
		global $IN, $REGX;

		$this->get_last_call($out, '');

		if($this->show_tab($weblog_id))
		{
			$data = $this->get_tab_data();
			ob_start();
			include(PATH_LIB . $this->addon_id.'/views/'.__CLASS__.'/tab_custom.php');
			$out .= ob_get_clean();
		}

		return $out;
	}

	// TAB Helpers

	/**
	 * Show the tab to the current user?
	 *
	 * @param $weblog_id integer The current weblog
	 * @return boolean display the tab
	 */
	private function show_tab($weblog_id = FALSE)
	{
		global $SESS;
		return (
			$this->site_settings['enabled'] == TRUE
			&& in_array($weblog_id, $this->site_settings['weblogs'])
			&& in_array($SESS->userdata['group_id'], $this->site_settings['member_groups'])
		) ? TRUE : FALSE;
	}

	/**
	 * Get the data for the tab, merge any posted data
	 *
	 * @return array The data for the tab after it has been merged with any revision / preview data
	 */
	private function get_tab_data()
	{
		global $IN;

		$entry_id = $IN->GBL('entry_id');
		$entry_data = $this->get_entry_data($entry_id);
		return ($post_data = $IN->GBL('Nsm_custom_tab_ext', 'POST')) ?  array_merge($entry_data, $post_data) : $entry_data;
	}

	/**
	 * Get the data for the entry from the cookie
	 *
	 * @return array The data for the entry from the cookie or a default array
	 */
	private function get_entry_data($entry_id)
	{
		if($all_data = $IN->GBL('Nsm_custom_tab_ext', 'COOKIE'))
		{
			$all_data = $this->unserialize($all_data);
			$entry_data = $all_data[$entry_id];
		}
		else
		{
			$entry_data = array("data_1" => "", "data_2" => "");
		}
		return $entry_data;
	}

	// Extension administration

	/**
	 * Activate the extension
	 * 
	 * @access 	public
	 * @see 	http://expressionengine.com/docs/development/extensions.html#enable
	 * @since	Version 1.0.0
	 */
	public function activate_extension()
	{
		$this->create_hooks();
	}

	/**
	 * Update the extension
	 *
	 * @access 	public
	 * @see		http://expressionengine.com/docs/development/extensions.html#enable
	 * @since	Version 1.0.0
	 * @param	string $current The current installed version
	 */
	public function update_extension($current = '')
	{
		$this->update_hooks();
	}

	/**
	 * Disable the extension
	 * 
	 * @access 	public
	 * @since	Version 1.0.0
	 * @see		http://expressionengine.com/docs/development/extensions.html#disable
	 */
	public function disable_extension(){}

	/**
	 * Render the settings form
	 * 
	 * @access 	public
	 * @param	string $current_settings The current settings
	 * @see		http://expressionengine.com/docs/development/extensions.html#settings
	 * @since	Version 1.0.0
	 */
	public function settings_form($current_settings)
	{
		global $DB, $DSP, $LANG, $PREFS, $REGX, $SESS;

		$site_id = $PREFS->ini("site_id");

		$lgau_query = $DB->query("SELECT class FROM exp_extensions WHERE class = 'Lg_addon_updater_ext' AND enabled = 'y' LIMIT 1");
		$lgau_enabled = $lgau_query->num_rows ? TRUE : FALSE;

		$weblog_query = $DB->query("SELECT * FROM exp_weblogs WHERE site_id = " . $PREFS->ini('site_id'));
		$member_group_query = $DB->query("SELECT group_id, group_title FROM exp_member_groups WHERE site_id = " . $PREFS->core_ini['site_id'] . " ORDER BY group_id");

		$DSP->title = $this->name . " " . $this->version . " | " . $LANG->line('extension_settings');
		$DSP->crumbline = TRUE;
		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities'));
		$DSP->crumb .= $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')));
		$DSP->crumb .= $DSP->crumb_item($this->name . " " . $this->version);

		$DSP->body .= "<div class='mor settings-form'>";
		// PAYPAL
		if(isset($this->paypal["donations_accepted"]) === TRUE)
		{
			$DSP->body .= "<p class='donate paypal'>
								<a rel='external'"
									. "href='https://www.paypal.com/cgi-bin/webscr?"
										. "cmd=_donations&amp;"
										. "business=".rawurlencode($this->paypal["account"])."&amp;"
										. "item_name=".rawurlencode($this->addon_name . " Development: Donation")."&amp;"
										. "amount=".rawurlencode($this->paypal["donation_amount"])."&amp;"
										. "no_shipping=1&amp;return=".rawurlencode($this->paypal["return_url"])."&amp;"
										. "cancel_return=".rawurlencode($this->paypal["cancel_url"])."&amp;"
										. "no_note=1&amp;"
										. "tax=0&amp;"
										. "currency_code=".$this->paypal["currency_code"]."&amp;"
										. "lc=US&amp;"
										. "bn=PP%2dDonationsBF&amp;"
										. "charset=UTF%2d8'"
									."class='button'
									target='_blank'>
									Support this addon by donating via PayPal.
								</a>
							</p>";
		}
		$DSP->body .= $DSP->heading("{$this->name} <small>{$this->version}</small>");
		$DSP->body .= $DSP->form_open(
								array('action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings'),
								array('name' => strtolower(__CLASS__)
							));
		ob_start(); include(PATH_LIB.$this->addon_id.'/views/'.__CLASS__.'/form_settings.php'); $DSP->body .= ob_get_clean();
		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit("Save extension settings"));
		$DSP->body .= $DSP->form_c();
		$DSP->body .= "</div>";
	}

	/**
	 * Save the settings
	 * 
	 * @access 	public
	 * @since	Version 1.0.0
	 * @see http://expressionengine.com/docs/development/extensions.html#settings
	 */
	public function save_settings()
	{
		global $IN, $PREFS;

		$new_settings = $IN->GBL(__CLASS__, "POST");
		if(isset($new_settings['weblogs']) == FALSE) $new_settings['weblogs'] = array();
		if(isset($new_settings['member_groups']) == FALSE) $new_settings['member_groups'] = array();

		$this->settings[SITE_ID] = $new_settings;
		$this->save_settings_to_db($this->settings);
	}

	/**
	 * Saves the objects hooks to the DB
	 * 
	 * @access 	private
	 * @since	Version 1.0.0
	 */
	private function create_hooks()
	{
		global $DB;

		$hook_template = array(
			'class'    => __CLASS__,
			'settings' => FALSE,
			'version'  => $this->version,
		);

		foreach($this->hooks as $key => $value)
		{
			if(is_array($value))
			{
				$hook["hook"] = $key;
				$hook["method"] = (isset($value["method"]) === TRUE) ? $value["method"] : $key;
				$hook = array_merge($hook, $value);
			}
			else
			{
				$hook["hook"] = $hook["method"] = $value;
			}
			$hook = array_merge($hook_template, $hook);
			$DB->query($DB->insert_string('exp_extensions', $hook));
		}
	}

	/**
	 * Updates the objects hooks in the DB
	 * 
	 * Delete the current hooks, recreate them from scratch
	 * 
	 * @access 	private
	 * @since	Version 1.0.0
	 */
	private function update_hooks()
	{
		$this->delete_hooks();
		$this->create_hooks();
	}

	/**
	 * Delete the objects hooks from the DB
	 * 
	 * @access 	private
	 * @since	Version 1.0.0
	 */
	private function delete_hooks()
	{
		global $DB;
		$DB->query("DELETE FROM `exp_extensions` WHERE `class` = '".__CLASS__."'");
		return $DB->affected_rows;
	}

	//  Settings Management

	/**
	 * Get the extension settings from the $SESS or database
	 *
	 * @access	private
	 * @param	array $addons The existing sources
	 * @return	array The new addon list
	 * @since	Version 1.0.0
	 */
	private function get_settings($refresh = FALSE)
	{
		global $DB, $PREFS, $REGX;
		$settings = FALSE;
		$site_id = $PREFS->ini("site_id");
		if(isset($SESS->cache[$this->addon_name][__CLASS__]['settings']) === FALSE || $refresh === TRUE)
		{
			$settings_query = $DB->query("SELECT `settings` FROM `exp_extensions` WHERE `enabled` = 'y' AND `class` = '".__CLASS__."' LIMIT 1");
			// if there is a row and the row has settings
			if ($settings_query->num_rows > 0 && $settings_query->row['settings'] != '')
			{
				// save them to the cache
				$settings = $REGX->array_stripslashes(unserialize($settings_query->row['settings']));
			}
		}
		else
		{
			$settings = $SESS->cache[$this->addon_name][__CLASS__]['settings'];
		}
		if(isset($settings[$site_id]) == FALSE)
		{
			$settings[$site_id] = $this->build_default_settings();
			$this->save_settings_to_db($settings);
		}
		$this->save_settings_to_session($settings);
		return $settings;
	}

	/**
	 * Build the default settings array for this site
	 * Here we can generate settings for the sites weblogs etc
	 *
	 * @access	private
	 * @param	array $settings The existing sources
	 * @since	Version 1.0.0
	 */
	private function build_default_settings()
	{
		$site_settings = $this->default_settings;
		return $site_settings;
	}

	/**
	 * Save the extension settings to the current $SESS
	 * Allows us to call the settings again without a db call for modules and plugins
	 *
	 * @access	private
	 * @param	array $settings The existing sources
	 * @since	Version 1.0.0
	 */
	private function save_settings_to_session($settings = FALSE)
	{
		$SESS->cache[$this->addon_name][__CLASS__]['settings'] = $settings;
		return $settings;
	}

	/**
	 * Save the extension settings to the database
	 *
	 * @access	private
	 * @param	array $settings The existing sources
	 * @since	Version 1.0.0
	 */
	private function save_settings_to_db($settings)
	{
		global $DB;
		$DB->query($DB->update_string("exp_extensions", array("settings" => $this->serialize($settings)), array("class" => __CLASS__)));
	}

	//  Helpers

	/**
	 * Serialise the array
	 * 
	 * @access	private
	 * @param	array The array to serialise
	 * @return	array The serialised array
	 */ 
	private function serialize($vals)
	{
		global $PREFS;
		if ($PREFS->ini('auto_convert_high_ascii') == 'y')
			$vals = $this->array_ascii_to_entities($vals);
	 	return addslashes(serialize($vals));
	}

	/**
	 * Unerialise the array
	 * 
	 * @access	private
	 * @param	array $vals The array to unserialise
	 * @param	boolean $convert convert the entities to ascii
	 * @return	array The serialised array
	 */ 
	private function unserialize($vals, $convert=TRUE)
	{
		global $REGX, $PREFS;
		if (($tmp_vals = @unserialize($vals)) !== FALSE)
		{
			$vals = $REGX->array_stripslashes($tmp_vals);
			if ($convert AND $PREFS->ini('auto_convert_high_ascii') == 'y')
				$vals = $this->array_entities_to_ascii($vals);
		}
	 	return $vals;
	}

	/**
	 * Get the last call from a previous hook
	 * 
	 * @access  private
	 * @param   mixed $param The variable we are going to fill with the last call
	 * @param   mixed $default The value to use if no last call is available
	 */
	private function get_last_call(&$param, $default = NULL)
	{
		global $EXT;
		if ($EXT->last_call !== FALSE)
			$param = $EXT->last_call;
		else if ($param !== NULL && $default !== NULL)
			$param = $default;
	}

	/**
	 * Creates a select box
	 *
	 * @access public
	 * @param mixed $selected The selected value
	 * @param array $options The select box options in a multi-dimensional array. Array keys are used as the option value, array values are used as the option label
	 * @param string $input_name The name of the input eg: Lg_polls_ext[log_ip]
	 * @param string $input_id A unique ID for this select. If no id is given the id will be created from the $input_name
	 * @param boolean $use_lanng Pass the option label through the $LANG->line() method or display in a raw state
	 * @param array $attributes Any other attributes for the select box such as class, multiple, size etc
	 * @return string Select box html
	 */
	function select_box($selected, $options, $input_name, $input_id = FALSE, $use_lang = TRUE, $key_is_value = TRUE, $attributes = array())
	{
		global $LANG;

		$input_id = ($input_id === FALSE) ? str_replace(array("[", "]"), array("_", ""), $input_name) : $input_id;

		$attributes = array_merge(array(
			"name" => $input_name,
			"id" => strtolower($input_id)
		), $attributes);

		$attributes_str = "";
		foreach ($attributes as $key => $value)
		{
			$attributes_str .= " {$key}='{$value}' ";
		}

		$ret = "<select{$attributes_str}>";

		foreach($options as $option_value => $option_label)
		{
			if (!is_int($option_value))
				$option_value = $option_value;
			else
				$option_value = ($key_is_value === TRUE) ? $option_value : $option_label;

			$option_label = ($use_lang === TRUE) ? $LANG->line($option_label) : $option_label;
			$checked = ($selected == $option_value) ? " selected='selected' " : "";
			$ret .= "<option value='{$option_value}'{$checked}>{$option_label}</option>";
		}

		$ret .= "</select>";
		return $ret;
	}

}
