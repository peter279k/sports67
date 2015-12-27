<?php
	interface Model
	{
		public function handle_login($table_name,$account,$pwd,$recaptcha);
		//public function update_pwd($table_name,$pwd1,$pwd2);
		public function check_login($who);
		public function get_volunteer_item();
		public function admin_volunteer_item();
		public function get_checkvolunteer_item();
		public function post_user_id($user_id);
		public function post_check_item($item_id,$user_id);
		public function handle_signup($signId,$signup_open);
		public function cloth_set_action($cloth_size);
		public function get_volunteer_list($item_name,$user_id);
		public function post_agree_volunteer($item_id,$student_number,$user_id);
		public function cancel_agree_volunteer($item_id,$student_number,$user_id);
		public function edit_volunteer_item($new_item_id,$item_id,$user_id,$student_number);
		public function cloth_size_cal($user_id);
		public function volunteer_signup_stat();
		public function post_sign_up($user_id,$item_number,$student_number);
		public function download_excel_file($user_id,$select_item);
		public function lightrun_date_check();
		public function contest_date_check();
		public function register_chief_account($user_acc,$user_pwd,$recaptcha);
		public function register_chief_fbaccount($user_acc,$user_id);
		public function handle_chief_login($account,$pwd,$recaptcha);
		public function handle_chief_fblogin($user_id);
		public function get_contest_list();
		public function get_contest_form($contest_id);
		public function post_contest_form($contest_id);
		public function post_contest_form2($contest_id,$people1,$people2,$people3,$people4,$people5);
		public function post_contest_form3($contest_id,$people_one);
		public function get_chief_forms($contest_id);
		public function post_delete_contest($contest_id,$team,$student_number);
		public function handle_lightrun_search($user_id,$lightrun_stu_number);
		public function edit_lightrun_size($student_number,$cloth_size);
		public function cancel_lightrun_signup($student_number);
		public function download_lightrun_excel_file($user_id);
		public function chief_account_statistics($user_id);
		public function get_contest_file($user_id);
		public function get_contest_book($user_id);
		public function print_pdf_link($dept,$grade,$name,$score);
	}
?>