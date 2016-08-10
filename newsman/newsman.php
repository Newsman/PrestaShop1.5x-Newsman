<?php

/**
 * Copyright 2015 Dazoot Software
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Dramba Victor for Newsman
 * @copyright 2015 Dazoot Software
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */
class Newsman extends Module
{
	//strings
	private $apiKey, $userId, $listId;
	//int
	private $msgType;
	//boolean
	private $flagSaveMapping;

	const API_URL = 'https://ssl.newsman.ro/api/1.2/xmlrpc/';

	public function __construct()
	{
		$this->name = 'newsman';
		$this->tab = 'advertising_marketing';
		$this->version = '1.0.0';
		$this->author = 'Victor Dramba for Newsman';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.4', 'max' => _PS_VERSION_);
		$this->module_key = 'bb46dd134d42c2936ece1d3322d3a384';

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Newsman');
		//TODO detailed description (in config.xml too)
		$this->description = $this->l(
			'The official Newsman module for PrestaShop. Manage your Newsman subscriber lists, map your shop groups to the Newsman segments.'
		);

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall Newsman module?');
	}

	public function uninstall()
	{
		return parent::uninstall()
		&& Configuration::deleteByName('NEWSMAN_DATA')
		&& Configuration::deleteByName('NEWSMAN_MAPPING')
		&& Configuration::deleteByName('NEWSMAN_CONNECTED')
		&& Configuration::deleteByName('NEWSMAN_API_KEY')
		&& Configuration::deleteByName('NEWSMAN_USER_ID')
		&& Configuration::deleteByName('NEWSMAN_CRON');
	}

