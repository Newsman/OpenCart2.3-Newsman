<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/library/Newsman/Client.php");

//Admin Controller
class ControllerExtensionModuleNewsman extends Controller
{
	private $error = array();

	private $restCall = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}";
	private $restCallParams = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}{{params}}";

	public
	function index()
	{
		$this->document->addStyle('./view/stylesheet/newsman.css');

		$this->load->model('setting/setting');

		$setting = $this->model_setting_setting->getSetting('newsman');

		$data = array();

		$data["message"] = "";
		$data["userid"] = "";
		$data["apikey"] = "";

		$data["list"] = "<option value=''>Select List</option>";

		$data["segment"] = "<option value=''>Select Segment</option>";

		if (isset($_POST["newsmanSubmit"]))
		{
			if (empty($_POST["userid"]) || empty($_POST["apikey"]))
			{
				$data["message"] = "Please insert User Id and Api Key";
				$this->SetOutput($data);
				return;
			}

			$this->restCall = str_replace("{{userid}}", $_POST["userid"], $this->restCall);
			$this->restCall = str_replace("{{apikey}}", $_POST["apikey"], $this->restCall);
			$this->restCall = str_replace("{{method}}", "list.all.json", $this->restCall);

			$settings = $setting;
			$settings["newsmanuserid"] = $_POST["userid"];
			$settings["newsmanapikey"] = $_POST["apikey"];

			$this->model_setting_setting->editSetting('newsman', $settings);

			$_data = json_decode(file_get_contents($this->restCall), true);

			$data["list"] = "";

			foreach ($_data as $list)
			{
				$data["list"] .= "<option value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";
			}

			$data["message"] = "Credentials are valid";
		}

		if (isset($_POST["newsmanSubmitSaveList"]))
		{
			if (empty($_POST["list"]))
			{
				$data["message"] = "Please select a list";
				$this->SetOutput($data);
				return;
			}

			$settings = $setting;
			$settings["newsmanlistid"] = $_POST["list"];

			$this->model_setting_setting->editSetting('newsman', $settings);

			$data["message"] = "List is saved";
		}

		if (isset($_POST["newsmanSubmitSaveType"]))
		{
			$settings = $setting;
			$settings["newsmantype"] = $_POST["type"];

			$this->model_setting_setting->editSetting('newsman', $settings);

			$data["message"] = "Import Type is saved";
		}

		if (isset($_POST["newsmanSubmitSaveSegment"]))
		{
			if (empty($_POST["segment"]))
			{
				$data["message"] = "Please select a segment";
				$this->SetOutput($data);
				return;
			}

			$settings = $setting;
			$settings["newsmansegment"] = $_POST["segment"];

			$this->model_setting_setting->editSetting('newsman', $settings);

			$data["message"] = "Segment is saved";
		}

		//List Import
		if (isset($_POST["newsmanSubmitList"]))
		{
			$this->restCallParams = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}{{params}}";

			$this->restCallParams = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCallParams);
			$this->restCallParams = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCallParams);
			$this->restCallParams = str_replace("{{method}}", "import.csv.json", $this->restCallParams);

			$client = new Newsman_Client($setting["newsmanuserid"], $setting["newsmanapikey"]);

			$csvdata = array();

			$this->load->model('customer/customer');
			$this->load->model('sale/order');

			$csvcustomers = $this->model_customer_customer->getCustomers();

			$csvdata = $this->getOrders();

			if (empty($csvdata))
			{
				$data["message"] .= PHP_EOL . "No data present in your store";
				$this->SetOutput($data);
				return;
			}

			$segments = null;

			if (array_key_exists("newsmansegment", $setting))
			{
				if ($setting["newsmansegment"] != "1" && $setting["newsmansegment"] != null)
				{
					$segments = array($setting["newsmansegment"]);
				}
			}

			//Import

			if ($setting["newsmantype"] == "customers")
			{
				//Customers
				$batchSize = 5000;

				$customers_to_import = array();

				foreach ($csvdata as $item)
				{
					$customers_to_import[] = array(
						"email" => $item["email"],
						"firstname" => $item["firstname"]
					);

					if ((count($customers_to_import) % $batchSize) == 0)
					{
						$this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
					}
				}

				if (count($customers_to_import) > 0)
				{
					$this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
				}

				unset($customers_to_import);

				$data["message"] .= PHP_EOL . "Customer who ordered imported successfully";

			} else
			{
				//Subscribers table
				try
				{
					$batchSize = 5000;

					$customers_to_import = array();

					foreach ($csvcustomers as $item)
					{
						if ($item["newsletter"] == 0)
						{
							continue;
						}

						$customers_to_import[] = array(
							"email" => $item["email"],
							"firstname" => $item["firstname"]
						);

						if ((count($customers_to_import) % $batchSize) == 0)
						{
							$this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
						}
					}

					if (count($customers_to_import) > 0)
					{
						$this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
					}

					unset($customers_to_import);

					$data["message"] .= PHP_EOL . "Customer subscribers imported successfully";

					$csvdata = $this->getSubscribers();

					if (empty($csvdata))
					{
						$data["message"] .= PHP_EOL . "No subscribers in your store";
						$this->SetOutput($data);
						return;
					}

					$batchSize = 5000;

					$customers_to_import = array();

					foreach ($csvdata as $item)
					{
						$customers_to_import[] = array(
							"email" => $item["email"]
						);

						if ((count($customers_to_import) % $batchSize) == 0)
						{
							$this->_importDatas($customers_to_import, $setting["newsmanlistid"], $segments, $client);
						}
					}

					if (count($customers_to_import) > 0)
					{
						$this->_importDatas($customers_to_import, $setting["newsmanlistid"], $segments, $client);
					}

					unset($customers_to_import);

				} catch (Exception $ex)
				{
					$this->SetOutput($data);
				}

				$data["message"] .= PHP_EOL . "Subscribers imported successfully";

				//Subscribers table
			}
			//Import

		}
		//List Import

		$setting = $this->model_setting_setting->getSetting('newsman');

		$data["type"] = 'subscribers';

		if(!empty($setting["newsmantype"]))		
			$data["type"] = $setting["newsmantype"];	

		$this->SetOutput($data);
	}

	public function getOrders($data = array())
	{
		$sql = "SELECT o.order_id, o.email, o.firstname, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o";

		if (isset($data['filter_order_status']))
		{
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id)
			{
				$implode[] = "o.order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode)
			{
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} else
		{
			$sql .= " WHERE o.order_status_id > '0'";
		}

		if (!empty($data['filter_order_id']))
		{
			$sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer']))
		{
			$sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_date_added']))
		{
			$sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified']))
		{
			$sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_total']))
		{
			$sql .= " AND o.total = '" . (float)$data['filter_total'] . "'";
		}

		$sort_data = array(
			'o.order_id',
			'customer',
			'order_status',
			'o.date_added',
			'o.date_modified',
			'o.total'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data))
		{
			$sql .= " ORDER BY " . $data['sort'];
		} else
		{
			$sql .= " ORDER BY o.order_id";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC'))
		{
			$sql .= " DESC";
		} else
		{
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit']))
		{
			if ($data['start'] < 0)
			{
				$data['start'] = 0;
			}

			if ($data['limit'] < 1)
			{
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public
	function validate()
	{
	}

	public
	function install()
	{
	}

	public
	function uninstall()
	{
	}

	public static function safeForCsv($str)
	{
		return '"' . str_replace('"', '""', $str) . '"';
	}

	public function _importData(&$data, $list, $segments = null, $client)
	{
		$csv = '"email","firstname","source"' . PHP_EOL;

		$source = self::safeForCsv("opencart 2.3 newsman plugin");
		foreach ($data as $_dat)
		{
			$csv .= sprintf(
				"%s,%s,%s",
				self::safeForCsv($_dat["email"]),
				self::safeForCsv($_dat["firstname"]),
				$source
			);
			$csv .= PHP_EOL;
		}

		$ret = null;
		try
		{
			$ret = $client->import->csv($list, $segments, $csv);

			if ($ret == "")
			{
				throw new Exception("Import failed");
			}
		} catch (Exception $e)
		{

		}

		$data = array();
	}

	public function _importDatas(&$data, $list, $segments = null, $client)
	{
		$csv = '"email","source"' . PHP_EOL;

		$source = self::safeForCsv("opencart 2.3 newsman plugin");
		foreach ($data as $_dat)
		{
			$csv .= sprintf(
				"%s,%s",
				self::safeForCsv($_dat["email"]),
				$source
			);
			$csv .= PHP_EOL;
		}

		$ret = null;
		try
		{
			$ret = $client->import->csv($list, $segments, $csv);

			if ($ret == "")
			{
				throw new Exception("Import failed");
			}
		} catch (Exception $e)
		{

		}

		$data = array();
	}

	public function getSubscribers()
	{
		$sql = "SELECT * FROM " . DB_PREFIX . "newsletter";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function SetOutput($data)
	{
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$setting = $this->model_setting_setting->getSetting('newsman');

		if (!empty($setting["newsmanuserid"]) && !empty($setting["newsmanapikey"]))
		{
			$this->restCall = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCall);
			$this->restCall = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCall);
			$this->restCall = str_replace("{{method}}", "list.all.json", $this->restCall);

			$this->restCallParams = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}{{params}}";

			$this->restCallParams = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCallParams);
			$this->restCallParams = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCallParams);

			$_data = json_decode(file_get_contents($this->restCall), true);

			$data["list"] = "";

			$data["segment"] = "";
			$data["segment"] .= "<option value='1'>No segment</option>";

			foreach ($_data as $list)
			{
				if (!empty($setting["newsmanlistid"]) && $setting["newsmanlistid"] == $list["list_id"])
				{
					$data["list"] .= "<option selected value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";

					$this->restCallParams = str_replace("{{method}}", "segment.all.json", $this->restCallParams);
					$this->restCallParams = str_replace("{{params}}", "?list_id=" . $setting["newsmanlistid"], $this->restCallParams);
					$_data = json_decode(file_get_contents($this->restCallParams), true);

					foreach ($_data as $segment)
					{
						if (!empty($setting["newsmansegment"]) && $setting["newsmansegment"] == $segment["segment_id"])
						{
							$data["segment"] .= "<option selected value='" . $segment["segment_id"] . "'>" . $segment["segment_name"] . "</option>";
						} else
						{
							$data["segment"] .= "<option value='" . $segment["segment_id"] . "'>" . $segment["segment_name"] . "</option>";
						}
					}
				} else
				{
					$data["list"] .= "<option value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";
				}
			}

			$data["userid"] = (empty($setting["newsmanuserid"])) ? "" : $setting["newsmanuserid"];
			$data["apikey"] = (empty($setting["newsmanapikey"])) ? "" : $setting["newsmanapikey"];
		}

		$this->response->setOutput($this->load->view('extension/module/newsman', $data));
	}
}

?>