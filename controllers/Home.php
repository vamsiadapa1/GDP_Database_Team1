<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/userguide3/general/urls.html
	 */
	public function dashboard()
	{
		$this->load->view('index');
	}

	public function index()
	{
		$this->load->view('index');
	}

	
	public function search()
	{
		$this->load->view('search');
	}

	public function login()
	{
		$this->load->view('login');
	}
	
	public function forgot()
	{
		$this->load->view('forgot');
	}

	public function signup()
	{
		$this->load->view('signup');
	}

	public function getPdfdata(){

		$search = $this->input->post("search");
		$sort_by = $this->input->post("sort_by");
		$search_words = explode(" ", $search);

		if($sort_by){
			$this->db->order_by($sort_by, 'asc');
		}
		$data = $this->db->like('extracted_text',$search)->get_where("tbl_pdfs")->result();

		$fData = [];
		$swords_count = [];
		foreach($data as $dp){

			$wcount = 0; 
			$iText = '';
			$text = json_decode($dp->extracted_text);
			foreach($text as $k => $t){
				
				if(stripos($t, $search) !== false){

					$str = explode(",", $t);
					foreach($str as $sk => $s){
						if(stripos($s, $search) !== false){
							$wcount += 1;
						}
					}

				}
				$iText .= str_ireplace($search,"<span style='background-color: yellow'><b>$search</b></span>",$t);

			}

			$fData[$dp->id] = ["file_id"=>$dp->id,"file_name" => $dp->file_name, "author" => $dp->author, "year" => $dp->year, "pdf_file"=>$dp->pdf_file, "text"=>$iText, "word_count" => []];
			$swords_count[] = ["file_id"=>$dp->id, "word_key" => $search, "word_count" =>$wcount ];
		}

		// $swr[] = "<span style='background-color: yellow'><b>$search</b></span>";
		$swr = [];
		foreach($search_words as $swc){
			$swr[] = "<span style='background-color: yellow'><b>$swc</b></span>";
		}
		
		foreach($search_words as $sw){

			$data1 = $this->db->like('extracted_text',$sw)->get_where("tbl_pdfs")->result();

			foreach($data1 as $dp1){
				$wcount1 = 0;
				$iText1 = '';
				$text1 = json_decode($dp1->extracted_text);
				foreach($text1 as $k => $t1){

					if(stripos($t1, $sw) !== false){

						$str1 = explode(",", $t1);
						foreach($str1 as $sk => $s1){
							if(stripos($s1, $sw) !== false){
								$wcount1 += 1;
							}
						}

					}
					$iText1 .= str_ireplace($sw,"<span style='background-color: yellow'><b>$sw</b></span>",$t1);

				}
				
				$fData[$dp1->id] = ["file_id"=>$dp1->id,"file_name" => $dp1->file_name, "author" => $dp1->author, "year" => $dp1->year, "pdf_file"=>$dp1->pdf_file,"text"=>$iText1, "word_count" => []];
				$swords_count[] = ["file_id"=>$dp1->id, "word_key" => $sw, "word_count" =>$wcount1];
				
			}

		}

		foreach($swords_count as $sc){
			if($sc['file_id'] == $fData[$sc['file_id']]['file_id']){

				$fText = str_ireplace($search_words,$swr,$fData[$sc['file_id']]['text']);
				$fData[$sc['file_id']]['word_count'][$sc['word_key']] = $sc['word_count'];
				$fData[$sc['file_id']]['text'] = $fText;
			}
		}

		$finalData = [];
		foreach($fData as $fd){
			$finalData[] = $fd;
		}	

		echo json_encode($finalData);		
	}

	public function downloadText($id)
	{
		$data = $this->db->get_where("tbl_pdfs",["id"=>$id])->row();
		$filename = basename($data->file_name).".txt";
		header("Content-type: text/plain");
		header("Content-Disposition: attachment; filename=$filename");

		$text = json_decode($data->extracted_text);
		foreach($text as $t){
			print $t;
		}
	}
	
	public function insertUser()
	{
		$first_name = $this->input->post('first_name');
		$last_name = $this->input->post('last_name');
		$email = $this->input->post('email');
		$mobile_number = $this->input->post('mobile_number');
		$password = $this->input->post('password');
		$cpassword = $this->input->post('cpassword');
		
		if($password !== $cpassword){
			echo json_encode(["status"=>400, "message"=>"Password & Confirm Password Not Matched."]);
			exit;
		}

		$eChk = $this->db->get_where("tbl_users",["email"=>$email])->num_rows();
		if($eChk > 0){
			echo json_encode(["status"=>400, "message"=>"Email Already Registered With Us."]);
			exit;
		}

		$data = [
			"first_name" => $first_name,
			"last_name" => $last_name,
			"email" => $email,
			"mobile" => $mobile_number,
			"password" => $this->secure->encrypt($password),
			"created_date" => date("Y-m-d H:i:s")
		];

		$d = $this->db->insert("tbl_users",$data);
		$lid = $this->db->insert_id();

		if($d){

			echo json_encode(["status"=>200, "message"=>"Successfully Registered."]);
			exit;
		}else{
			echo json_encode(["status"=>400, "message"=>"Error Occured."]);
			exit;
		}

	}

	public function do_login(){
		// print_r($_POST);
	
		$email = $this->input->post("email");
		$password = $this->input->post("password");
		$role = $this->input->post("role");

		// if($role){
		// 	$this->db->where("role", "admin");
		// }else{
		// 	$this->db->where("role", "employee");
		// }
		$mchk = $this->db->get_where("tbl_users",array("email"=>$email,"status"=>"Active"))->num_rows();
		// echo $this->db->last_query();
		
		if($mchk == 1){
	
			// if($role){
			// 	$this->db->where("role", "admin");
			// }else{
			// 	$this->db->where("role", "employee");
			// }
			$pchk = $this->db->get_where("tbl_users",array("email"=>$email,"status"=>"Active"))->row();
			$cpass = $this->secure->decrypt($pchk->password);
		
			if($cpass == $password){
				$this->session->set_userdata(["user_id"=>$pchk->id,"user_name"=>$pchk->first_name." ".$pchk->last_name, "role" => $pchk->role]);
				echo json_encode(["status"=>200,"message"=>"Logged in successfully."]);
				exit;
			}else{
				echo json_encode(["status"=>400,"message"=>"Password is Wrong."]);
				exit;
			}
	
		}else{
			
			echo json_encode(["status"=>400,"message"=>"You are not registered with us. Please sign up with us."]);
			exit;
			
		}
		
	}

	public function updatePassword(){
	
		$email = $this->input->post("email");
		$password = $this->input->post("password");
		$cpassword = $this->input->post("cpassword");
		
		if($password !== $cpassword){
			echo json_encode(["status"=>400, "message"=>"Password & Confirm Password Not Matched."]);
			exit;
		}
	
		$pchk = $this->db->where("email",$email)->update("tbl_users",array("password" => $this->secure->encrypt($password)));
		
		if($pchk){
			echo json_encode(["status"=>200,"message"=>"Password Updated successfully."]);
			exit;
		}else{
			echo json_encode(["status"=>400,"message"=>"Error Occured."]);
			exit;
		}
	
	}

	public function logout(){
		$this->session->sess_destroy();
		redirect("home/login");
	}
	
}