	public function getContent()
	{
		$out = '';
		if (Tools::isSubmit('submitOptionsconfiguration'))
		{
			$this->msgType = 1;
			$data = array();

			Configuration::updateValue('NEWSMAN_CONNECTED', 0);
			Configuration::deleteByName('NEWSMAN_DATA');

			$this->apiKey = Tools::getValue('api_key');
			$this->userId = Tools::getValue('user_id');

			if (!Validate::isGenericName($this->apiKey) || $this->apiKey == '')
			{
				$data['msg'][] = $this->displayError($this->l('Invalid value for API KEY'));
			}
			if (!Validate::isInt($this->userId))
			{
				$data['msg'][] = $this->displayError($this->l('Invalid value for UserID'));
			}

			if (!isset($data['msg']) || !count($data['msg']))
			{
				$client = $this->getClient($this->userId, $this->apiKey);

				if ($client->query('list.all'))
				{
					$data['lists'] = $client->getResponse();
					Configuration::updateValue('NEWSMAN_API_KEY', $this->apiKey);
					Configuration::updateValue('NEWSMAN_USER_ID', $this->userId);
					Configuration::updateValue('NEWSMAN_CONNECTED', 1);
					$data['msg'][] = $this->displayConfirmation($this->l('Connected. Please choose the synchronization details below.'));
					$data['ok'] = true;
					//get segments for the first list
					$this->listId = $data['lists'][0]['list_id'];
					$client->query('segment.all', $this->listId);
					$data['segments'] = $client->getResponse();
					//save lists and segments
					Configuration::updateValue(
						'NEWSMAN_DATA',
						Tools::jsonEncode(array('lists' => $data['lists'], 'segments' => $data['segments']))
					);
					$output['saved'] = 'saved';
				} else
				{
					$data['msg'][] = $this->displayError(
						$this->l('Error connecting. Please check your API KEY and user ID.') . "<br>" .
						$client->getErrorMessage()
					);
				}
			}

		} elseif (Tools::isSubmit('submitOptionsConfigurationRefresh'))
		{
			$this->msgType = 2;
			$data = array();

			Configuration::updateValue('NEWSMAN_CONNECTED', 0);
			Configuration::deleteByName('NEWSMAN_DATA');

			$this->apiKey = Tools::getValue('hApi_key');
			$this->userId = Tools::getValue('HUserId');
			$this->listId = Tools::getValue('sel_list');

			if (!Validate::isGenericName($this->apiKey) || $this->apiKey == '')
			{
				$data['msg'][] = $this->displayError($this->l('Invalid value for API KEY'));
			}
			if (!Validate::isInt($this->userId))
			{
				$data['msg'][] = $this->displayError($this->l('Invalid value for UserID'));
			}

			if (!isset($data['msg']) || !count($data['msg']))
			{
				$client = $this->getClient($this->userId, $this->apiKey);

				if ($client->query('list.all'))
				{
					$data['lists'] = $client->getResponse();
					Configuration::updateValue('NEWSMAN_API_KEY', $this->apiKey);
					Configuration::updateValue('NEWSMAN_USER_ID', $this->userId);
					Configuration::updateValue('NEWSMAN_CONNECTED', 1);
					$data['msg'][] = $this->displayConfirmation($this->l('Connected. Please choose the synchronization details below.'));
					$data['ok'] = true;
					//get segments for the first list
					//$this->listId = $data['lists'][0]['list_id'];
					$client->query('segment.all', $this->listId);
					$data['segments'] = $client->getResponse();
					//save lists and segments
					Configuration::updateValue(
						'NEWSMAN_DATA',
						Tools::jsonEncode(array('lists' => $data['lists'], 'segments' => $data['segments']))
					);
					$output['saved'] = 'saved';
				} else
				{
					$data['msg'][] = $this->displayError(
						$this->l('Error connecting. Please check your API KEY and user ID.') . "<br>" .
						$client->getErrorMessage()
					);
				}
			}

		} elseif (Tools::isSubmit('submitSaveCronBtn'))
		{
			if ($this->SaveCron())
			{
				$this->msgType = 5;
			} else
			{
				$this->msgType = 6;
			}
		}

		$connected = Configuration::get('NEWSMAN_CONNECTED');

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		//$helper->table = $this->table;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

		// Language
		// Get default Language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;


		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;        // false -> remove toolbar
		$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit' . $this->name;

		// Load current value

		/*
		$helper->fields_value['api_key'] = Configuration::get('NEWSMAN_API_KEY');
		$helper->fields_value['user_id'] = Configuration::get('NEWSMAN_USER_ID');
		$helper->fields_value['cron_url'] = $this->context->shop->getBaseURL() . 'modules/newsman/cron_task.php';
		$helper->fields_value['cron_option'] = Configuration::get('NEWSMAN_CRON');
		*/

		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		$mappingSection = array(
			array(
				'type' => 'select',
				'label' => 'Newsman list',
				'name' => 'sel_list',
				'options' => array('query' => array())
			),
			array(
				'type' => 'html',
				'name' => 'unused',
				'html_content' => $this->l('Newsman destination segment')
			)
		);

		//check for newsletter module
		if (Module::isInstalled('blocknewsletter'))
		{
			$mappingSection[] = array(
				'type' => 'select',
				'label' => $this->l('Newsletter subscribers'),
				'name' => 'map_newsletter',
				'class' => 'id-map-select',
				'options' => array('query' => array())
			);
		}
		//list groups
		foreach (Group::getGroups($default_lang) as $row)
		{
			if ($row['id_group'] < 3)
			{
				continue;
			}
			$mappingSection[] = array(
				'type' => 'select',
				'label' => $row['name'] . ' ' . $this->l('Group'),
				'name' => 'map_group_' . $row['id_group'],
				'class' => 'id-map-select',
				'options' => array('query' => array())
			);
		}

		//Validation - inputs

		$this->userId = !empty($this->userId) ? $this->userId : Configuration::get('NEWSMAN_USER_ID');
		$this->apiKey = !empty($this->apiKey) ? $this->apiKey : Configuration::get('NEWSMAN_API_KEY');

		//End Validation - inputs


		$out = '<div id="newsman-msg"></div>';

		$out .= '
<form action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">
<fieldset>
	<legend>' . $this->l('API SETTINGS') . '</legend>';
		$out .= '<br /><br />
	<br class="clear" />
	<div id="connectNewsmanMsg" class="conf" style="display:none;">Connected to newsman successfully.</div>
	<label for="api_key">' . $this->l('API KEY') . '</label>
	<div class="margin-form">
<input type="text" name="api_key" id="api_key" class="" value="' . $this->apiKey . '" size="40" required="required">
	</div>
	 <label for="user_id">' . $this->l('USER ID') . '</label>
	<div class="margin-form">
	<input type="text" name="user_id" id="user_id" class="" value="' . $this->userId . '" size="40" required="required">
	</div>
	<br class="clear" />
	<div class="margin-form">
	   <input type="submit" class="btn btn-default pull-right" name="submitOptionsconfiguration" value="' . $this->l('Connect') . '"><i class="process-icon-ok"></i></input>
	</div>
	<br class="clear" />
</fieldset>
</form>';

		/*HELPER FORM METHOD
				$out .= $helper->generateForm(array(
					array('form' => array(
						'legend' => array(
							'title' => $this->l('API Settings'),
							'icon' => 'icon-cogs'
						),
						'input' => array(
							array(
								'type' => 'text',
								'label' => $this->l('API KEY'),
								'name' => 'api_key',
								'size' => 40,
								'required' => true
							),
							array(
								'type' => 'text',
								'label' => $this->l('User ID'),
								'name' => 'user_id',
								'size' => 40,
								'required' => true
							)
						),
						'buttons' => array(
							array(
								'title' => 'Connect',
								'class' => 'pull-right',
								'icon' => $connected ? 'process-icon-ok' : 'process-icon-next',
								'js' => 'connectAPI(this)'
							)
						)
					))));
				HELPER FORM METHOD*/

		/*HELPER FORM METHOD
		$out .= '
<form name="autoSync" id="autoSync" action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">
	<br class="clear" />
<div id="connectNewsmanMsg" class="conf" style="display:none;">Connected to newsman successfully.</div>
	<div class="margin-form">
	   <input type="submit" class="btn btn-default pull-right" name="submitOptionsconfiguration" value="' . $this->l('Connect') . '"><i class="process-icon-ok"></i></input>
	</div>
	<br class="clear" />
</form>';
		HELPER FORM METHOD*/

		/*HELPER FORM METHOD
		$out .= $helper->generateForm(array(array('form' => array(
			'legend' => array(
				'title' => $this->l('Synchronization mapping')
			),
			'input' => $mappingSection,
			'buttons' => array(
				array(
					'title' => $this->l('Save mapping'),
					'class' => 'pull-right',
					'icon' => 'process-icon-save',
					'js' => 'saveMapping(this)'
				),
				array(
					'title' => $this->l('Refresh segments'),
					'icon' => 'process-icon-refresh',
					'js' => 'connectAPI(this)'
				)
			)
		))));
		HELPER FORM METHOD*/

		/*HELPER FORM METHOD
		$out .= '
<form name="autoSync" id="autoSync" action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">
<br class="clear" />
	<div id="saveMappingMsg" class="conf" style="display:none;">Data has been mapped successfully for synchronization.</div>
<div class="margin-form">
  <input type="submit" class="btn btn-default pull-right" name="submitOptionsConfigurationRefresh" value="' . $this->l('Refresh Segments') . '"><i class="process-icon-ok"></i></input>
	  <input type="submit" class="btn btn-default pull-right" name="submitSaveMapping" value="' . $this->l('Save mapping') . '"><i class="process-icon-ok"></i></input>
	</div>
	<br class="clear" />
	<input type="hidden" name="HUserId" id="HUserId" class="" value="' . $this->userId . '" size="40" required="required">
	<input type="hidden" name="hApi_key" id="hApi_key" class="" value="' . $this->apiKey . '" size="40" required="required">
</form>';
		HELPER FORM METHOD*/

		$out .= '
<form action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">
<fieldset>
	<legend>' . $this->l('SYNCHRONIZATION MAPPING') . '</legend>';
		$out .= '<br /><br />
	<br class="clear" />
	<div id="refreshSegmentsMsg" class="conf" style="display:none;">Newsman segments refreshed successfully.</div>
	<div id="saveMappingMsg" class="conf" style="display:none;">Data has been mapped successfully for synchronization.</div>
	<label for="sel_list">' . $this->l('Newsman list') . '</label>
	<div class="margin-form">
<select name="sel_list" class="fixed-width-xl" id="sel_list"></select>
	</div>
	<div>
	<h5 style="padding-left: 150px;">Newsman destination segment</h5>
	 </div>
	  <label for="sel_list">' . $this->l('Newsletter subscribers') . '</label>
	<div class="margin-form">
  <select name="map_newsletter" class="id-map-select fixed-width-xl" id="map_newsletter"></select>
	</div>
	';

		$mapOptions = array();

		if (Tools::isSubmit('submitSaveMapping'))
		{
			$this->msgType = 3;
			$mapOptions['list'] = $_POST['sel_list'];
			$mapOptions['map_newsletter'] = $_POST['map_newsletter'];
		}

		$count = 0;
		foreach ($mappingSection as $item)
		{
			$count++;
			if ($count > 3)
			{
				if (Tools::isSubmit('submitSaveMapping'))
				{
					$mapOptions[$item['name']] = $_POST[$item['name']];
				}

				$out .= '<label for="sel_list">' . $this->l($item['label']) . '</label>
	<div class="margin-form">
  <select name="' . $item['name'] . '" class="id-map-select fixed-width-xl" id="' . $item['name'] . '"></select>
	</div>';
			}
		}

		if (Tools::isSubmit('submitSaveMapping'))
		{
			$this->SaveMapping($mapOptions);
		}

		$out .= '<br class="clear" />
	<div class="margin-form">
  <input type="submit" class="btn btn-default pull-right" name="submitOptionsConfigurationRefresh" value="' . $this->l('Refresh Segments') . '"><i class="process-icon-ok"></i></input>
	  <input type="submit" class="btn btn-default pull-right" name="submitSaveMapping" value="' . $this->l('Save mapping') . '"><i class="process-icon-ok"></i></input>
	</div>
	<br class="clear" />
	<input type="hidden" name="HUserId" id="HUserId" class="" value="' . $this->userId . '" size="40" required="required">
	<input type="hidden" name="hApi_key" id="hApi_key" class="" value="' . $this->apiKey . '" size="40" required="required">
</fieldset>
</form>';

		//AUTOMATIC SYNCHRONIZATION

		/*HELPER METHOD
		$out .= $helper->generateForm(array(array('form' => array(
			'legend' => array(
				'title' => $this->l('Automatic synchronization')
			),
			'input' => array(
				array(
					'label' => 'Automatic synchronization',
					'type' => 'select',
					'name' => 'cron_option',
					'options' => array(
						'query' => array(
							array('value' => '', 'label' => $this->l('never (disabled)')),
							array('value' => 'd', 'label' => $this->l('every day')),
							array('value' => 'w', 'label' => $this->l('every week')),
						),
						'id' => 'value',
						'name' => 'label'
					)
				)
			),
			'buttons' => array(
				array(
					'title' => $this->l('Synchronize now'),
					'icon' => 'process-icon-next',
					'js' => 'synchronizeNow(this)'
				),
				array(
					'title' => $this->l('Save option'),
					'icon' => 'process-icon-save',
					'class' => 'pull-right',
					'js' => 'saveCron(this)'
				),

			)
		))));
		HELPER METHOD*/

		/*HELPER METHOD
		$out .= '
<form name="autoSync" id="autoSync" action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">
<br class="clear" />
<div id="syncMsg" class="conf" style="display:none;">Users uploaded and scheduled for import. It might take a few minutes until they show up in your Newsman lists.</div>
	<div class="margin-form">
	  <input type="submit" class="btn btn-default" name="submitSynchronizeBtn" value="Synchronize now"/>
	  <input type="submit" class="btn btn-default pull-right" name="submitSaveCronBtn" value="Save Options"/>
	</div>
	<br class="clear" />
</form>';
		HELPER METHOD*/


		$out .= '
<form name="autoSync" id="autoSync" action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">
<fieldset>
	<legend>' . $this->l('AUTOMATIC SYNCHRONIZATION') . '</legend>';
		$out .= '<br /><br />
	<br class="clear" />
	<div id="syncMsg" class="conf" style="display:none;">Users uploaded and scheduled for import. It might take a few minutes until they show up in your Newsman lists.</div>
	<div id="cronSync" class="conf" style="display:none;">Automatic synchronization option saved.</div>
	<div id="cronSyncFail" class="conf" style="display:none;">To enable automatic synchronization you need to install ' .
			'and configure "Cron tasks manager" module from PrestaShop.</div>
	<label for="sel_list">' . $this->l('Automatic synchronization') . '</label>
	<div class="margin-form">
<select name="cron_option" class=" fixed-width-xl" id="cron_option">
																																																												<option value="" selected="selected">never (disabled)</option>

																																																			<option value="d">every day</option>

																																																			<option value="w">every week</option>

																																													</select>
	</div>
	<br class="clear" />
	<div class="margin-form">
	  <input type="submit" class="btn btn-default" name="submitSynchronizeBtn" value="Synchronize now"/>
	  <input type="submit" class="btn btn-default pull-right" name="submitSaveCronBtn" value="Save Options"/>
	</div>
	<br class="clear" />
</fieldset>
</form>';

		//the script

		if (Tools::isSubmit('submitSynchronizeBtn'))
		{
			$this->msgType = 4;
		}

		$this->context->controller->addJS($this->_path . 'views/js/newsman.js');

		$ajaxURL = $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name;
		$mapExtra = array(
			array('', $this->l('Do not import')),
			array('none', $this->l('Import, no segment'))
		);

		$data = Configuration::get('NEWSMAN_DATA');
		$mapping = Configuration::get('NEWSMAN_MAPPING');

		$out .= '<script>var newsman=' . Tools::jsonEncode(array(
				'msg' => $this->msgType,
				'data' => $data ? Tools::jsonDecode($data) : false,
				'mapExtra' => $mapExtra,
				'mapping' => $mapping ? Tools::jsonDecode($mapping) : false,
				'ajaxURL' => $ajaxURL,
				'strings' => array(
					'needConnect' => $this->l('You need to connect to Newsman first!'),
					'needMapping' => $this->l('You need to save mapping first!')
				)
			)) . '</script>';


		if (Tools::isSubmit('submitSynchronizeBtn'))
		{
			$this->doSynchronize();
		}

		return $out;
	}

