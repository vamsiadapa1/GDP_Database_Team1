<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {

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
	public function view()
	{
        $query = $this->db->query("
		SELECT `id`, `first_name`, `last_name`, `mobile`, `email`, `password`, `station`, `created_date`, `role`, `status` FROM `tbl_users` ORDER BY id DESC");
			// echo $this->db->last_query();
		if($query){
            $data["data"] = $query->result_array();
		}else{
				return false;
		}
		$this->load->view('users_view', $data);
	}

    
    public function saveData()
	{
        // print_r($_POST);
        $fname=$this->input->post('fname');
		$lname=$this->input->post('lname');
        $password = $this->secure->encrypt($this->input->post('password'));
		$mobile=$this->input->post('mobile');
		$email=$this->input->post('email');
		$designation=$this->input->post('designation');
        if($this->input->post('record_id') == ""){
            $query = $this->db->query("select * from tbl_users where email = '".$email."'");
            if($query->num_rows() > 0){
                echo "error1";
            }else{
                $data = array(
                    'first_name' => $fname, 
                    'last_name'  => $lname, 
                    'email'  => $email, 
                    'mobile'  => $mobile, 
                    'password'  => $password, 
                    'station'  => "-", 
                    'role'  => "employee", 
                    'status' => 'Active', 
                    'created_date'  => date('Y-m-d H:i:s')
                );
                // print_r($data);
                $q1 =$this->db->insert('tbl_users', $data);
                // echo $this->db->last_query();
            }

            if($q1){
                echo "success";
            }
            else{
                echo "error";
            }
        }else{
            $record_id=$this->input->post('record_id');
            $fname=$this->input->post('fname');
            $lname=$this->input->post('lname');
            $password=$this->secure->encrypt($this->input->post('password'));
            $mobile=$this->input->post('mobile');
            $email=$this->input->post('email');

            $data = array(
                'first_name' => $fname, 
                'last_name'  => $lname, 
                'email'  => $email, 
                'mobile'  => $mobile, 
                'password'  => $password, 
                'status' => 'Active'
            );
            $this->db->where('id', $record_id);
            $q1 =$this->db->update('tbl_users', $data);
            $user_id = $this->db->insert_id();
            if($q1){
                echo "success";
            }
            else{
                echo "error";
            }

        }
		
        // SELECT `id`, `first_name`, `last_name`, `mobile`, `email`, `password`, `station`, `created_date`, `role`, `status` FROM `tbl_users` WHERE 1

		
	}

    public function getUserDetails()
	{ 
        $query = $this->db->query("SELECT * FROM `tbl_users` WHERE 1=1 AND status = 'Active' AND id='".$this->input->post('user_id')."'");
        // echo $this->db->last_query();
		if($query){
            $res = $query->result_array();
            $pass =$this->secure->decrypt($res[0]['password']);
            $data[] = array(
                'id'  => $res[0]['id'], 
                'first_name' => $res[0]['first_name'], 
                'last_name'  => $res[0]['last_name'], 
                'email'  => $res[0]['email'], 
                'mobile'  => $res[0]['mobile'], 
                'password'  =>$pass
            );
            // print_r($data);
		    echo json_encode($data);
		}else{
				return false;
		}
	}

	public function deleteUser()
	{
		$user_id=$this->input->post('user_id');
		$status=$this->input->post('status');

		if($status == 'Active'){ $stat = "Inactive";}else{ $stat = "Active";}

		$data = array( 
            'status' => $stat
        );
        $this->db->where('id', $user_id);
        $q1 =$this->db->update('tbl_users', $data);
        $user_id = $this->db->insert_id();
		if($q1){
			echo "success";
		}
		else{
			echo "error";
		}
	}


	// public function index()
	// {
	// 	$this->load->view('index');
	// }

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





















