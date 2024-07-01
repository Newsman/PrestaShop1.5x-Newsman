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
 * @author    NewsMAN
 * @copyright Dazoot Software
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

		$this->isOauth($data);

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
		
		if($data["isOauth"])
		{
		// isOauth
		$out .= '
<div id="contentOauth">

	<!-- oauth step -->
	';
if ($data["oauthStep"] == 1) {
	$out .= '
	<form method="post" enctype="multipart/form-data">
		<input type="hidden" name="newsman_oauth" value="Y"/>
		<input type="hidden" name="step" value="1"/>
		<table class="form-table newsmanTable newsmanTblFixed newsmanOauth">
			<tr>
				<td>
					<p class="description"><b>Connect your site with NewsMAN for:</b></p>
				</td>
			</tr>
			<tr>
				<td>
					<p class="description">- Subscribers Sync</p>
				</td>
			</tr>
			<tr>
				<td>
					<p class="description">- Ecommerce Remarketing</p>
				</td>
			</tr>
			<tr>
				<td>
					<p class="description">- Create and manage forms</p>
				</td>
			</tr>
			<tr>
				<td>
					<p class="description">- Create and manage popups</p>
				</td>
			</tr>
			<tr>
				<td>
					<p class="description">- Connect your forms to automation</p>
				</td>
			</tr>
		</table>
		<div style="padding-top: 5px;">
			<a style="background: #ad0100; color: #fff;" href="' . $data["oauthUrl"] . '" class="button button-primary btn btn-primary">Login with NewsMAN</a>
		</div>
	</form>';
} elseif ($data["oauthStep"] == 2) {
	$out .= '
	<form method="post" enctype="multipart/form-data">
		<input type="hidden" name="oauthstep2" value="Y"/>
		<input type="hidden" name="step" value="1"/>
		<input type="hidden" name="creds" value="' . htmlspecialchars($data["creds"], ENT_QUOTES, "UTF-8") . '" />
		<table class="form-table newsmanTable newsmanTblFixed newsmanOauth">
			<tr>
				<td>
					<select name="newsman_list" id="">
						<option value="0">-- select list --</option>';
	foreach ($data["dataLists"] as $list) {
		$out .= '<option value="' . htmlspecialchars($list['list_id']) . '">' . htmlspecialchars($list['list_name']) . '</option>';
	}
	$out .= '					</select>
				</td>
			</tr>
		</table>
		<div style="padding-top: 5px;">
			<button type="submit" style="background: #ad0100; color: #fff;" class="button button-primary btn btn-primary">Save</button>
		</div>
	</form>';
}
$out .= '

</div>
';
// isOauth
		}
		else{
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
	}

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

	public function isOauth(&$data, $checkOnlyIsOauth = false){
		require 'Client.php';

		$redirUri = urlencode("https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		$redirUri = str_replace("amp%3B", "", $redirUri);
		$data["oauthUrl"] = "https://newsman.app/admin/oauth/authorize?response_type=code&client_id=nzmplugin&nzmplugin=Opencart&scope=api&redirect_uri=" . $redirUri;

		//oauth processing

		$error = "";
		$dataLists = array();
		$data["oauthStep"] = 1;
		$viewState = array();

		if(!empty($_GET["error"])){
			switch($error){
				case "access_denied":
					$error = "Access is denied";
					break;
				case "missing_lists":
					$error = "There are no lists in your NewsMAN account";
					break;
			}
		}else if(!empty($_GET["code"])){

			$authUrl = "https://newsman.app/admin/oauth/token";

			$code = $_GET["code"];

			$redirect = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			$body = array(
				"grant_type" => "authorization_code",
				"code" => $code,
				"client_id" => "nzmplugin",
				"redirect_uri" => $redirect
			);

			$ch = curl_init($authUrl);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

			$response = curl_exec($ch);

			if (curl_errno($ch)) {
				$error .= 'cURL error: ' . curl_error($ch);
			}

			curl_close($ch);

			if ($response !== false) {

				$response = json_decode($response);

				$data["creds"] = json_encode(array(
					"newsman_userid" => $response->user_id,
					"newsman_apikey" => $response->access_token
					)
				);

				foreach($response->lists_data as $list => $l){
					$dataLists[] = array(
						"list_id" => $l->list_id,
						"list_name" => $l->name
					);
				}	

				$data["dataLists"] = $dataLists;

				$data["oauthStep"] = 2;
			} else {
				$error .= "Error sending cURL request.";
			}  
		}

		if(!empty($_POST["oauthstep2"]) && $_POST['oauthstep2'] == 'Y')
		{
			if(empty($_POST["newsman_list"]) || $_POST["newsman_list"] == 0)
			{
				$step = 1;
			}
			else
			{
				$creds = stripslashes($_POST["creds"]);
				$creds = html_entity_decode($creds);
				$creds = json_decode($creds, true);

				$client = new Newsman_Client($creds["newsman_userid"], $creds["newsman_apikey"]);
				$ret = $client->remarketing->getSettings($_POST["newsman_list"]);

				$remarketingId = $ret["site_id"] . "-" . $ret["list_id"] . "-" . $ret["form_id"] . "-" . $ret["control_list_hash"];

				//set feed
				$url = "https://" . $_SERVER['SERVER_NAME'] . "/index.php?route=module/newsman_import&newsman=products.json&apikey=" . $creds["newsman_apikey"];		

				try{
					$ret = $client->feeds->setFeedOnList($_POST["newsman_list"], $url, $_SERVER['SERVER_NAME'], "NewsMAN");	
				}
				catch(Exception $ex)
				{			
					//the feed already exists
				}

				$settings = array();
				$settings['list_id'] = $_POST["newsman_list"];
				$settings['NEWSMAN_API_KEY'] = $creds["newsman_apikey"];
				$settings['NEWSMAN_USER_ID'] = $creds["newsman_userid"];

				Configuration::updateValue('NEWSMAN_API_KEY', $settings['NEWSMAN_API_KEY']);
				Configuration::updateValue('NEWSMAN_USER_ID', $settings['NEWSMAN_USER_ID']);
				Configuration::updateValue(
					'NEWSMAN_DATA',
					Tools::jsonEncode(array('lists' => $dataLists, 'segments' => array()))
				);
				$mapping = array(
					"list" => $settings["list_id"]
				);
				$mapping = Tools::jsonEncode($mapping);
				Configuration::updateValue('NEWSMAN_MAPPING', $mapping);

				$settings = [
					"analytics_newsmanremarketing" . '_register' => "newsmanremarketing",
					"analytics_newsmanremarketing" . '_trackingid' => $remarketingId
				];

				$settingsStatus = [
					'newsmanremarketing' . '_status' => 1
				];

				/*obsolete*/
				/*$this->model_setting_setting->editSetting("analytics_newsmanremarketing", $settings);
				$this->model_setting_setting->editSetting("newsmanremarketing", $settingsStatus);*/
			}
		}

		$_apiKey = Configuration::get('NEWSMAN_API_KEY');

		if(empty($_apiKey))
		{
			$data["isOauth"] = true;
		}
		else{
			$data["isOauth"] = false;
		}
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
		if (Module::isInstalled('blocknewsletter'))
		{
			$dbq = new DbQuery();
			$q = $dbq->select('`email`,`newsletter_date_add`')
				->from('newsletter')
				->where('`active` = 1');
			$ret = Db::getInstance()->executeS($q->build());
			$count += count($ret);
			$header = "email,newsletter_date_add,source";
			$lines = array();
			foreach ($ret as $row)
			{
				$lines[] = "{$row['email']},{$row['newsletter_date_add']},prestashop newsletter";
			}

			//upload from newsletter
			$segment_id = Tools::substr($mapping['map_newsletter'], 0, 4) == 'seg_' ? Tools::substr($mapping['map_newsletter'], 4) : null;

			if(empty($segment_id)){
				$segment_id = array();
			}

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
			$q = $dbq->select('c.email, c.firstname, c.lastname, c.id_gender')
				->from('customer', 'c')
				->leftJoin('customer_group', 'cg', 'cg.id_customer=c.id_customer')
				->where('cg.id_group=' . $id_group);
			$ret = Db::getInstance()->executeS($q->build());
			if (count($ret))
			{
				$count += count($ret);
				$cols = array_keys($ret[0]);
				 //rename id_gender
				 $cols[3] = "gender";
			
				 $header = join(',', $cols) . ",source";

				//rename gender again to be filtered
				$cols[3] = "id_gender";

				$lines = array();
				foreach ($ret as $row)
				{
					$line = '';
					foreach ($cols as $col)
					{
						if ($col == "id_gender") {
                            if ($row[$col] == "1") {
                                $row[$col] = "Barbat";
                            } else if ($row[$col] == "2") {
                                $row[$col] = "Femeie";
                            }
                        }

						$line .= $row[$col] . ',';
					}
					$lines[] = "$line prestashop group_{$id_group}";
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
			$res = $client->query('import.csv', $list_id, $segments, join("\n", $a));		
		}
	}
}