	private function jsonOut($output)
	{
		header('Content-Type: application/json');
		echo Tools::jsonEncode($output);
	}

	private function getConfig($configData)
	{
		$servername = _DB_SERVER_;
		$username = _DB_USER_;
		$password = _DB_PASSWD_;
		$dbname = _DB_NAME_;

		$mappingData = array();

// Create connection
		$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
		if ($conn->connect_error)
		{
			die("Connection failed: " . $conn->connect_error);
		}

		$sql = "SELECT * FROM ps_configuration WHERE name = '" . $configData . "'";
		$result = $conn->query($sql);

		if ($result->num_rows > 0)
		{
			// output data of each row
			while ($row = $result->fetch_assoc())
			{
				$mappingData = $row["value"];
			}
		} else
		{
			echo "0 results";
		}
		$conn->close();

		return $mappingData;
	}

	public function ajaxProcessConnect()
	{
		$output = array();
		Configuration::updateValue('NEWSMAN_CONNECTED', 0);
		Configuration::deleteByName('NEWSMAN_DATA');
		$api_key = Tools::getValue('api_key');
		$user_id = Tools::getValue('user_id');
		if (!Validate::isGenericName($api_key) || $api_key == '')
		{
			$output['msg'][] = $this->displayError($this->l('Invalid value for API KEY'));
		}
		if (!Validate::isInt($user_id))
		{
			$output['msg'][] = $this->displayError($this->l('Invalid value for UserID'));
		}
		if (!isset($output['msg']) || !count($output['msg']))
		{
			$client = $this->getClient($user_id, $api_key);
			if ($client->query('list.all'))
			{
				$output['lists'] = $client->getResponse();
				Configuration::updateValue('NEWSMAN_API_KEY', $api_key);
				Configuration::updateValue('NEWSMAN_USER_ID', $user_id);
				Configuration::updateValue('NEWSMAN_CONNECTED', 1);
				$output['msg'][] = $this->displayConfirmation($this->l('Connected. Please choose the synchronization details below.'));
				$output['ok'] = true;
				//get segments for the first list
				$list_id = $output['lists'][0]['list_id'];
				$client->query('segment.all', $list_id);
				$output['segments'] = $client->getResponse();
				//save lists and segments
				Configuration::updateValue(
					'NEWSMAN_DATA',
					Tools::jsonEncode(array('lists' => $output['lists'], 'segments' => $output['segments']))
				);
				$output['saved'] = 'saved';
			} else
			{
				$output['msg'][] = $this->displayError(
					$this->l('Error connecting. Please check your API KEY and user ID.') . "<br>" .
					$client->getErrorMessage()
				);
			}
		}
		$this->jsonOut($output);
	}

