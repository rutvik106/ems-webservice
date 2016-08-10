<?php

require_once("../lib/report-functions.php");
require_once('../lib/cg.php');
require_once('../lib/bd.php');
require_once('../lib/common.php');
require_once('../lib/adminuser-functions.php');
require_once ('../lib/follow-up-functions.php');
require_once ('../lib/follow-up-type-functions.php');
require_once ('../lib/sub-category-functions.php');
require_once ('../lib/rel-attribute-functions.php');
require_once ('../lib/prefix-functions.php');
require_once ('../lib/customer-type-functions.php');
require_once ('../lib/product-unit-functions.php');
require_once ('../lib/enquiry-group-functions.php');
require_once ('../lib/lead-functions.php');
require_once("../lib/rel-enquiry-group-functions.php");
require_once("../lib/quantity-functions.php");


switch($_POST["method"]){

	case 'get_follow_up_view':

	$enquiry_form_id=$_POST['enquiry_id'];

	$enquiry=getEnquiryById($enquiry_form_id);

	$customer_id =$enquiry["customer_id"]; //$enquiryDetails['customer_id'];

	//echo json_encode($enquiry);

	$root=array( "enquiry"=>$enquiry,
							"customer"=>getCustomerById($customer_id),
							"contact"=>getCustomerContactNo($customer_id),
							"follow_up_details"=>getFollowUpDetailsByEnquiryId($enquiry_form_id));
	
	echo json_encode($root);
	
	//echo json_encode(getExtraCustomerDetailsById($customer_id));

	//echo json_encode(getCustomerById($customer_id));

	
	//echo json_encode(getFollowUpDetailsByEnquiryId($enquiry_form_id));

	
	//echo json_encode(getVisitDetailsByEnquiryId($enquiry_form_id));

	
	//echo json_encode(getCloseLeadByEnquiryId($enquiry_form_id));

	
	//echo json_encode(getRelSubCatEnquiryFromEnquiryId($enquiry_form_id));

	
	//echo json_encode(getNotesByEnquiryId($enquiry_form_id));

	
	//echo json_encode(getBookingFormByEnquiryId($enquiry_form_id));

	break;

	case 'get_add_new_enquiry_data':

	$arrayName = array('units' => listUnits(),'enquiry_type' => listCustomerTypes(),'enquiry_group' => listEnquiryGroups(),'prefix' => listPrefix());

	echo json_encode(array('response'=>$arrayName));

	break;

	case 'add_new_enquiry':

	if(isset($_SESSION['EMSadminSession']['admin_rights']) && (in_array(2,$admin_rights) || in_array(7,					$admin_rights)))
	{   

		$attribute_name_array=json_decode($_POST['attribute_name_array'],true);
		$mobile_no=json_decode($_POST['mobile_no']);
		$quantity_id=json_decode($_POST['quantity_id']);
		$product_id=json_decode($_POST['product_id']);
	    $mrp_array=json_decode($_POST['mrp']);
		$unit_id=json_decode($_POST['unit_id']);
		
		$enquiry_group_id=explode(",", $_POST['enquiry_group_id']); 

		$result=insertLead($_POST["prefix_id"], $_POST["customer_name"], $product_id, $mrp_array, $unit_id, $quantity_id,$attribute_name_array, $mobile_no, $_POST["email_id"], $_POST["discussion"], $_POST["customer_type_id"], $_POST["refrence"], $_POST["reminder_date"]. " ".$_POST["reminder_time"], $_POST['enquiry_date'], $_POST["budget"],$_POST["customer_id"], $_POST["city"], $_POST["customer_area"], $_POST["km"], $_POST["sms_status"], $enquiry_group_id);
		
		if(is_numeric($result) && is_numeric($_POST["customer_id"]))
		{
			$response=array("status"=>"1","message"=>"New Enquiry successfully added!");
			die(json_encode(array("response"=>$response)));
		}
		else if(is_numeric($result))
		{
			$response=array("status"=>"1","message"=>"New Customer successfully added!");
			die(json_encode(array("response"=>$response)));
		}
		else{
			$response=array("status"=>"0","message"=>"Invalid Input OR Duplicate Entry!");
			die(json_encode(array("response"=>$response)));

		}

		exit;
	}
	else
	{	
		$response=array("status"=>"0","message"=>"Authentication Failed! Not enough access rights!");
		die(json_encode(array("response"=>$response)));
	}

	break;

	case 'get_attributes_from_subcat_id':

	$array = array_values(getAttributesFromSubCatId($_POST['sub_cat_id']));

	echo json_encode($array);

	break;


	case 'add_new_follow_up':

	if(isset($_SESSION['EMSadminSession']['admin_rights']) && (in_array(2,$admin_rights) || in_array(7,					$admin_rights)))
	{
		$enquiry_id=$_POST["enquiry_id"];
		$enquiry_id=clean_data($enquiry_id);



		$result=insertFollowUp($enquiry_id, $_POST["followUpDiscussion"], $_POST["next_follow_up_date"]. " ".$_POST["next_follow_up_time"], $_POST["sms_status"], $_POST["follow_up_type_id"]);

		if($result=="success")
		{
			$response=array("status"=>"1","message"=>"Follow Up successfully added!");
			die(json_encode(array("response"=>$response)));
		}
		else
		{					
			$response=array("status"=>"0","message"=>"Invalid Input OR Duplicate Entry!");
			die(json_encode(array("response"=>$response)));
		}

	}
	else
	{	
		$response=array("status"=>"0","message"=>"Authentication Failed! Not enough access rights!");
		die(json_encode(array("response"=>$response)));
	}

	break;

	case 'add_customer':

	if(isset($_SESSION['EMSadminSession']['admin_rights']) && (in_array(2,$admin_rights) || in_array(7,					$admin_rights)))
	{ 
		$mobile_no=json_decode($_POST["mobile_no"]);

		$result=insertCustomer($_POST["customer_name"], $_POST["email_id"], $mobile_no, $_POST["prefix_id"]);

		if(is_numeric($result))
		{
			$response=array("status"=>"1","message"=>"Customer added successfully");
			die(json_encode(array("response"=>$response)));
		}
		else
		{
			$response=array("status"=>"0","message"=>"Failed to add New Customer");
			die(json_encode(array("response"=>$response)));
		}

	}

	break;


	case "get_follow_up":

	$admin_id=$_SESSION['EMSadminSession']['admin_id'];
	$data=viewFollowUps(getTodaysDate(),null,null,null,null,null,null,null,null,null,null,null,null,$admin_id);

				//print_r($data);

	uasort($data, "EMIPaymentDatesComparatorForEmiReports");

	$new_data = array();

	foreach($data as $d)
	{
		$new_data[] = $d;
	}	
	echo json_encode($new_data);

	break;

	case "try_login":



	$username=clean_data($_POST['username']);
	$password=clean_data($_POST['password']);

	$sql="SELECT 
	admin_id, admin_hash, admin_name, admin_password
	FROM 
	ems_admin
	WHERE 
	admin_username='$username'
	AND is_active=1";

	$result=dbQuery($sql);
	$adminArray=dbResultToArray($result);
	$result=dbQuery($sql);

	$all=getAllActiveAdmin();

	if($all==0)
	{
		$response = array("error" => "LICENCE EXPIRED! CALL 09824143009 OR 09428592016!");
		echo json_encode("response",$response);
		exit;
	}

	if(dbNumRows($result)>0)
	{



		$admin=$adminArray[0];
		$admin_id=$admin['admin_id'];
		$admin_name=$admin['admin_name'];
		$admin_hash=$admin['admin_hash'];
		$admin_pass=$admin['admin_password'];

		$Password=crypt($password,$admin_hash); 

		$resultt=strcasecmp($admin_pass,$Password); /* returns 0 if both string are equal */

		if($resultt==0)
		{



			$_SESSION['EMSadminSession']['admin_name']=$adminArray[0]['admin_name'];
			$_SESSION['EMSadminSession']['admin_id']=$adminArray[0]['admin_id'];
			$_SESSION['EMSadminSession']['admin_rights']=getAdminRightsForAdminId($admin_id);
			$_SESSION['EMSadminSession']['admin_logged_in']=true;



			$sql="UPDATE 
			ems_admin
			SET 
			last_login=NOW()
			WHERE admin_id=$admin_id";

			$result=dbQuery($sql);	

			$response= array('admin_name'=>$_SESSION['EMSadminSession']['admin_name'],
				'admin_id'=>$_SESSION['EMSadminSession']['admin_id'],
				'admin_rights'=>$_SESSION['EMSadminSession']['admin_rights'],
				'admin_logged_in'=>$_SESSION['EMSadminSession']['admin_logged_in'],
				'session_id'=>session_id());

			echo json_encode(array("user"=>$response));

			exit;
		}
		else
		{
			$response = array("error" => "Invalid Username or Password!");
			echo json_encode("response",$response);
		}
	}
	else
	{
		$response = array("error" => "Invalid Username or Password!");
		echo json_encode("response",$response);
	}	

	break;


	case get_product_dropdown_data:

	echo json_encode(listSubCategories());

	break;


	default: echo "invalid method";


}


function EMIPaymentDatesComparatorForEmiReports($a,$b){
	$aEMIDate=$a['next_follow_up_date'];
	$bEMIDate=$b['next_follow_up_date'];
	$aEMIDate_array = explode(" ", $aEMIDate);
	$bEMIDate_array = explode(" ", $bEMIDate);
	$aEMIDate = trim($aEMIDate_array[0]);
	$bEMIDate = trim($bEMIDate_array[0]);
	$aEMIDate = str_replace('/', '-', $aEMIDate);
	$aEMIDate=date('Y-m-d',strtotime($aEMIDate));
	$bEMIDate = str_replace('/', '-', $bEMIDate);
	$bEMIDate=date('Y-m-d',strtotime($bEMIDate));
	if (strtotime($aEMIDate) < strtotime($bEMIDate)) return -1;
	if (strtotime($aEMIDate) > strtotime($bEMIDate)) return 1;
	return 0;
}


function odd($k)
{
    // returns whether the input integer is odd
	return($k & 1);
}



?>