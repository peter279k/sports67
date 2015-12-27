<?php
	require_once("../interface/controller.php");
	require_once("../Model/model.php");
	class my_controller implements Controller
	{
		public function __construct()
		{
			$this->model = new my_model();
		}
		
		public function invoke($action,$data)
		{
			$response = array();
			$arr = explode("_",$action);
			$arr_index = count($arr)-1;
			switch($action)
			{
				case "check_login_student":
					$response["result"] = $this->model->check_login($arr[$arr_index]);
					break;
				case "handle_login_student":
					$response["result"] = $this->model->handle_login($arr[$arr_index]."67",$data[0]["user-acc"],$data[0]["user-pwd"],$data[0]["recaptcha"]);
					break;
				case "handle_logon_student":
					$response["result"] = $this->model->handle_logon($arr[$arr_index]);
					break;
				case "get_volunteer_item":
					$response["result"] = $this->model->get_volunteer_item();
					break;
				case "cloth_set_action":
					$response["result"] = $this->model->cloth_set_action($data);
					break;
				case "handle_signup_volunteer":
					$response["result"] = $this->model->handle_signup($data[0]["id_name"],$data[0]["signup_time"]);
					break;
				case "admin_volunteer_item":
					$response["result"] = $this->model->admin_volunteer_item();
					break;
				case "get_volunteer_list":
					$response["result"] = $this->model->get_volunteer_list($data[0]["item-list"],$data[0]["user_id"]);
					break;
				case "post_agree_volunteer":
					$response["result"] = $this->model->post_agree_volunteer($data[0]["item_id"],$data[0]["student_number"],$data[0]["user_id"]);
					break;
				case "edit_volunteer_item":
					$response["result"] = $this->model->edit_volunteer_item($data[0]["new_item_id"],$data[0]["item_id"],$data[0]["user_id"],$data[0]["student_number"]);
					break;
				case "cancel_agree_volunteer":
					$response["result"] = $this->model->cancel_agree_volunteer($data[0]["item_id"],$data[0]["student_number"],$data[0]["user_id"]);
					break;
				case "get_checkvolunteer_item":
					$response["result"] = $this->model->get_checkvolunteer_item();
					break;
				case "post_check_volunteeritem":
					$response["result"] = $this->model->post_check_item($data[0]["item_id"],$data[0]["user_id"]);
					break;
				case "post_user_id":
					$response["result"] = $this->model->post_user_id($data[0]["user_id"]);
					break;
				case "cloth_size_cal":
					$response["result"] = $this->model->cloth_size_cal($data[0]["user_id"]);
					break;
				case "volunteer_signup_stat":
					$response["result"] = $this->model->volunteer_signup_stat();
					break;
				case "post_sign_up":
					$response["result"] = $this->model->post_sign_up($data[0]["user_id"],$data[0]["item-number"],$data[0]["student_number"]);
					break;
				case "download_excel_file":
					$response["result"] = $this->model->download_excel_file($data[0]["user_id"],$data[0]["select-item"]);
					break;
				case "lightrun_date_check":
					$response["result"] = $this->model->lightrun_date_check();
					break;
				case "handle_lightrun_search":
					$response["result"] = $this->model->handle_lightrun_search($data[0]["user-id"],$data[0]["lightrun-stu-number"]);
					break;
				case "edit_lightrun_size":
					$response["result"] = $this->model->edit_lightrun_size($data[0]['student_number'],$data[0]['size']);
					break;
				case "cancel_lightrun_signup":
					$response["result"] = $this->model->cancel_lightrun_signup($data[0]['student_number']);
					break;
				case "post_light_run":
					$response["result"] = $this->model->post_light_run($data[0]["stu-number"],$data[0]["cloth-size"]);
					break;
				case "download_lightrun_excel":
					$response["result"] = $this->model->download_lightrun_excel_file($data[0]["user_id"]);
					break;
				case "register_chief_account":
					$response["result"] = $this->model->register_chief_account($data[0]["user-acc"],$data[0]["user-pwd"],$data[0]["recaptcha"]);
					break;
				case "register_chief_fbaccount":
					$response["result"] = $this->model->register_chief_fbaccount($data[0]["user-fbacc"],$data[0]["user-id"]);
					break;
				case "check_chief_login":
					$response["result"] = $this->model->check_login('chief');
					break;
				case "handle_chief_login":
					$response["result"] = $this->model->handle_chief_login($data[0]["user-acc"],$data[0]["user-pwd"],$data[0]["recaptcha"]);
					break;
				case "handle_chief_fblogin":
					$response["result"] = $this->model->handle_chief_fblogin($data[0]['user-id']);
					break;
				case "handle_chief_logon":
					$response["result"] = $this->model->handle_logon('chief');
					break;
				case "get_contest_list":
					$response["result"] = $this->model->get_contest_list();
					break;
				case "get_contest_form":
					$response["result"] = $this->model->get_contest_form($data[0]["contest-id"]);
					break;
				case "post_contest_form":
					$response["result"] = $this->model->post_contest_form($data[0]["contest_id"]);
					break;
				case "post_contest_form2":
					$response["result"] = $this->model->post_contest_form2($data[0]["contest_id"],$data[0]["people1"],$data[0]["people2"],$data[0]["people3"],$data[0]["people4"],$data[0]["people5"]);
					break;
				case "post_contest_form3":
					$response["result"] = $this->model->post_contest_form3($data[0]["contest_id"],$data[0]["people-one"]);
					break;
				case "get_chief_forms":
					$response["result"] = $this->model->get_chief_forms($data[0]["contest_id"]);
					break;
				case "post_delete_contest":
					$response["result"] = $this->model->post_delete_contest($data[0]["contest_id"],$data[0]["team"],$data[0]["student_number"]);
					break;
				case "contest_date_check":
					$response["result"] = $this->model->contest_date_check();
					break;
				case "chief_account_statistics":
					$response["result"] = $this->model->chief_account_statistics($data[0]["user_id"]);
					break;
				case "admin_contest_list":
					$response["result"] = $this->model->admin_contest_list();
					break;
				case "get_contest_file":
					$response["result"] = $this->model->get_contest_file($data[0]['user_id']);
					break;
				case "get_contest_book":
					$response["result"] = $this->model->get_contest_book($data[0]['user_id']);
					break;
				case "print_pdf_link":
					$response["result"] = $this->model->print_pdf_link($data[0]['dept'],$data[0]['grade'],$data[0]['name'],$data[0]['score']);
					break;
				default:
					$response["result"] = "route-error";
					break;
			}
			
			return json_encode($response);
		}
	}
?>