	public function ajaxProcessSaveMapping()
	{
		$mapping = Tools::getValue('mapping');
		Configuration::updateValue('NEWSMAN_MAPPING', $mapping);
		$this->jsonOut(true);
	}

	public function SaveMapping($mapping)
	{
		$map = Tools::jsonEncode($mapping);
		Configuration::updateValue('NEWSMAN_MAPPING', $map);
	}

	private function getClient($user_id, $api_key)
	{
		require_once dirname(__FILE__) . '/lib/XMLRPC.php';
		return new XMLRPC_Client(self::API_URL . "$user_id/$api_key");
	}

	public function ajaxProcessSynchronize()
	{
		$this->doSynchronize();
		$this->jsonOut(array('msg' =>
			$this->displayConfirmation($this->l('Users uploaded and scheduled for import. It might take a few minutes until they show up in your Newsman lists.'))));
	}

	public function ajaxProcessListChanged()
	{
		$list_id = Tools::getValue('list_id');
		$client = $this->getClient(Configuration::get('NEWSMAN_USER_ID'), Configuration::get('NEWSMAN_API_KEY'));
		$client->query('segment.all', $list_id);
		$output = array();
		$output['segments'] = $client->getResponse();
		$this->jsonOut($output);
	}

	public function ajaxProcessSaveCron()
	{
		$option = Tools::getValue('option');
		if (!$option || Module::isInstalled('cronjobs') && function_exists('curl_init'))
		{
			$this->jsonOut(array('msg' => $this->displayConfirmation($this->l('Automatic synchronization option saved.'))));
			Configuration::updateValue('NEWSMAN_CRON', $option);
			if ($option)
			{
				$this->registerHook('actionCronJob');
			} else
			{
				$this->unregisterHook('actionCronJob');
			}
		} else
		{
			$this->unregisterHook('actionCronJob');
			Configuration::updateValue('NEWSMAN_CRON', '');
			$this->jsonOut(
				array(
					'fail' => true,
					'msg' => $this->displayError(
						$this->l(
							'To enable automatic synchronization you need to install ' .
							'and configure "Cron tasks manager" module from PrestaShop.'
						)
					)
				)
			);
		}
	}

