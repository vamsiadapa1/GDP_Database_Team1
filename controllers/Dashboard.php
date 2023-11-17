<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once(APPPATH.'libraries/vendor/autoload.php');

class Dashboard extends CI_Controller {

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

	public function __construct() {
        parent::__construct();
        if(!$this->session->userdata('user_id')){
			redirect('home/login');
		}
    } 

	public function index()
	{
		$this->load->view('index');
	}

	public function pdfs()
	{
		$this->load->view('pdf_upload');
	}
	
	public function deleteEmployee($eid)
	{

		$d = $this->db->delete("tbl_users", ["id"=>$eid]);

		if($d){
			echo json_encode(["status"=>true, "msg"=>"Successfully Deleted"]);
			exit;
		}else{
			echo json_encode(["status"=>false, "msg"=>"Error Occured"]);
			exit;
		}

	}
	
	public function upload_files()
	{
		$user_id = $this->session->userdata('user_id');
		$author = $this->input->post('author');
		$year = $this->input->post('year');
		$title = $this->input->post('title');

		$fChk = $this->db->get_where("tbl_pdfs",["file_name"=>$_FILES['file']['name'],"author"=>$author,"year"=>$year])->num_rows();

		// print_r(["file_name"=>$_FILES['file']['name'],"author"=>$author,"year"=>$year]);
		// echo $fChk;
		// exit;

		if ($fChk > 0)
		{
			$this->session->set_flashdata('error', "File Already Uploaded.");
			redirect('dashboard/pdfs');
		}

		$config['upload_path']          = 'uploads/pdfs/';
		$config['allowed_types']        = 'pdf|txt';
		$config['encrypt_name']        = true;

		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload('file'))
		{
			$this->session->set_flashdata('error', $this->upload->display_errors());
			redirect('dashboard/pdfs');
		}
		else
		{
			$fd=$this->upload->data();
			
			$oName = $fd['client_name'];
			$file = "uploads/pdfs/".$fd['file_name'];

			if($fd['file_ext'] == '.txt'){
				
				$fh = fopen($file,'r');
				$eText = '';
				while ($line = fgets($fh)) {
					$eText .= $line;
				}
				fclose($fh);

				$text = [preg_replace('/[\x00-\x1F\x80-\xFF]/', '', str_replace([':', '\\', '/', '*', '\n', '<', '>'], '', trim($eText)))];

			}else{

				$config = new \Smalot\PdfParser\Config();
				$config->setFontSpaceLimit(-60);
				$parser = new \Smalot\PdfParser\Parser([], $config);
				$pdf = $parser->parseFile(base_url().$file);
				$text1 = $pdf->getText();

				$pCount = $pdf->getPages();

				$text = [];
				for ($i=0; $i < count($pCount); $i++) { 
					$str = trim($pCount[$i]->getText());
					array_push($text, str_replace([':', '\\', '/', '*', '\n', '<', '>'], '', $str));
				}

			}

			$d = $this->db->insert("tbl_pdfs", ["created_by"=>$user_id,"pdf_file"=>$file, "extracted_text"=> json_encode($text), "author"=>$author,"year"=>$year, "file_name"=>$oName, "title" => $title]);

			if($d){
				$this->session->set_flashdata('success', "PDF Successfully Uploaded.");
				redirect('dashboard/pdfs');
			}else{
				$this->session->set_flashdata('error', "Error Occured");
				redirect('dashboard/pdfs');
			}
		}

	}

	public function deletePdf($eid)
	{

		$pData = $this->db->get_where("tbl_pdfs", ["id"=>$eid])->row();
		$del = unlink($pData->pdf_file);

		if($del){
			$d = $this->db->delete("tbl_pdfs", ["id"=>$eid]);

			if($d){
				echo json_encode(["status"=>true, "msg"=>"Successfully Deleted"]);
				exit;
			}else{
				echo json_encode(["status"=>false, "msg"=>"Error Occured"]);
				exit;
			}
		}else{
			echo json_encode(["status"=>false, "msg"=>"Error Occured"]);
			exit;
		}	
	}
	
}
