<?php
	require_once('../interface/model.php');
	require_once('db_conn.php');
	require_once('session.php');
	require_once('PHPExcel/PHPExcel.php');
	require_once('tcpdf/config/lang/eng.php');
	require_once('tcpdf/tcpdf.php');
	class my_model implements Model
	{
		//simulate database.
		public function check_login($who)
		{
			$session = new my_session();
			$result = $session->get_session($who);
			return $result;
		}
		
		/*更改密碼方法
		public function update_pwd($table_name,$pwd1,$pwd2)
		{
			
		}*/
		
		public function handle_login($table_name,$account,$pwd,$recaptcha)
		{
			$response = "";
			if($account=="" || $pwd=="" || $recaptcha=="")
			{
				$response = "post-error";
			}
			else
			{
				$link = $this->link_db();
				$sql = "SELECT pwd FROM ".$table_name." WHERE stu_number = :account";
				$rs = $link -> prepare($sql);
				$rs -> execute(array(":account"=>$account));
				$user = $rs -> fetch(PDO::FETCH_ASSOC);
				if(count($user)!=1)
				{
					$response = "login-error";
				}
				else
				{
					if($this->hash_verify($pwd,$user["pwd"]))
					{
						$sess = new my_session();
						$sess -> set_session("student",$account);
						$response = "login-success";
					}
					else
						$response = "login-error";
				}
				$link = null;
			}
			
			return $response;
		}
		
		public function handle_chief_fblogin($user_id)
		{
			$response = null;
			if($user_id=="")
				$response = "post-error";
			else
			{
				$link = $this -> link_db();
				$sql = "SELECT account FROM sport_chief67 WHERE facebook_id = :facebook_id";
				$stmt = $link -> prepare($sql);
				$stmt -> execute(array(":facebook_id"=>$user_id));
				$user = $stmt -> fetch(PDO::FETCH_ASSOC);
				if(count($user)!=1)
				{
					$response = "fb-login-fail";
				}
				else
				{
					$sess = new my_session();
					$sess -> set_session("chief",$user["account"]);
					$response = "fb-login-success";
				}
				$link = null;
			}
			
			return $response;
		}
		
		public function handle_chief_login($account,$pwd,$recaptcha)
		{
			$response = null;
			$link = null;
			if($account=="" || $pwd=="" || $recaptcha=="")
			{
				$response = "post-error";
			}
			else
			{
				$link = $this->link_db();
				if($link==null)
					$response = "cannot link db.";
				else
				{
					$link = $this->link_db();
					$sql = "SELECT pwd FROM sport_chief67 WHERE account = :account";
					$rs = $link -> prepare($sql);
					$rs -> execute(array(":account"=>$account));
					$user = $rs -> fetch(PDO::FETCH_ASSOC);
					if(count($user)!=1)
					{
						$response = "login-error";
					}
					else
					{
						if($this->hash_verify($pwd,$user["pwd"]))
						{
							$sess = new my_session();
							$sess -> set_session("chief",$account);
							$response = "login-success";
						}
						else
							$response = "login-error";
					}
					$link = null;
				} 
			}
			
			return $response;
		}
		
		public function handle_logon($who)
		{
			$sess = new my_session();
			$sess -> kill_session($who);
			return true;
		}
		
		public function get_contest_list()
		{
			$response = null;
			$sess = new my_session();
			$stu_number = $sess -> get_session("chief");
			if(!$stu_number)
				$response = "no-login";
			else
			{
				$link = null;
				$link = $this -> link_db();
				if($link==null)
				{
					$response = "cannot link db.";
				}
				else
				{
					$sql = "SELECT class FROM student67 WHERE stu_number = :stu_number";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":stu_number"=>$stu_number));
					$class = $stmt -> fetch();
					
					$contest_row = array();
					$len = 0;
					if(mb_stristr($class["class"],"體育") || mb_stristr($class["class"],"競技") || mb_stristr($class["class"],"心動"))
					{
						$sql = "SELECT * FROM contest_item67 WHERE a_b = '男子甲組' OR a_b = '女子甲組' OR a_b = '不分組'";
						$contest_row[$len]["a_b"] = "甲組";
					}
					else
					{
						$sql = "SELECT * FROM contest_item67 WHERE a_b = '男子乙組' OR a_b = '女子乙組' OR a_b = '不分組'";
						$contest_row[$len]["a_b"] = "乙組";
					}
					
					$contest_item = $link ->query($sql);
					
					while($contest_res = $contest_item -> fetch())
					{
						$contest_row[$len]["ID"] = $contest_res["ID"];
						$contest_row[$len]["item"] = $contest_res["item"];
						$contest_row[$len]["category"] = $contest_res["category"];
						$contest_row[$len]["least_number"] = $contest_res["least_number"];
						$contest_row[$len]["max_number"] = $contest_res["max_number"];
						$contest_row[$len]["male_female"] = $contest_res["a_b"];
						$len++;
					}
					$link = null;
					$response = $contest_row;
				}
			}
			
			return $response;
		}
		
		public function get_contest_form($contest_id)
		{
			$response = null;
			$sess = new my_session();
			$stu_number = $sess -> get_session("chief");
			if(!$stu_number)
				$response = "no-login";
			else
			{
				$link = null;
				$link = $this -> link_db();
				if($link==null)
					$response = "cannot link db.";
				else
				{
					$today = date("Y-m-d H:i:s");
					$sql = "SELECT COUNT(*) FROM contest_item67 WHERE deadline<:date";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":date"=>$today));
					if((int)$stmt -> fetchColumn()!=0)
					{
						$response = "is-deadline";
						return $response;
					}
					$sql = "SELECT least_number,max_number FROM contest_item67 WHERE ID = :ID";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":ID"=>$contest_id));
					$contests = $stmt -> fetch();
					$contest_row = array();
					$contest_row[0]["least_number"] = $contests["least_number"];
					$contest_row[0]["max_number"] = $contests["max_number"];
					$response = $contest_row;
					$link = null;
				}
			}
			return $response;
		}
		
		public function post_contest_form($contest_id)
		{
			$response = null;
			$sess = new my_session();
			$stu_number = $sess -> get_session("chief");
			
			if(!$stu_number)
				$response = "no-login";
			else
			{
				$link = null;
				$link = $this -> link_db();
				if($link==null)
				{
					$response = "cannot link db.";
				}
				else
				{
					$sql = "SELECT COUNT(*) FROM contest_day67 WHERE item = :item AND sport_chief = :sport_chief";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":item"=>$contest_id,":sport_chief"=>$stu_number));
					if((int)$stmt -> fetchColumn()==1)
						$response = "已經報名過了!";
					else
					{
						$sql = "INSERT INTO contest_day67(student_number,item,gender,sport_chief,team) VALUES(:student_number,:item,:gender,:sport_chief,:team)";
						$stmt = $link -> prepare($sql);
						$stmt -> execute(array(":student_number"=>"NONE",":item"=>$contest_id,":gender"=>"NONE",":sport_chief"=>$stu_number,":team"=>"participate"));
						$response = "報名成功!";
					}
					$link = null;
				}
			}
			return $response;
		}
		
		public function post_contest_form2($contest_id,$people1,$people2,$people3,$people4,$people5)
		{
			$response = null;
			$sess = new my_session();
			$stu_number = $sess -> get_session("chief");
			if(!$stu_number)
				$response = "no-login";
			else if($people1==null || $people2==null || $people3==null || $people4==null)
			{
				$response = "post-error";
			}
			else
			{
				$link = null;
				$link = $this -> link_db();
				if($link==null)
				{
					$response = "cannot link db.";
				}
				else
				{
					$sql = "SELECT COUNT(*) FROM contest_day67 WHERE item = :item AND sport_chief = :sport_chief AND ";
					$stmt2 = $link -> prepare($sql);
					$stmt2 -> execute(array(":item"=>$contest_id,":sport_chief"=>$stu_number));
					if((int)$stmt2 -> fetchColumn()==8 || (int)$stmt2 -> fetchColumn()==9 || (int)$stmt2 -> fetchColumn()==10)
						$response = "<h2>已經報名2個隊伍了!</h2>";
					else
					{
						$check_sign = true;
						if($people5=="")
							$people = array($people1,$people2,$people3,$people4,"NONE");
						else
							$people = array($people1,$people2,$people3,$people4,$people5);
						
						if($people1==$people2 || $people1==$people3 || $people1==$people4 ||
							$people1==$people5 || $people2==$people3 || $people2==$people4 ||
							$people2==$people5 || $people3==$people4 || $people3==$people5 || 
							$people4==$people5)
						{
							$response = "報名停止，輸入的學號重複!";
							$check_sign = false;
						}
						$people_len = count($people);
						for($people_count=0;$people_count<$people_len;$people_count++)
						{
							if($people[$people_count]=="NONE")
								break;
							$sql = "SELECT COUNT(*) FROM contest_day67 WHERE item = :item AND student_number = :student_number";
							$stmt = $link -> prepare($sql);
							$stmt -> execute(array(":item"=>$contest_id,":student_number"=>$people[$people_count]));
							if((int)$stmt -> fetchColumn()!=0)
							{
								$check_sign = false;
								$response = "報名停止，此學號".$people[$people_count]."已經報名過了!";
								break;
							}
						}
						
						for($people_count=0;$people_count<$people_len;$people_count++)
						{
							if($people[$people_count]=="NONE")
								break;
							$sql = "SELECT sex FROM student67 WHERE stu_number = :stu_number";
							$stmt = $link -> prepare($sql);
							$stmt -> execute(array(":stu_number"=>$people[$people_count]));
							$sex = $stmt -> fetch();
							if($sex["sex"]=="")
							{
								$response = "報名停止，此".$people[$people_count]."學號有誤!";
								$check_sign = false;
								break;
							}
							
							$stmt = $link -> prepare("SELECT a_b FROM contest_item67 WHERE ID = :ID");
							$stmt -> execute(array(":ID"=>$contest_id));
							$a_b = $stmt -> fetch();
							if((stristr($a_b["a_b"],"男") && $sex["sex"]=="女") || (stristr($a_b["a_b"],"女") && $sex["sex"]=="男"))
							{
								$response = "報名停止，此".$people[$people_count]."學號為".$sex["sex"]."!";
								$check_sign = false;
								break;
							}
						}
						
						if($check_sign)
						{
							for($people_count=0;$people_count<$people_len;$people_count++)
							{
								if($people[$people_count]=="NONE")
									break;
								$sql = "SELECT sex FROM student67 WHERE stu_number = :stu_number";
								$stmt = $link -> prepare($sql);
								$stmt -> execute(array(":stu_number"=>$people[$people_count]));
								$sex = $stmt -> fetch();
								$sql = "INSERT INTO contest_day67(student_number,item,gender,sport_chief,team) VALUES(:student_number,:item,:gender,:sport_chief,:team)";
								$stmt = $link -> prepare($sql);
								if((int)$stmt2 -> fetchColumn()<=4)
									$stmt -> execute(array(":student_number"=>$people[$people_count],":item"=>$contest_id,":gender"=>$sex["sex"],":sport_chief"=>$stu_number,":team"=>$stu_number."400M-1"));
								else
									$stmt -> execute(array(":student_number"=>$people[$people_count],":item"=>$contest_id,":gender"=>$sex["sex"],":sport_chief"=>$stu_number,":team"=>$stu_number."400M-2"));
							}
							$response = "報名成功!";
						}
					}
					$link = null;
				}
			}
			
			return $response;
		}
		
		public function post_contest_form3($contest_id,$people_one)
		{
			$response = null;
			$sess = new my_session();
			$stu_number = $sess -> get_session("chief");
			if(!$stu_number)
				$response = "no-login";
			else if(!$this -> check_limit_item($stu_number,$contest_id,$people_one))
			{
				$response = "is-limit";
			}
			else if($people_one==null)
				$response = "post-error";
			else
			{
				$link = null;
				$link = $this -> link_db();
				if($link==null)
				{
					$response = "cannot link db.";
				}
				else
				{
					$sql = "SELECT COUNT(*) FROM contest_day67 WHERE item = :item AND sport_chief = :sport_chief";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":item"=>$contest_id,":sport_chief"=>$stu_number));
					if((int)$stmt -> fetchColumn()==5)
						$response = "<h2>已經報名5個人了!</h2>";
					else
					{
						$sql = "SELECT COUNT(*) FROM contest_day67 WHERE item = :item AND student_number = :student_number";
						$stmt = $link -> prepare($sql);
						$stmt -> execute(array(":item"=>$contest_id,":student_number"=>$people_one));
						if($stmt -> fetchColumn()!=0)
							$response = "此學號已經報名了";
						else
						{
							$stmt2 = $link -> prepare("SELECT a_b FROM contest_item67 WHERE ID = :ID");
							$stmt2 -> execute(array(":ID"=>$contest_id));
							$a_b = $stmt2 -> fetch();
							$sql = "SELECT sex FROM student67 WHERE stu_number = :stu_number";
							$stmt = $link -> prepare($sql);
							$stmt -> execute(array(":stu_number"=>$people_one));
							$sex = $stmt -> fetch();
							if($sex["sex"]=="")
							{
								$response = "報名停止，此".$people_one."學號有誤!";
							}
							else if(stristr($a_b["a_b"],"男") && $sex["sex"]=="女")
							{
								$response = "報名停止，此學號".$people_one."為".$sex["sex"]."!";
							}
							else if(stristr($a_b["a_b"],"女") && $sex["sex"]=="男")
							{
								$response = "報名停止，此學號".$people_one."為".$sex["sex"]."!";
							}
							else
							{
								$sql = "INSERT INTO contest_day67(student_number,item,gender,sport_chief,team) VALUES(:student_number,:item,:gender,:sport_chief,:team)";
								$stmt = $link -> prepare($sql);
								$stmt -> execute(array(":student_number"=>$people_one,":item"=>$contest_id,":gender"=>$sex["sex"],":sport_chief"=>$stu_number,":team"=>"in-person"));
						
								$response = "報名成功!";
							}
						}
					}
					$link = null;
				}
			}
			return $response;
		}
		
		public function get_chief_forms($contest_id)
		{
			$response = null;
			$sess = new my_session();
			$stu_number = $sess -> get_session("chief");
			if(!$stu_number)
				$response = "no-login";
			else
			{
				$link = null;
				$link = $this->link_db();
				if($link==null)
					$response = "cannot link db.";
				else
				{
					$sql = "SELECT student67.class AS class,student67.name AS name,contest_day67.student_number AS student_number,contest_day67.gender AS gender,contest_day67.team AS team,contest_item67.item AS item,contest_item67.category AS category FROM student67,contest_day67,contest_item67 WHERE contest_day67.student_number=student67.stu_number AND contest_day67.sport_chief = :sport_chief AND contest_day67.item = :contest_id AND contest_item67.ID = :contest_id";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":sport_chief"=>$stu_number,":contest_id"=>$contest_id));
					$contest_row = array();
					$contest_len = 0;
					while($res = $stmt->fetch())
					{
						$contest_row[$contest_len]["class"] = $res["class"];
						$contest_row[$contest_len]["name"] = $res["name"];
						$contest_row[$contest_len]["student_number"] = $res["student_number"];
						$contest_row[$contest_len]["gender"] = $res["gender"];
						$contest_row[$contest_len]["team"] = $res["team"];
						$contest_row[$contest_len]["item"] = $res["item"];
						$contest_row[$contest_len]["category"] = $res["category"];
						$contest_len++;
					}
					$response = $contest_row;
				}
				$link = null;
			}
			
			return $response;
		}
		
		public function post_delete_contest($contest_id,$team,$student_number)
		{
			$response = null;
			$link = $this -> link_db();
			$sess = new my_session();
			$stu_number = $sess -> get_session("chief");
			if(!$stu_number)
				$response = "no-login";
			else
			{
				$link = null;
				$link = $this->link_db();
				if($link==null)
					$response = "cannot link db.";
				else
				{
					$sql = "DELETE FROM contest_day67 WHERE sport_chief = :sport_chief AND item = :item AND student_number = :student_number";
					$stmt = $link -> prepare($sql);
					if($team=="in-person" || $team=="participate")
					{
						$stmt -> execute(array(":sport_chief"=>$stu_number,":item"=>$contest_id,":student_number"=>$student_number));
					}
					else
					{
						$sql = "DELETE FROM contest_day67 WHERE sport_chief = :sport_chief AND team = :team AND item = :item";
						$stmt = $link -> prepare($sql);
						$stmt -> execute(array(":sport_chief"=>$stu_number,":team"=>$team,":item"=>$contest_id));
					}
					$link = null;
					$response = "刪除成功";
				}
			}
			return $response;
		}
		
		public function post_sign_up($user_id,$item_number,$student_number)
		{
			$response = null;
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
			}
			else
			{
				$time = new DateTime("NOW",new DateTimeZone("Asia/Taipei"));
				$today = $time -> format('Y-m-d H:i:s');
				
				$link = $this->link_db();
				$sql = "SELECT COUNT(*) FROM volunteer_list67 WHERE student_number = :student_number AND item = :item";
				$stmt = $link -> prepare($sql);
				$stmt -> execute(array(":student_number"=>$student_number,":item"=>$item_number));
				if(((int)$stmt -> fetchColumn())==0)
				{
					$sql = "INSERT INTO volunteer_list67(item,student_number,signup_time,agree) VALUES(:item,:student_number,:signup_time,'1')";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":item"=>$item_number,":student_number"=>$student_number,":signup_time"=>$today));
					$response = "報名成功!";
				}
				else
				{
					$response = "重複報名!";
				}
				$link = null;
			}
			return $response;
		}
		
		public function get_volunteer_item()
		{
			$link = $this->link_db();
			$result = $link -> query("CALL get_volunteer_item()");
			$row = array();
			$row_i = 0;
			
			$time = new DateTime("NOW",new DateTimeZone("Asia/Taipei"));
			while($res = $result->fetch())
			{
				$today = $time -> format('Y-m-d H:i:s');
				$todate = $time -> format('Y-m-d');
				$stmt = $link -> prepare("SELECT COUNT(*) FROM volunteer_item67 WHERE ID = ".$res["ID"]. " AND signup_open<=':todate'");
				$stmt -> execute(array(":todate"=>$todate));
				
				$rs = $link -> prepare("SELECT COUNT(*) FROM volunteer_item67 WHERE ID = ".$res["ID"]." AND signup_close<'$today'");
				$rs -> execute();
				
				$row[$row_i]["link_sign"] = "我要報名";
				
				if((int)$stmt->fetchColumn()==0)
					$row[$row_i]["link_sign"] = "報名未開始";
				if($todate=="2015-01-27")
					$row[$row_i]["link_sign"] = "我要報名";
				if((int)$res["isCheck"]==1)
					$row[$row_i]["link_sign"] = "已經額滿";
				$row[$row_i]["link_sign"] = "報名截止";
		
				$row[$row_i]["ID"] = $res["ID"];
				$rs = $link -> prepare("SELECT COUNT(*) FROM volunteer_list67 WHERE item = ".$row[$row_i]["ID"]);
				$rs -> execute();
				$row[$row_i]["sign_people"] = (int)$rs -> fetchColumn()."人";
				$row[$row_i]["item"] = $res["item"];
				$row[$row_i]["night_sport"] = $res["night_sport"];
				$row[$row_i]["signup_open"] = $res["signup_open"];
				$row[$row_i]["signup_close"] = $res["signup_close"];
				$row[$row_i]["limit_number"] = $res["limit_number"];
				$row[$row_i]["condition"] = $res["condition"];
				$row_i++;
			}
			
			$link = null;
			return $row;
		}
		
		public function volunteer_signup_stat()
		{
			$sess = new my_session();
			$response = "";
			
			if(!$sess -> get_session("student"))
			{
				$response = "請先登入!";
			}
			else
			{
				$stu_number = $sess -> get_session("student");
				$link = $this -> link_db();
				$sql = "SELECT COUNT(*) FROM volunteer_list67 WHERE student_number = :student_number";
				$stmt = $link -> prepare($sql);
				$stmt -> execute(array(":student_number"=>$stu_number));
				$count = (int)$stmt -> fetchColumn();
				if($count==0)
				{
					$response = "no-record";
				}
				else
				{
					$sql = "SELECT volunteer_list67.agree AS agree,volunteer_item67.item AS item,volunteer_item67.isCheck AS isCheck,cloth_size67.size AS size FROM volunteer_list67,volunteer_item67,cloth_size67 WHERE volunteer_item67.ID = volunteer_list67.item AND volunteer_list67.student_number = :student_number AND cloth_size67.student_number = :student_number";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":student_number"=>$stu_number));
					$row = array();
					$len = 0;
					while($res = $stmt -> fetch())
					{
						$row[$len]["item"] = $res["item"];
						$row[$len]["size"] = $res["size"];
						if($res["isCheck"]==0)
						{
							$row[$len]["agree"] = "處理中";
						}
						else
						{
							if($res["agree"]==0)
							{
								$row[$len]["agree"] = "不同意";
							}
							else
							{
								$row[$len]["agree"] = "同意";
							}
						}
						$len++;
					}
					$response = $row;
				}
				$link = null;
			}
			return $response;
		}
		
		public function admin_volunteer_item()
		{
			$link = $this -> link_db();
			$sql = "CALL get_volunteer_item()";
			$result = $link -> query($sql);
			$row = array();
			$len = 0;
			while($res = $result -> fetch())
			{
				$row[$len]["ID"] = $res["ID"];
				$row[$len]["item"] = $res["item"];
				$row[$len]["night_sport"] = $res["night_sport"];
				$row[$len]["isCheck"] = $res["isCheck"];
				$len++;
			}
			$link = null;
			return $row;
		}
		
		public function chief_account_statistics($user_id)
		{
			/*
				目前註冊體育股長有誰
				報名賽事有誰
			*/
			$response = null;
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
				return $response;
			}
			//目前已經註冊的體育股長
			$link = $this -> link_db();
			$objPHPEXCEL = new PHPExcel();
			$sql = "SELECT student67.stu_number AS stu_number,student67.class AS class,student67.name AS name FROM sport_chief67,student67 WHERE sport_chief67.account = student67.stu_number";
			$result = $link -> query($sql);
			$row_data_excel = array();
			$row_len = 0;
			while($res = $result -> fetch())
			{
				$row_data_excel[$row_len]["class"] = $res["class"];
				$row_data_excel[$row_len]["name"] = $res["name"];
				$row_data_excel[$row_len]["stu_number"] = $res["stu_number"];
				$row_len++;
			}
			$return_obj = $this -> excel_writer_statistics($objPHPEXCEL,$row_data_excel,0);
			$row_data_excel = array();
			//有註冊有報名賽事的體育股長
			$sql = "SELECT DISTINCT student67.stu_number AS stu_number,student67.class AS class, student67.name AS name FROM contest_day67,sport_chief67,student67 WHERE sport_chief67.account = student67.stu_number AND contest_day67.sport_chief = sport_chief67.account";
			$result = $link -> query($sql);
			$row_data_excel = array();
			$row_len = 0;
			while($res = $result -> fetch())
			{
				$row_data_excel[$row_len]["stu_number"] = $res["stu_number"];
				$row_data_excel[$row_len]["class"] = $res["class"];
				$row_data_excel[$row_len]["name"] = $res["name"];
				$row_len++;
			}
			$return_obj = $this -> excel_writer_statistics($return_obj,$row_data_excel,1);
			$response = $this -> excel_save($return_obj,'體育股長情形.xls');
			return $response;
		}
		
		private function excel_writer_statistics($objPHPEXCEL,$row_data_excel,$num)
		{
			if($num==0)
			{
				$myWorkSheet = new PHPExcel_WorkSheet($objPHPEXCEL, '目前已經註冊的體育股長');
			}
			else
			{
				$myWorkSheet = new PHPExcel_WorkSheet($objPHPEXCEL, '有註冊有報名賽事的體育股長');
			}
			
			$objPHPEXCEL -> addSheet($myWorkSheet, $num);
			$objPHPEXCEL -> getSheet($num) -> getColumnDimension('A')->setWidth(15);
			$objPHPEXCEL -> getSheet($num) -> setCellValueByColumnAndRow(0, 1, "班級");
			$objPHPEXCEL -> getSheet($num) -> setCellValueByColumnAndRow(1, 1, "學號");
			$objPHPEXCEL -> getSheet($num) -> setCellValueByColumnAndRow(2, 1, "姓名");
			$row_len = count($row_data_excel);
			for($row_count=0;$row_count<$row_len;$row_count++)
			{
				$objPHPEXCEL -> getSheet($num) -> setCellValueByColumnAndRow(0, ($row_count+2), $row_data_excel[$row_count]["class"]);
				$objPHPEXCEL -> getSheet($num) -> setCellValueByColumnAndRow(1, ($row_count+2), $row_data_excel[$row_count]["stu_number"]);
				$objPHPEXCEL -> getSheet($num) -> setCellValueByColumnAndRow(2, ($row_count+2), $row_data_excel[$row_count]["name"]);
			}
			
			return $objPHPEXCEL;
		}
		
		public function get_contest_file($user_id)
		{
			$response = null;
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
			}
			else
			{
				$link = $this -> link_db();
				
				$row_data_excel = array();
				$objPHPEXCEL = new PHPExcel();
				$item_number = 1;
				$check_item = null;
				while($item_number<=38)
				{
					if($item_number==30 || $item_number==36)
					{
						$check_item = "no-special-item";
						$sql = "SELECT number_book67.number_book AS number_book,contest_item67.ID AS ID,contest_day67.team AS team,contest_day67.sport_chief AS sport_chief,contest_item67.item AS item,contest_item67.a_b AS a_b,contest_item67.category AS category,student67.class AS class,student67.stu_number As stu_number,student67.name AS name FROM number_book67,contest_item67,contest_day67,student67 WHERE contest_day67.item = :item AND contest_item67.ID = :item AND number_book67.student_number = student67.stu_number AND student67.stu_number = contest_day67.student_number";
						$stmt = $link -> prepare($sql);	
						$stmt -> execute(array(":item"=>$item_number));
						$len = 0;
						while($res = $stmt -> fetch())
						{
							$row_data_excel[$len]['ID'] = $res['ID'];
							$row_data_excel[$len]['item'] = $res['item'];
							$row_data_excel[$len]['a_b'] = $res['a_b'];
							$row_data_excel[$len]['category'] = $res['category'];
							$row_data_excel[$len]['class'] = $res['class'];
							$row_data_excel[$len]['stu_number'] = $res['stu_number'];
							$row_data_excel[$len]['name'] = $res['name'];
							
							$row_data_excel[$len]['team'] = $res['team'];
							$row_data_excel[$len]['sport_chief'] = $res['sport_chief'];
							$row_data_excel[$len]['number_book'] = $res['number_book'];
							$len++;
						}
					}
					else if($item_number%6==0 || $item_number==37 || $item_number==38)
					{
						$check_item = "special-item";
						$sql = "SELECT contest_item67.category AS category,contest_item67.item AS item,contest_item67.a_b AS a_b,student67.class AS class,student67.stu_number As stu_number,student67.name AS name FROM contest_item67,contest_day67,student67 WHERE contest_day67.item = :item AND contest_item67.ID = :item AND student67.stu_number = contest_day67.sport_chief";
						$stmt = $link -> prepare($sql);	
						$stmt -> execute(array(":item"=>$item_number));
						$len = 0;
						while($res = $stmt -> fetch())
						{
							$row_data_excel[$len]['item'] = $res['item'];
							$row_data_excel[$len]['a_b'] = $res['a_b'];
							$row_data_excel[$len]['class'] = $res['class'];
							$row_data_excel[$len]['stu_number'] = $res['stu_number'];
							$row_data_excel[$len]['name'] = $res['name'];
							$row_data_excel[$len]['category'] = $res['category'];
							$len++;
						}
					}
					else
					{
						$check_item = "no-special-item";
						$sql = "SELECT number_book67.number_book AS number_book,contest_item67.ID AS ID,contest_day67.team AS team,contest_day67.sport_chief AS sport_chief,contest_item67.item AS item,contest_item67.a_b AS a_b,contest_item67.category AS category,student67.class AS class,student67.stu_number As stu_number,student67.name AS name FROM number_book67,contest_item67,contest_day67,student67 WHERE contest_day67.item = :item AND contest_item67.ID = :item AND number_book67.student_number = student67.stu_number AND student67.stu_number = contest_day67.student_number";
						$stmt = $link -> prepare($sql);	
						$stmt -> execute(array(":item"=>$item_number));
						$len = 0;
						while($res = $stmt -> fetch())
						{
							$row_data_excel[$len]['ID'] = $res['ID'];
							$row_data_excel[$len]['item'] = $res['item'];
							$row_data_excel[$len]['a_b'] = $res['a_b'];
							$row_data_excel[$len]['category'] = $res['category'];
							$row_data_excel[$len]['class'] = $res['class'];
							$row_data_excel[$len]['stu_number'] = $res['stu_number'];
							$row_data_excel[$len]['name'] = $res['name'];
							
							$row_data_excel[$len]['team'] = $res['team'];
							$row_data_excel[$len]['sport_chief'] = $res['sport_chief'];
							$row_data_excel[$len]['number_book'] = $res['number_book'];
							$len++;
						}
					}
					$return_obj = $this -> excel_writer_contest($objPHPEXCEL,$row_data_excel,$item_number,$check_item);
					$row_data_excel = array();
					$item_number++;
				}
				$link = null;
				$response = $this -> excel_save($return_obj,'賽事名單.xls');
				return $response;
			}
		}
		
		public function get_contest_book($user_id)
		{
			$sql = "SELECT student67.class AS class,student67.stu_number AS stu_number FROM sport_chief67,student67 WHERE student67.stu_number = sport_chief67.account";
			$sql2 = "SELECT number_book67.number_book AS number_book,contest_item67.a_b AS a_b,contest_item67.item AS item,contest_item67.category AS category, student67.stu_number AS stu_number ,student67.class AS class, student67.name AS name FROM number_book67,student67, contest_day67, contest_item67 WHERE contest_day67.sport_chief = :sport_chief AND student67.class = :class AND contest_day67.item = contest_item67.ID AND contest_day67.student_number = student67.stu_number AND student67.stu_number = number_book67.student_number";
			$response = null;
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
			}
			else
			{
				$link = $this -> link_db();
				$row_data_excel = array();
				$objPHPEXCEL = new PHPExcel();
				$result = $link -> query($sql);
				$row_count = 0;
				while($res = $result -> fetch())
				{
					$stmt = $link -> prepare($sql2);
					$stmt -> execute(array(":sport_chief"=>$res['stu_number'],":class"=>$res['class']));
					$row_data_len = 0;
					while($temp = $stmt -> fetch())
					{
						$row_data_excel[$row_data_len]['a_b'] = $temp['a_b'];
						$row_data_excel[$row_data_len]['item'] = $temp['item'];
						$row_data_excel[$row_data_len]['category'] = $temp['category'];
						$row_data_excel[$row_data_len]['class'] = $temp['class'];
						$row_data_excel[$row_data_len]['name'] = $temp['name'];
						$row_data_excel[$row_data_len]['stu_number'] = $temp['stu_number'];
						$row_data_excel[$row_data_len]['number_book'] = $temp['number_book'];
						$row_data_len += 1;
					}
					if(count($row_data_excel)==0)
						continue;
					$objPHPEXCEL = $this -> excel_writer_number($objPHPEXCEL,$row_data_excel,$row_count);
					$row_data_excel = array();
					$row_count++;
				}
				
				$response = $this -> excel_save($objPHPEXCEL,"號碼布專用.xls");
			}
			
			return $response;
		}
		
		public function download_excel_file($user_id,$select_item)
		{
			$response = null;
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
			}
			else
			{
				$item_arr = explode(",", $select_item);
				$item_len = count($item_arr);
				$link = $this -> link_db();
				$row_data_excel = array();
				$sheet_count = 0;
				$objPHPEXCEL = new PHPExcel();
				for($item_count=0;$item_count<$item_len;$item_count++)
				{
					$sql = "SELECT cloth_size67.size AS size,volunteer_item67.ID AS ID,volunteer_item67.item AS item,student67.class AS class,student67.stu_number AS stu_number,student67.name AS name,student67.sex AS sex,student67.phone AS phone,student67.email AS email,volunteer_item67.isCheck AS isCheck,volunteer_list67.agree AS agree FROM student67,volunteer_item67,volunteer_list67,cloth_size67 WHERE volunteer_list67.item = :item AND volunteer_item67.ID = :item AND student67.stu_number = volunteer_list67.student_number AND volunteer_list67.student_number = cloth_size67.student_number";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":item"=>$item_arr[$item_count]));
					$row_len = 0;
					while($res = $stmt -> fetch())
					{
						$row_data_excel[$row_len]["item"] = $res["item"];
						$row_data_excel[$row_len]["ID"] = $res["ID"];
						$row_data_excel[$row_len]["size"] = $res["size"];
						$row_data_excel[$row_len]["class"] = $res["class"];
						$row_data_excel[$row_len]["stu_number"] = $res["stu_number"];
						$row_data_excel[$row_len]["name"] = $res["name"];
						$row_data_excel[$row_len]["sex"] = $res["sex"];
						$row_data_excel[$row_len]["phone"] = $res["phone"];
						$row_data_excel[$row_len]["email"] = $res["email"];
						$row_data_excel[$row_len]["isCheck"] = $res["isCheck"];
						$row_data_excel[$row_len]["agree"] = $res["agree"];
						$row_len++;
					}
					$objPHPEXCEL = $this -> excel_writer($objPHPEXCEL,$row_data_excel,$item_count);
					$row_data_excel = array();
				}
				$link = null;
				$response = $this -> excel_save($objPHPEXCEL,'志工組別.xls');
				return $response;
			}
		}
		
		public function download_lightrun_excel_file($user_id)
		{
			$response = null;
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
			}
			else
			{
				$link = null;
				$link = $this -> link_db();
				if($link==null)
				{
					$response = "cannot link db.";
				}
				else
				{
					$sql = "SELECT * FROM light_run67";
					$result = $link -> query($sql);
					$lightrun_row = array();
					$lightrun_len = 0;
					while($res = $result -> fetch())
					{
						$sql = "SELECT class,name,sex FROM student67 WHERE stu_number = :stu_number";
						$stmt2 = $link -> prepare($sql);
						$stmt2 -> execute(array(":stu_number"=>$res['student_number']));
						$stu_info = $stmt2 -> fetch();
						
						$lightrun_row[$lightrun_len]['student_number'] = $res['student_number'];
						$lightrun_row[$lightrun_len]['ID'] = $res['ID'];
						$lightrun_row[$lightrun_len]['size'] = $res['size'];
						$lightrun_row[$lightrun_len]['class'] = $stu_info['class'];
						$lightrun_row[$lightrun_len]['name'] = $stu_info['name'];
						$lightrun_row[$lightrun_len]['sex'] = $stu_info['sex'];
						$lightrun_len++;
					}
					$link = null;
					$response_excel = $this -> lightrun_excel_writer($lightrun_row);
					return $response = $this -> excel_save($response_excel,'螢光路跑.xls');
				}
			}
			
			return $response;
		}
		
		public function get_checkvolunteer_item()
		{
			$link = $this -> link_db();
			$sql = "SELECT isCheck,limit_number,item,ID FROM volunteer_item67";
			$result = $link -> query($sql);
			$row = array();
			$len = 0;
			while($res = $result -> fetch())
			{
				$row[$len]["isCheck"] = $res["isCheck"];
				$row[$len]["limit_number"] = $res["limit_number"];
				$row[$len]["item"] = $res["item"];
				$row[$len]["ID"] = $res["ID"];
				$sql = "SELECT COUNT( * ) FROM volunteer_list67 WHERE ".$res['ID']." = item AND agree =1";
				$stmt = $link -> prepare($sql);
				$stmt -> execute();
				$row[$len]["signup_people"] = $stmt -> fetchColumn();
				$len++;
			}
			
			$link = null;
			return $row;
		}
		
		public function post_check_item($item_id,$user_id)
		{
			$response = null;
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
			}
			else
			{
				$link = $this -> link_db();
				$sql = "UPDATE volunteer_item67 SET isCheck = 1 WHERE ID = :ID";
				$stmt = $link -> prepare($sql);
				$stmt -> execute(array(":ID"=>$item_id));
				$response = "確認成功!";
				$link = null;
			}
			
			return $response;
		}
		
		public function post_user_id($user_id)
		{
			$response = null;
			$link = $this -> link_db();
			$sql = "SELECT account FROM admin67 WHERE account = :account";
			$stmt = $link -> prepare($sql);
			$stmt -> execute(array(":account"=>$user_id));
			$userID = $stmt -> fetch();
			$response = "註冊管理員已到上限!";
			
			if($userID["account"]=="")
			{
				$sql = "INSERT INTO admin67(account,pwd,isFB) VALUES(:account,'facebooklogin',1)";
				$stmt = $link -> prepare($sql);
				$stmt -> execute(array(":account"=>$user_id));
				$response = "註冊成功!";
			}
			else
			{
				$response = "已經註冊!";
			}
			
			$link = null;
			return $response;
		}
		
		public function post_agree_volunteer($item_id,$student_number,$user_id)
		{
			$response = null;
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
			}
			else
			{
				$link = $this -> link_db();
				$result = $link -> prepare("SELECT limit_number FROM volunteer_item67 WHERE ID = :ID");
				$result -> execute(array(":ID"=>$item_id));
				$row = $result -> fetch();
				
				$sql = "SELECT COUNT(*) FROM volunteer_list67 WHERE agree = 1 AND item = :item";
				$stmt = $link -> prepare($sql);
				$stmt -> execute(array(":item"=>$item_id));
				
				$sql = "SELECT item FROM volunteer_list67 WHERE student_number = :student_number AND item != :item";
				$stmt2 = $link -> prepare($sql);
				$stmt2 -> execute(array(":student_number"=>$student_number,":item"=>$item_id));
				$stmt2_row = array();
				$len = 0;
				while($tmp = $stmt2 -> fetch())
				{
					$stmt2_row[$len]["item"] = $tmp["item"];
					$len++;
				}
				
				if((int)$stmt -> fetchColumn()==(int)$row["limit_number"])
				{
					$response = "is-limit-number";
				}
				else
				{
					$sql = "UPDATE volunteer_list67 SET agree = 1 WHERE student_number = :student_number AND item = :item";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":student_number"=>$student_number,":item"=>$item_id));
					if(count($stmt2_row)>1)
						$response = $stmt2_row;
					else
						$response = "agree-success";
				}
				$link = null;
			}
			return $response;
		}
		
		public function cancel_agree_volunteer($item_id,$student_number,$user_id)
		{
			$response = null;
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
			}
			else
			{
				$link = $this -> link_db();
				$sql = "UPDATE volunteer_list67 SET agree = 0 WHERE student_number = :student_number AND item = :item";
				$stmt = $link -> prepare($sql);
				$stmt -> execute(array(":student_number"=>$student_number,":item"=>$item_id));
				$response = "update-agree-success";
			}
			return $response;
		}
		
		public function edit_volunteer_item($new_item_id,$item_id,$user_id,$student_number)
		{
			$response = null;
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
			}
			else
			{
				$link = $this->link_db();
				$sql = "SELECT MAX( ID ) AS ID FROM volunteer_item67";
				$stmt = $link -> prepare($sql);
				$stmt -> execute();
				$max_id = $stmt->fetch(PDO::FETCH_ASSOC);
				if((int)$new_item_id<=0 || (int)$new_item_id>(int)$max_id["ID"])
				{
					$response = "item-error";
				}
				else
				{
					$sql = "UPDATE volunteer_list67 SET item = :item WHERE student_number = :student_number AND item = :old_item";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":item"=>$new_item_id,":student_number"=>$student_number,":old_item"=>$item_id));
					$response = "update-item-success";
				}
				$link = null;
			}
			
			return $response;
		}
		
		public function get_volunteer_list($item_name,$user_id)
		{
			$response = null;
			$link = $this->link_db();
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
			}
			else
			{
				$sql = "SELECT volunteer_list67.agree AS agree,student67.sex AS sex,student67.name AS name,student67.class AS class,volunteer_list67.student_number AS student_number,volunteer_list67.signup_time AS signup_time,cloth_size67.size AS size FROM student67,volunteer_list67,cloth_size67 WHERE volunteer_list67.student_number = student67.stu_number AND volunteer_list67.item = :item AND student67.stu_number=cloth_size67.student_number";
				$stmt = $link -> prepare($sql);
				$stmt -> execute(array(":item"=>$item_name));
				$row = array();
				$len = 0;
				while($res = $stmt->fetch())
				{
					$row[$len]["student_number"] = $res["student_number"];
					$row[$len]["signup_time"] = $res["signup_time"];
					$row[$len]["class"] = $res["class"];
					$row[$len]["name"] = $res["name"];
					$row[$len]["sex"] = $res["sex"];
					$row[$len]["agree"] = $res["agree"];
					$row[$len]["size"] = $res["size"];
					$len++;
				}
				$len_row = count($row);
				for($len=0;$len<$len_row;$len++)
				{
					if($len<$len_row)
					{
						if($row[$len]["student_number"]==$row[$len+1]["student_number"])
						{
							$sign_time = $row[$len]["signup_time"];
							$stu_number = $row[$len]["student_number"];
							$link -> query("DELETE FROM volunteer_list67 WHERE student_number = '$stu_number' AND signup_time = '$sign_time'");
						}
					}
				}
				$response = $row;
			}
			
			$link = null;
			return $response;
		}
		
		public function cloth_set_action($cloth_size)
		{
			$sess = new my_session();
			$response = "";
			
			if(!$sess -> get_session("student"))
			{
				$response = "請先登入!";
			}
			else if($cloth_size=="")
			{
				$response = "cloth-size-error";
			}
			else
			{
				$link = $this->link_db();
				$sql = "SELECT size FROM  cloth_size67 WHERE student_number = :stu_number";
				$stmt = $link -> prepare($sql);
				$stmt -> execute(array(":stu_number"=>$sess->get_session("student")));
				$stu_size = $stmt -> fetch();
				if($stu_size["size"]!="")
				{
					$response = "T-Shirt的size您已經設定成".$stu_size["size"]."了!";
				}
				else
				{
					$sql = "INSERT INTO cloth_size67(student_number,size) VALUES(:student_number,:cloth_size)";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":student_number"=>$sess->get_session("student"), ":cloth_size"=>$cloth_size));
					$response = "設定T-Shirt的size成功!";
				}
				
				$link = null;
			}
			
			return $response;
		}
		
		public function handle_signup($signId,$signup_open)
		{
			$response = "";
			if($signup_open==null)
			{
				$response = "post-error";
			}
			else
			{
				$sess = new my_session();
				$link = $this->link_db();
				$time = new DateTime("NOW",new DateTimeZone("Asia/Taipei"));
				$today = $time -> format('Y-m-d');
				
				$result = $link -> query("SELECT night_sport FROM volunteer_item67 WHERE ID = ".$signId);
				$night_sport = $result -> fetch();
				
				$result = $link -> prepare("SELECT COUNT(*) FROM volunteer_item67 WHERE ID = :ID AND isCheck = 1");
				$result -> execute(array(":ID"=>$signId));
				$is_check = $result -> fetchColumn();
				
				if(!$sess->get_session("student"))
				{
					$response = "登入後才可報名!";
				}
				else if((int)$is_check!=0)
				{
					$response = $is_check["item"]."已經額滿!";
				}
				else
				{
					$stmt = $link -> prepare("SELECT COUNT(*) FROM volunteer_list67 WHERE student_number = :student_number AND item = :item");
					$stmt -> execute(array(":student_number"=>$sess->get_session("student"),":item"=>$signId));
					$res = (int)$stmt -> fetchColumn();
					$is_post_again = true;
					if($res==1)
						$is_post_again = false;
					
					$today = $time -> format('Y-m-d H:i:s');
					$rs = $link -> prepare("SELECT COUNT(*) FROM volunteer_item67 WHERE ID = :ID AND signup_close<':signup_close'");
					$rs -> execute(array(":ID"=>$signId,":signup_close"=>$today));
					
					$cloth_set = $link -> prepare("SELECT COUNT(*) FROM cloth_size67 WHERE student_number = :student_number");
					$cloth_set -> execute(array(":student_number"=>$sess->get_session("student")));
					
					if((int)$rs->fetchColumn()!=0)
					{
						$response = "報名已經截止，下次請早!";
					}
					else if((int)$cloth_set->fetchColumn()==0)
					{
						$response = "請先將運動會T-shirt尺寸設定好!";
					}
					else if(!$is_post_again)
					{
						$response = "不可重複報名同一個志工項目!";
					}
					else
					{
						$sql = "INSERT INTO volunteer_list67(item,student_number,signup_time) VALUES(:item,:student_number,:signup_time)";
						$stmt = $link -> prepare($sql);
						$stmt -> execute(array(":item"=>$signId,":student_number"=>$sess->get_session("student"),":signup_time"=>$today));
						$link = null;
						$response = "報名成功!";
					}
				}
			}
			
			return $response;
		}
		
		public function post_light_run($stu_number,$cloth_size)
		{
			$sess = new my_session();
			$response = null;
			if(!$sess->get_session("student"))
			{
				$response = "login-error";
			}
			else
			{
				$link = null;
				$link = $this -> link_db();
				if($link!=null)
				{
					$sql = "SELECT COUNT(*) FROM light_run67 WHERE student_number = :student_number";
					$check_limit = "SELECT COUNT(*) FROM light_run67";
					$stmt2 = $link -> prepare($check_limit);
					$stmt2 -> execute();
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":student_number"=>$stu_number));
					if((int)$stmt -> fetchColumn()==1)
						$response = "has-signed-up";
					else if((int)$stmt2 -> fetchColumn()==350)
						$response = "has-limit-number";
					else
					{
						$sql = "INSERT INTO light_run67(student_number,size,signup_time) VALUES(:student_number,:size,:signup_time)";
						$time = new DateTime("NOW",new DateTimeZone("Asia/Taipei"));
						$today = $time -> format('Y-m-d H:i:s');
						$stmt = $link -> prepare($sql);
						$stmt -> execute(array(":student_number"=>$stu_number,":size"=>$cloth_size,":signup_time"=>$today));
						$stmt = $link -> prepare("SELECT ID FROM light_run67 WHERE student_number = :student_number");
						$stmt -> execute(array(":student_number"=>$stu_number));
						$result = $stmt -> fetch();
						$response = "<h3>報名成功</h3>";
					}
					$link = null;
				}
				else
				{
					$response = "cannot link db.";
				}
			}
			return $response;
		}
		
		public function lightrun_date_check()
		{
			date_default_timezone_set("Asia/Taipei");
			$response = null;
			$today = strtotime(date("Y-m-d"));
			$open_date = strtotime("2015-02-24");
			$dead_line = strtotime("2015-03-06");
			if(($today-$open_date)<0)
				$response["check_date"] = "報名即將在2015-02-24開始!";
			else if((($today-$dead_line)/24/60/60)>=1)
				$response['check_date'] = "報名結束!";
			else
			{
				$link = null;
				$link = $this -> link_db();
				if($link!=null)
				{
					$sql = "SELECT COUNT(*) FROM light_run67";
					$stmt = $link -> prepare($sql);
					$stmt -> execute();
					$sign_id = (int)$stmt -> fetchColumn();
					if($sign_id==350)
						$response["signup-id"] = "報名已經到上限350人!";
					else
						$response["signup-id"] = $sign_id + 1;
				}
				else
				{
					$response["signup-id"] = "cannot link db.";
				}
			}
			return $response;
		}
		
		public function handle_lightrun_search($user_id,$stu_number)
		{
			$response = null;
			$link = null;
			if(empty($user_id) || empty($stu_number))
			{
				$response = "post-error";
			}
			else
		 	{
				$link = $this -> link_db();
				if($link==null)
				{
					$response = "cannot link db.";
				}
				else
				{
					$sql = "SELECT * FROM light_run67 WHERE student_number = :student_number";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":student_number"=>$stu_number));
					$light_row = array();
					$light_len = 0;
					while($res = $stmt -> fetch())
					{
						$light_row[$light_len]['student_number'] = $res['student_number'];
						$light_row[$light_len]['size'] = $res['size'];
						$light_row[$light_len]['signup_time'] = $res['signup_time'];
						$light_len++;
					}
					$link = null;
					$response = $light_row;
				}
			}
			return $response;
		}
		
		public function edit_lightrun_size($student_number,$cloth_size)
		{
			$response = null;
			$link = null;
			if(empty($student_number) || empty($cloth_size))
			{
				$response = "post-error";
			}
			else
			{
				$link = $this -> link_db();
				if($link==null)
				{
					$response = "cannot link db.";
				}
				else
				{
					$cloth_size_arr = array("3XS","2XS","XS","S","M","L","XL","2L","3L");
					if(in_array($cloth_size,$cloth_size_arr))
					{
						$sql = "UPDATE light_run67 SET size = :size WHERE student_number = :student_number";
						$stmt = $link -> prepare($sql);
						$stmt -> execute(array(":size"=>trim($cloth_size),":student_number"=>$student_number));
						$response = "edit-size-success";
					}
					else
					{
						$response = "no-this-size";
					}
					$link = null;
				}
			}
			return $response;
		}
		
		public function cancel_lightrun_signup($student_number)
		{
			$response = null;
			$link = null;
			if(empty($student_number))
			{
				$response = "post-error";
			}
			else
			{
				$link = $this -> link_db();
				if($link==null)
				{
					$response = "cannot link db.";
				}
				else
				{
					$sql = "DELETE FROM light_run67 WHERE student_number = :student_number";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":student_number"=>$student_number));
					$response = "cancel-signup-success";
					$link = null;
				}
			}
			
			return $response;
		}
		
		public function contest_date_check()
		{
			date_default_timezone_set("Asia/Taipei");
			$response = null;
			$today = strtotime(date("Y-m-d"));
			$open_date = strtotime("2015-02-24");
			$dead_line = strtotime("2015-03-17");
			if(($today-$open_date)<0)
				$response = "報名即將在2015-02-24開始!";
			else if((($today-$dead_line)/24/60/60)>=1)
				$response = "報名結束!";
			else
				$response = "OK";
			return $response;
		}
		
		public function register_chief_account($user_acc,$user_pwd,$recaptcha)
		{
			if(strtotime(date("Y-m-d"))>strtotime("2015-04-17"))
			{
				$response = "register-dead";
			}
			else if($user_acc=="" || $user_pwd=="" || $recaptcha=="")
			{
				$response = "post-error";
			}
			else
			{
				$link = null;
				$link = $this -> link_db();
				if($link==null)
				{
					$response = "cannot link db.";
				}
				else
				{
					$sql = "SELECT COUNT(*) FROM sport_chief67,student67 WHERE student67.stu_number = sport_chief67.account AND student67.class=(SELECT class FROM student67 WHERE stu_number = :stu_number)";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":stu_number"=>$user_acc));
					if((int)$stmt -> fetchColumn()>=1)
					{
						$response = "register-fail";
						return $response;
					}
					
					$sql = "SELECT COUNT(*) FROM sport_chief67 WHERE account = :account";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":account"=>$user_acc));
					$sql = "SELECT COUNT(*) FROM student67 WHERE stu_number = :stu_number";
					$stmt2 = $link -> prepare($sql);
					$stmt2 -> execute(array(":stu_number"=>$user_acc));
					if((int)$stmt -> fetchColumn()==1)
						$response = "is-register";
					else if((int)$stmt2 -> fetchColumn()==0)
						$response = "no-stuNum-found";
					else
					{
						$hash_pwd = $this -> hash_pwd($user_pwd);
						$sql = "INSERT INTO sport_chief67(account,pwd,facebook_id) VALUES(:account,:pwd,:facebook_id)";
						$stmt = $link -> prepare($sql);
						$stmt -> execute(array(":account"=>$user_acc,":pwd"=>$hash_pwd,":facebook_id"=>"normal_login"));
						$response = "register-success";
					}
					$link = null;
				}
			}
			
			return $response;
		}
		
		public function register_chief_fbaccount($user_acc,$user_id)
		{
			$response = null;
			
			if(strtotime(date("Y-m-d"))>strtotime("2015-03-17"))
			{
				$response = "register-dead";
			}
			else if($user_acc=="" || $user_id=="")
			{
				$response = "post-error";
			}
			else
			{
				$link = null;
				$link = $this->link_db();
				if($link==null)
				{
					$response = "cannot link db.";
				}
				else
				{
					$sql = "SELECT COUNT(*) FROM sport_chief67,student67 WHERE student67.stu_number = sport_chief67.account AND student67.class=(SELECT class FROM student67 WHERE stu_number = :stu_number)";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":stu_number"=>$user_acc));
					if((int)$stmt -> fetchColumn()>=1)
					{
						$response = "register-fail";
						return $response;
					}
					
					$sql = "SELECT COUNT(*) FROM sport_chief67 WHERE account = :account";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":account"=>$user_acc));
					
					$sql = "SELECT COUNT(*) FROM student67 WHERE stu_number = :stu_number";
					$stmt2 = $link -> prepare($sql);
					$stmt2 -> execute(array(":stu_number"=>$user_acc));
					if((int)$stmt2 -> fetchColumn()==0)
						$response = "no-stuNum-found";
					else if((int)$stmt -> fetchColumn()==1)
						$response = "is-register";
					else
					{
						$sql = "INSERT INTO sport_chief67(account,pwd,facebook_id) VALUES(:account,:pwd,:facebook_id)";
						$stmt = $link -> prepare($sql);
						$stmt -> execute(array(":account"=>$user_acc,":pwd"=>"facebooklogin",":facebook_id"=>$user_id));
						$response = "register-success";
					}
				}
			}
			
			return $response;
		}
		
		public function cloth_size_cal($user_id)
		{
			$response = null;
			$res = $this -> check_admin_account($user_id);
			if($res==0)
			{
				$response = "account-error";
			}
			else
			{
				$link = $this -> link_db();
				$sql = "SELECT student_number,size FROM cloth_size67";
				$res = $link -> query($sql);
				$row = array();
				$cloth_count = 0;
				
				$row[$cloth_count]["3XS"] = 0;
				$row[$cloth_count]["2XS"] = 0;
				$row[$cloth_count]["XS"] = 0;
				$row[$cloth_count]["S"] = 0;
				$row[$cloth_count]["M"] = 0;
				$row[$cloth_count]["L"] = 0;
				$row[$cloth_count]["XL"] = 0;
				$row[$cloth_count]["2L"] = 0;
				$row[$cloth_count]["3L"] = 0;
				
				$row[$cloth_count+1]["3XS"] = 0;
				$row[$cloth_count+1]["2XS"] = 0;
				$row[$cloth_count+1]["XS"] = 0;
				$row[$cloth_count+1]["S"] = 0;
				$row[$cloth_count+1]["M"] = 0;
				$row[$cloth_count+1]["L"] = 0;
				$row[$cloth_count+1]["XL"] = 0;
				$row[$cloth_count+1]["2L"] = 0;
				$row[$cloth_count+1]["3L"] = 0;
				
				while($res_cloth = $res -> fetch())
				{
					switch($res_cloth["size"])
					{
						case "3XS";
							$row[0]["3XS"] += 1;
							break;
						case "2XS":
							$row[0]["2XS"] += 1;
							break;
						case "XS":
							$row[0]["XS"] += 1;
							break;
						case "S":
							$row[0]["S"] += 1;
							break;
						case "M":
							$row[0]["M"] += 1;
							break;
						case "L":
							$row[0]["L"] += 1;
							break;
						case "XL":
							$row[0]["XL"] += 1;
							break;
						case "2L":
							$row[0]["2L"] += 1;
							break;
						case "3L":
							$row[0]["3L"] += 1;
							break;
					}
					$sql = "SELECT student_number FROM volunteer_list67 WHERE student_number = :student_number";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":student_number"=>$res_cloth["student_number"]));
					$result = $stmt -> fetch();
					if($result["student_number"]!=null)
					{
						$row[$cloth_count]["student_number"] = "no-problem";
					}
					else
					{
						$row[$cloth_count]["student_number"] = $res_cloth["student_number"];
						$sql = "SELECT class,stu_number,name,sex FROM student67 WHERE stu_number = :stu_number";
						$stmt = $link -> prepare($sql);
						$stmt -> execute(array(":stu_number"=>$row[$cloth_count]["student_number"]));
						$student = $stmt -> fetch();
						$row[$cloth_count]["class"] = $student["class"];
						$row[$cloth_count]["stu_number"] = $student["stu_number"];
						$row[$cloth_count]["name"] = $student["name"];
						$row[$cloth_count]["sex"] = $student["sex"];
						$row[$cloth_count]["size"] = $res_cloth["size"];
						switch($res_cloth["size"])
						{
							case "3XS";
								$row[1]["3XS"] += 1;
								break;
							case "2XS":
								$row[1]["2XS"] += 1;
								break;
							case "XS":
								$row[1]["XS"] += 1;
								break;
							case "S":
								$row[1]["S"] += 1;
								break;
							case "M":
								$row[1]["M"] += 1;
								break;
							case "L":
								$row[1]["L"] += 1;
								break;
							case "XL":
								$row[1]["XL"] += 1;
								break;
							case "2L":
								$row[1]["2L"] += 1;
								break;
							case "3L":
								$row[1]["3L"] += 1;
								break;
						}
					}
					$cloth_count++;
				}
				$row[0]["clothes"] = $row[0]["3XS"]+$row[0]["2XS"]+$row[0]["XS"]+$row[0]["S"]+$row[0]["M"]+$row[0]["L"]+$row[0]["XL"]+$row[0]["2L"]+$row[0]["3L"];
				$row[0]["no_sign_clothes"] =  $row[1]["3XS"]+$row[1]["2XS"]+$row[1]["XS"]+$row[1]["S"]+$row[1]["M"]+$row[1]["L"]+$row[1]["XL"]+$row[1]["2L"]+$row[1]["3L"];
				$response = $row;
			}
			return $response;
		}
		
		public function print_pdf_link($dept,$grade,$name,$score)
		{
			ob_start();
			ob_get_flush();
			ob_get_clean();
			// create new PDF document
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			// remove default header/footer
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
			//set image scale factor
			$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
			$pdf->SetFont('msungstdlight','B',23);
			// add a page
			$pdf->AddPage();
			// output the HTML content

			$x = $pdf->getX();
			$y = $pdf->getY();
			$pdf->SetFont('msungstdlight','B',24);

			/*
				$dept = "體育";
				$grp = "男子組";
				$item = "100公尺";
				$order_no = "第一名";
				$score = "9.09秒";
			*/
			if(isset($grade) && $grade != "")
			{
				$pdf->writeHTMLCell($w=115, $h=0, $x='11', $y='100', $dept, $border=0, $ln=1, $fill=0, $reseth=true, $align='R', $autopadding=true);
				$pdf->writeHTMLCell($w=13, $h=0, $x='152', $y='100', $grade, $border=0, $ln=1, $fill=0, $reseth=true, $align='C', $autopadding=true);
				$pdf->SetFont('msungstdlight','B',20);
				$pdf->writeHTMLCell($w=70, $h=0, $x='94', $y='205', $score, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
			}
			else if(isset($name) && $name != "")
			{
				$pdf->writeHTMLCell($w=120, $h=0, $x='40', $y='95', $dept, $border=0, $ln=1, $fill=0, $reseth=true, $align='R', $autopadding=true);
				$pdf->writeHTMLCell($w=140, $h=0, $x='20', $y='110', $name, $border=0, $ln=1, $fill=0, $reseth=true, $align='R', $autopadding=true);
				$pdf->SetFont('msungstdlight','B',20);
				$pdf->writeHTMLCell($w=80, $h=0, $x='99', $y='201', $score, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
			}
			else if(isset($dept) && $dept != "")
			{
				$pdf->writeHTMLCell($w=120, $h=0, $x='40', $y='98', $dept, $border=0, $ln=1, $fill=0, $reseth=true, $align='R', $autopadding=true);
				$pdf->SetFont('msungstdlight','B',20);
				$pdf->writeHTMLCell($w=100, $h=0, $x='93', $y='202', $score, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
			}

			/*
				$pdf->writeHTMLCell($w=130, $h=0, $x='29', $y='91', $dept, $border=0, $ln=1, $fill=0, $reseth=true, $align='C', $autopadding=true);
		
				$pdf->writeHTMLCell($w=100, $h=0, $x='82', $y='142', $grp, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
				$pdf->writeHTMLCell($w=100, $h=0, $x='82', $y='155', $item, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
				$pdf->writeHTMLCell($w=100, $h=0, $x='82', $y='168', $order_no, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
				$pdf->writeHTMLCell($w=100, $h=0, $x='82', $y='181', $score, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
			*/
			$pdf->SetFillColor(255, 255, 200);
			$pdf->SetTextColor(0, 63, 127);

			// reset pointer to the last page
			$pdf->lastPage();
	
			$pdf -> Output('/var/www/sports/67/admin/print_cert/print_cert.pdf','F');
			
			$response = '/sports/67/admin/print_cert/print_cert.pdf';
			return $response;
		}
		
		private function check_limit_item($chief_account,$contest_id,$stu_number)
		{
			/*
				先判斷出甲組乙組,要報的賽事ID,看有無超過最多報名三項限制
				超過false 沒有則true
			*/
			$link = null;
			$link = $this -> link_db();
			$sql = "SELECT class FROM student67 WHERE stu_number = :stu_number";
			$stmt = $link -> prepare($sql);
			$stmt -> execute(array(":stu_number"=>$chief_account));
			$class = $stmt -> fetch();
			
			if(mb_stristr($class["class"],"體育") || mb_stristr($class["class"],"競技") || mb_stristr($class["class"],"心動"))
			{
				$male = array(1,2,3,4,25,26,27);
				$female = array(13,14,15,16,28,29,30);
				if(in_array($contest_id,$male))
				{
					$sql = "SELECT COUNT( item ) FROM contest_day67 WHERE (item >=1 AND item <=4 AND student_number = :student_number AND sport_chief = :sport_chief) OR ( item >=25 AND item <=27 AND student_number = :student_number AND sport_chief = :sport_chief)";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":student_number"=>$stu_number,":sport_chief"=>$chief_account));
					if((int)$stmt -> fetchColumn()>=2)
						return false;
					else
						return true;
				}
				if(in_array($contest_id,$female))
				{
					$sql = "SELECT COUNT( item ) FROM contest_day67 WHERE (item >=13 AND item <=16 AND student_number = :student_number AND sport_chief = :sport_chief) OR ( item >=28 AND item <=30 AND student_number = :student_number AND sport_chief = :sport_chief)";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":student_number"=>$stu_number,":sport_chief"=>$chief_account));
					if((int)$stmt -> fetchColumn()>=2)
						return false;
					else
						return true;
				}
			}
			else
			{
				$male = array(19,20,21,22,34,35,36);
				$female = array(7,8,9,10,31,32,33);
				if(in_array($contest_id,$male))
				{
					$sql = "SELECT COUNT( item ) FROM contest_day67 WHERE (item >=19 AND item <=22 AND student_number = :student_number AND sport_chief = :sport_chief) OR ( item >=34 AND item <=36 AND student_number = :student_number AND sport_chief = :sport_chief)";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":student_number"=>$stu_number,":sport_chief"=>$chief_account));
					if((int)$stmt -> fetchColumn()>=2)
						return false;
					else
						return true;
				}
				if(in_array($contest_id,$female))
				{
					$sql = "SELECT COUNT( item ) FROM contest_day67 WHERE (item >=7 AND item <=10 AND student_number = :student_number AND sport_chief = :sport_chief) OR ( item >=31 AND item <=33 AND student_number = :student_number AND sport_chief = :sport_chief)";
					$stmt = $link -> prepare($sql);
					$stmt -> execute(array(":student_number"=>$stu_number,":sport_chief"=>$chief_account));
					if((int)$stmt -> fetchColumn()>=2)
						return false;
					else
						return true;
				}
			}
			
		}
		
		private function check_admin_account($user_id)
		{
			$link = $this->link_db();
			$sql = "SELECT COUNT(*) FROM admin67 WHERE account = :account AND isFB = 1";
			$res = $link -> prepare($sql);
			$res -> execute(array(":account"=>$user_id));
			$link = null;
			return (int)$res -> fetchColumn();
		}
		
		private function link_db()
		{
			$link_db = null;
			try
			{
				$link_db = new PDO(host, user_name, user_pwd);
			}
			catch(PDOEcxception $e)
			{
				$link_db = null;
			}
			
			if($link_db!=null)
				$link_db -> query("SET NAMES utf8");
			return $link_db;
		}
		
		private function hash_pwd($pwd)
		{
			$salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
			$salt = "$2a$10$".$salt;
			return crypt($pwd,$salt);
		}
		
		private function hash_verify($pwd,$db_pass)
		{
			if($db_pass==crypt($pwd, $db_pass))
				return true;
			else
				return false;
		}
		
		private function excel_writer_contest($objPHPEXCEL,$row_data_excel,$sheet_count,$check_item)
		{
			$row_count = 0;
			$row_len = count($row_data_excel);
			if($check_item=="special-item")
			{
				$myWorkSheet = new PHPExcel_WorkSheet($objPHPEXCEL, $row_data_excel[$row_count]["item"].'('.$row_data_excel[$row_count]["a_b"].$row_data_excel[$row_count]["category"].')');
				$objPHPEXCEL -> addSheet($myWorkSheet, $sheet_count);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(0, 1, "康樂股長班級");
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(1, 1, "康樂股長學號");
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(2, 1, "康樂股長姓名");
				$objPHPEXCEL -> getSheet($sheet_count) -> getColumnDimension('A')->setWidth(15);
				$objPHPEXCEL -> getSheet($sheet_count) -> getColumnDimension('B')->setWidth(15);
				$objPHPEXCEL -> getSheet($sheet_count) -> getColumnDimension('C')->setWidth(15);
				$myWorkSheet -> getStyle('B2:B'.($row_len+1)) -> getAlignment() -> setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			}
			else
			{
				$myWorkSheet = new PHPExcel_WorkSheet($objPHPEXCEL, $row_data_excel[$row_count]["item"].'('.$row_data_excel[$row_count]["a_b"].$row_data_excel[$row_count]["category"].')');
				$objPHPEXCEL -> addSheet($myWorkSheet, $sheet_count);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(0, 1, "班級");
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(1, 1, "學號");
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(2, 1, "姓名");
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(3, 1, "號碼布");
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(4, 1, "報名的康樂股長學號");
				$objPHPEXCEL -> getSheet($sheet_count) -> getColumnDimension('A')->setWidth(15);
				if($row_data_excel[$row_count]["ID"]==5 || $row_data_excel[$row_count]["ID"]==11 ||
					$row_data_excel[$row_count]["ID"]==17 || $row_data_excel[$row_count]["ID"]==23)
				{
					$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(5, 1, "隊伍名稱");
					$objPHPEXCEL -> getSheet($sheet_count) -> getColumnDimension('F')->setWidth(15);
				}
				
				$objPHPEXCEL -> getSheet($sheet_count) -> getColumnDimension('E')->setWidth(25);
				$myWorkSheet -> getStyle('E2:E'.($row_len+1)) -> getAlignment() -> setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			}
			
			
			$column = 2;
			while($row_count<$row_len)
			{
				if($row_data_excel[$row_count]["ID"]==5 || $row_data_excel[$row_count]["ID"]==11 ||
					$row_data_excel[$row_count]["ID"]==17 || $row_data_excel[$row_count]["ID"]==23)
				{
					if(stristr($row_data_excel[$row_count]["team"],"-1"))
						$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(5, $column, "隊伍一");
					else
						$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(5, $column, "隊伍二");
				}
				if($category!="no-category")
				{
					$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(4, $column, $row_data_excel[$row_count]["sport_chief"]);
				}
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(0, $column, $row_data_excel[$row_count]["class"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(1, $column, $row_data_excel[$row_count]["stu_number"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(2, $column, $row_data_excel[$row_count]["name"]);
				if($check_item!="special-item")
					$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueExplicitByColumnAndRow(3, $column, $row_data_excel[$row_count]["number_book"]);
				
				$column += 1;
				$row_count++;
			}	
			
			return $objPHPEXCEL;
			
		}
		
		private function excel_writer_number($objPHPEXCEL,$row_data_excel,$sheet_count)
		{
			$row_count = 0;
			$column = 2;
			$row_len = count($row_data_excel);
			$myWorkSheet = new PHPExcel_WorkSheet($objPHPEXCEL, $row_data_excel[$row_count]["class"]);
			$objPHPEXCEL -> addSheet($myWorkSheet, $sheet_count);
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(0, 1, "班級");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(1, 1, "姓名");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(2, 1, "學號");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(3, 1, "號碼布");
			//$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(3, 1, "比賽項目");
			//$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(4, 1, "甲組/乙組");
			//$objPHPEXCEL -> getSheet($sheet_count) -> getColumnDimension('D')->setWidth(30);
			//$objPHPEXCEL -> getSheet($sheet_count) -> getColumnDimension('E')->setWidth(15);
			
			while($row_count<$row_len)
			{
				$temp = $row_data_excel[$row_count]["name"];
				if($row_count<($row_len-1))
				{
					if($temp==$row_data_excel[$row_count+1]["name"])
					{
						$row_count++;
						continue;
					}
				}
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(0, $column, $row_data_excel[$row_count]["class"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(1, $column, $row_data_excel[$row_count]["name"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(2, $column, $row_data_excel[$row_count]["stu_number"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueExplicitByColumnAndRow(3, $column, $row_data_excel[$row_count]["number_book"]);
				
				//$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(3, $column, $row_data_excel[$row_count]["item"].'('.$row_data_excel[$row_count]["category"].')');
				//$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(4, $column, $row_data_excel[$row_count]["a_b"]);
				
				$row_count++;
				$column++;
			}
			
			return $objPHPEXCEL;
		}
		
		private function excel_writer($objPHPEXCEL,$row_data_excel,$sheet_count)
		{
			$row_count = 0;
			$row_len = count($row_data_excel);
			$myWorkSheet = new PHPExcel_WorkSheet($objPHPEXCEL, $row_data_excel[$row_count]["item"].'('.$row_data_excel[$row_count]["ID"].')');
			$objPHPEXCEL -> addSheet($myWorkSheet, $sheet_count);
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(0, 1, "班級");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(1, 1, "學號");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(2, 1, "姓名");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(3, 1, "性別");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(4, 1, "衣服尺寸");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(5, 1, "電話");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(6, 1, "信箱");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(7, 1, "同不同意");
			$styleArray = array(
				'font' => array(
					'color' => array(
						'rgb' => '0066FF',
						'name' => 'Arial',
					),
				),
			);
			if($row_data_excel[$row_count]["isCheck"]==1)
			{
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(8, 1, "已最後確認");
				$styleArray['font']['color']['rgb'] = '336600';
			}
			else
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(8, 1, "未最後確認");
			$objPHPEXCEL -> getSheet($sheet_count) -> getColumnDimension('F')->setWidth(30);
			$objPHPEXCEL -> getSheet($sheet_count) -> getColumnDimension('G')->setWidth(30);
			$objPHPEXCEL -> getSheet($sheet_count) -> getColumnDimension('I')->setWidth(30);
			
			$myWorkSheet -> getStyle('I1') -> applyFromArray($styleArray);
			
			$styleArray = array(
				'font' => array(
					'color' => array(
						'rgb' => 'FF0000',
						'name' => 'Arial',
					),
				),
			);
			
			$column = 2;
			while($row_count<$row_len)
			{
				if($row_data_excel[$row_count]["agree"]==1)
					$row_data_excel[$row_count]["agree"] = "同意";
				else
				{
					$row_data_excel[$row_count]["agree"] = "不同意";
					$myWorkSheet -> getStyle('A'.$column.':'.'H'.$column) -> applyFromArray($styleArray);
				}
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(0, $column, $row_data_excel[$row_count]["class"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(1, $column, $row_data_excel[$row_count]["stu_number"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(2, $column, $row_data_excel[$row_count]["name"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(3, $column, $row_data_excel[$row_count]["sex"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(4, $column, $row_data_excel[$row_count]["size"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueExplicitByColumnAndRow(5, $column, $row_data_excel[$row_count]["phone"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(6, $column, $row_data_excel[$row_count]["email"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(7, $column, $row_data_excel[$row_count]["agree"]);
				$column += 1;
				$row_count++;
			}	
			
			return $objPHPEXCEL;
		}
		
		private function lightrun_excel_writer($row_data_excel)
		{
			$objPHPEXCEL = new PHPExcel;
			$row_count = 0;
			$sheet_count = 0;
			$row_len = count($row_data_excel);
			$myWorkSheet = new PHPExcel_WorkSheet($objPHPEXCEL, '螢光路跑名單');
			$myClothSizeSheet = new PHPExcel_WorkSheet($objPHPEXCEL, '螢光T-shirt尺寸統計');
			$objPHPEXCEL -> addSheet($myWorkSheet, $sheet_count);
			$objPHPEXCEL -> addSheet($myClothSizeSheet, ($sheet_count+1));
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(0, 1, "順序");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(1, 1, "班級");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(2, 1, "學號");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(3, 1, "姓名");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(4, 1, "性別");
			$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(5, 1, "衣服尺寸");
			$objPHPEXCEL -> getSheet($sheet_count+1) -> setCellValueByColumnAndRow(0, 1, "衣服尺寸");
			$objPHPEXCEL -> getSheet($sheet_count+1) -> setCellValueByColumnAndRow(1, 1, "總件數");
			
			$styleArray = array(
				'font' => array(
					'color' => array(
						'rgb' => 'FF0000',
						'name' => 'Arial',
					),
				),
			);
			
			$cloth_size_arr = array("3XS"=>0,"2XS"=>0,"XS"=>0,"S"=>0,"M"=>0,"L"=>0,"XL"=>0,"2L"=>0,"3L"=>0);
			$i = 0;
			foreach($cloth_size_arr as $key=>$value)
			{
				$objPHPEXCEL -> getSheet($sheet_count+1) -> setCellValueByColumnAndRow(0, ($i+2), $key);
				$i++;
			}
			
			$column = 2;
			while($row_count<$row_len)
			{
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(0, $column, ($row_count+1));
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(1, $column, $row_data_excel[$row_count]["class"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(2, $column, $row_data_excel[$row_count]["student_number"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(3, $column, $row_data_excel[$row_count]["name"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(4, $column, $row_data_excel[$row_count]["sex"]);
				$objPHPEXCEL -> getSheet($sheet_count) -> setCellValueByColumnAndRow(5, $column, $row_data_excel[$row_count]["size"]);
				
				foreach($cloth_size_arr as $key=>$value)
				{
					if($key==$row_data_excel[$row_count]["size"])
					{
						$cloth_size_arr[$key] += 1;
					}
				}
				$column += 1;
				$row_count++;
			}	
			
			$i = 0;
			foreach($cloth_size_arr as $key=>$value)
			{
				$objPHPEXCEL -> getSheet($sheet_count+1) -> setCellValueByColumnAndRow(1, ($i+2), $value);
				$i++;
			}
			
			return $objPHPEXCEL;
		}
		
		private function excel_save($objPHPEXCEL,$file_name)
		{
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPEXCEL, 'Excel5');
			$objWriter -> save("/var/www/sports/67/admin/excel/".$file_name);
			$objPHPEXCEL -> disconnectWorksheets();
			unset($objPHPEXCEL);
			return "/sports/67/admin/excel/".$file_name;
		}
	}
?>