	public function SaveCron()
	{
		$flag = false;

		$option = Tools::getValue('cron_option');
		if (!$option || Module::isInstalled('cronjobs') && function_exists('curl_init'))
		{
			//$this->jsonOut(array('msg' => $this->displayConfirmation($this->l('Automatic synchronization option saved.'))));
			Configuration::updateValue('NEWSMAN_CRON', $option);
			$flag = true;
			if ($option)
			{
				$this->registerHook('actionCronJob');
			} else
			{
				$this->unregisterHook('actionCronJob');
			}
		} else
		{
			$this->unregisterHook('actionCronJob');
			Configuration::updateValue('NEWSMAN_CRON', '');
			/*$this->jsonOut(
				array(
					'fail' => true,
					'msg' => $this->displayError(
						$this->l(
							'To enable automatic synchronization you need to install ' .
							'and configure "Cron tasks manager" module from PrestaShop.'
						)
					)
				)
			);
			*/
		}
		return $flag;
	}

	public function getCronFrequency()
	{
		$option = Configuration::get('NEWSMAN_CRON');
		return array(
			'hour' => '1',
			'day' => '-1',
			'month' => '-1',
			'day_of_week' => $option == 'd' ? '-1' : '1'
		);
	}

	public function actionCronJob()
	{
		$this->doSynchronize();
	}

	public function doSynchronize()
	{
		$mappingData = $this->getConfig('NEWSMAN_MAPPING');

		if (!Configuration::get('NEWSMAN_CONNECTED') || !$mappingData)
		{
			return 0;
		}

		$client = $this->getClient(Configuration::get('NEWSMAN_USER_ID'), Configuration::get('NEWSMAN_API_KEY'));

		$mapping = Tools::jsonDecode($mappingData, true);
		$list_id = $mapping['list'];
		$count = 0;
		//newsletter
		$value = $mapping['map_newsletter'];
		if ($value && Module::isInstalled('blocknewsletter'))
		{
			$dbq = new DbQuery();
			$q = $dbq->select('`email`')
				->from('newsletter')
				->where('`active` = 1');
			$ret = Db::getInstance()->executeS($q->build());
			$count += count($ret);
			$header = "email,prestashop_source";
			$lines = array();
			foreach ($ret as $row)
			{
				$lines[] = "{$row['email']},newsletter";
			}
			//upload from newsletter
			$segment_id = Tools::substr($mapping['map_newsletter'], 0, 4) == 'seg_' ? Tools::substr($mapping['map_newsletter'], 4) : null;
			$this->exportCSV($client, $list_id, array($segment_id), $header, $lines);
		}
		foreach ($mapping as $key => $value)
		{
			if (!$value)
			{
				continue;
			}
			if (Tools::substr($key, 0, 10) !== 'map_group_')
			{
				continue;
			}
			$id_group = (int)(Tools::substr($key, 10));
			$dbq = new DbQuery();
			$q = $dbq->select('c.email, c.firstname, c.lastname')
				->from('customer', 'c')
				->leftJoin('customer_group', 'cg', 'cg.id_customer=c.id_customer')
				->where('cg.id_group=' . $id_group);
			$ret = Db::getInstance()->executeS($q->build());
			if (count($ret))
			{
				$count += count($ret);
				$cols = array_keys($ret[0]);
				$header = join(',', $cols) . ",prestashop_source";

				$lines = array();
				foreach ($ret as $row)
				{
					$line = '';
					foreach ($cols as $col)
					{
						$line .= $row[$col] . ',';
					}
					$lines[] = "$line,group_{$id_group}";
				}
				//upload group
				$segment_id = Tools::substr($value, 0, 4) == 'seg_' ? Tools::substr($value, 4) : null;
				$this->exportCSV($client, $list_id, array($segment_id), $header, $lines);
			}
		}
		return $count;
	}

	private function exportCSV($client, $list_id, $segments, $header, $lines)
	{
		$max = 10000;
		for ($i = 0; $i < count($lines); $i += $max)
		{
			$a = array_slice($lines, $i, $max);
			array_unshift($a, $header);
			$client->query('import.csv', $list_id, $segments, join("\n", $a));
		}
	}
}
