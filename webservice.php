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
require_once("../lib/prefix-functions.php");

require_once ("../lib/customer-extra-details-functions.php");
require_once ("../lib/profession-functions.php");
require_once ("../lib/data-from-functions.php");


switch($_POST["method"]){


	case 'get_customer_details':

	$customer_id=$_POST["customer_id"];

	$customerDetails = getCustomerById($customer_id);

	$contactNumbers=getCustomerContactNo($customer_id);

	$extraCustomerDetails = getExtraCustomerDetailsById($customer_id);

	$prefix = getPrefixById($customerDetails["prefix_id"]);							  
	$customer_prefix = $prefix['prefix'];

	//$notes = getNotesByCustomerId($customer_id);

	//$memberDetails = getMembersByCustomerId($customer_id);

	$singleCustomerDetails=array("customer_name"=>$customer_prefix.$customerDetails["customer_name"],
								 "customer_email"=>$customerDetails["customer_email"],
								 "customer_contact"=>$contactNumbers,
								 "customer_id"=>$customerDetails["customer_id"]);

	$proof_details=getCustomerProofByCustomerId();


	$enquiryDetails = getEnquiryByCustomerId($customer_id);

	$customerEnquiryDetails=array();

	foreach($enquiryDetails as $enquiryDetail)
	{

		$enquiryDate = $enquiryDetail['enquiry_date'];

		$enquiry_form_id = $enquiryDetail['enquiry_form_id'];

		$subCategory = getSubCatFromEnquiryId($enquiry_form_id);

		$isBoughtVariable = $enquiryDetail['is_bought'];

		$enquiryStatus="";

		if($isBoughtVariable==0)
		{
			$enquiryStatus= "New Enquiry";
		}
		else if($isBoughtVariable==3)
		{
			$enquiryStatus= "Ongoing Enquiry";
		}
		else if($isBoughtVariable==1 || $isBoughtVariable==2)
		{
			$enquiryStatus= "Closed Enquiry";
		}

		$leadHolder = $enquiryDetail['current_lead_holder'];

		$adminDetails = getAdminUserByID($leadHolder);

		$handled_by = $adminDetails['admin_name'];

		$singleCustomerEnquiryDetails=array(
			"enquiry_date"=>$enquiryDate,
			"enquiry_for"=>$subCategory[0]["sub_cat_name"],
			"enquiry_status"=>$enquiryStatus,
			"enquiry_managed_by"=>$handled_by);

		$customerEnquiryDetails[]=$singleCustomerEnquiryDetails;

	}

	echo json_encode(array("customer_details"=>$singleCustomerDetails,
						   "customer_enquiry_details"=>$customerEnquiryDetails));

	break;


	case 'search_customer':


	$enquiry_id = $_POST['enquiry_id'];
	$mobile_number = $_POST['mobile_no'];
	$name = $_POST['name'];
	$email = $_POST['email'];

	if(validateForNull($email) || validateForNull($mobile_number) || validateForNull($name) || validateForNull($enquiry_id))
	{						
		if(validateForNull($name))
		{
			$customer_id=getCustomerIdFromCustomerName($name);
			if(checkForNumeric($customer_id))
			{
				unset($_SESSION['search']);
				echo json_encode(array("response"=>array("result"=>$customer_id)));
				exit;
			}
			else if(is_array($customer_id))
			{
				$searchedCustomer=array();
				foreach($customer_id as $file_id)
				{

					$customer=getCustomerById($file_id['customer_id']);
					$contactNos = getCustomerContactNo($file_id['customer_id']);
					$searchedCustomer[]=array("customer_id"=>$file_id['customer_id'],
						"customer"=>$customer,
						"customer_contact"=>$contactNos);
				}

				echo json_encode(array("response"=>array("result"=>$searchedCustomer)));
				exit;
			}
			else{
				unset($_SESSION['search']);
				echo json_encode(array("response"=>array("result"=>"invalid")));
				exit;

			}	
		}
		else if(validateForNull($mobile_number))
		{


			$customer_id=getCustomerIdFromContactNo($mobile_number);

			if(checkForNumeric($customer_id))
			{
				unset($_SESSION['search']);
				echo json_encode(array("response"=>array("result"=>$customer_id)));
				exit;
			}

			else{
				unset($_SESSION['search']);
				echo json_encode(array("response"=>array("result"=>"invalid contact number")));
				exit;

			}	
		}

		else if(validateForNull($enquiry_id))
		{



			$customer_id = getCustomerByUniqueEnquiryId($enquiry_id);

			if(checkForNumeric($customer_id))
			{
				unset($_SESSION['search']);
				echo json_encode(array("response"=>array("result"=>$customer_id)));
				exit;
			}

			else{
				unset($_SESSION['search']);
				echo json_encode(array("response"=>array("result"=>"invalid enquiry id")));
				exit;

			}	
		}

		else if(validateForNull($email))
		{


			$customer_id=getCustomerIdFromEmail($email);

			if(checkForNumeric($customer_id))
			{
				unset($_SESSION['search']);
				echo json_encode(array("response"=>array("result"=>$customer_id)));
				exit;
			}

			else{
				unset($_SESSION['search']);
				echo json_encode(array("response"=>array("result"=>"invalid email id")));
				exit;

			}	
		}

	}
	else
	{	
		echo json_encode(array("response"=>array("result"=>"minimum one field require")));
		exit;
	}



	break;


	case 'get_customer_names':
	
	$sql = "SELECT customer_name FROM ems_customer WHERE customer_name LIKE '%".$_REQUEST['term']."%'";
	$result=dbQuery($sql);
	$resultArray=dbResultToArray($result);
	foreach ($resultArray as $r) 
	{
		$results[] = array('label' => $r['customer_name']);
	}


	
	echo json_encode($results);

	break;


	case 'get_follow_up_view':

	$enquiry_form_id=$_POST['enquiry_id'];

	$enquiry=getEnquiryById($enquiry_form_id);

	$enquiryDetails=getEnquiryById($enquiry_form_id);

	$customer_id =$enquiry["customer_id"]; //$enquiryDetails['customer_id'];


	$customer_detail_array=getCustomerById($customer_id);

	$prefix=getPrefixById($customer_detail_array["prefix_id"]);	

	$customer_detail_array["prefix"]=$prefix["prefix"];

	$tNumber = getNoOfEnquiriesForCustomerId($customer_id);
	$sNumber = getNoOfSuccessfullEnquiriesForCustomerId($customer_id);     

	$enquiry["total_enquiry"]=$sNumber.'/'.$tNumber; 


	$group="";

	$groupNameDetailsArray = getEnquiryGroupNamesByEnquiryId($enquiry_form_id);
	foreach($groupNameDetailsArray as $groupNameArray)
	{
		$group= $group . $groupNameArray['enquiry_group_name']. ", ";  
	}  

	$enquiry["group"]=$group;

	//$prefix_only=array("prefix"=>$prefix["prefix"]);

	//array_merge($customer_detail_array,$prefix_only);

	//echo json_encode($enquiry);



	$follow_up_details=getFollowUpDetailsByEnquiryId($enquiry_form_id);

	$final_follow_up_details=array();

	foreach ($follow_up_details as $single_follow_up) {
		

		$follow_up_type_id = $single_follow_up['follow_up_type_id'];
		if($follow_up_type_id!=NULL)
		{
			$follow_up_type_details = getFollowUpTypeById($follow_up_type_id);
			$single_follow_up["follow_up_type"] =$follow_up_type_details['follow_up_type'];
		}
		else
		{
			$single_follow_up["follow_up_type"] = "-";
		}


		$adminId = $single_follow_up['created_by'];
		$adminNameArray = getAdminUserByID($adminId);
		$adminName = $adminNameArray['admin_name'];
		$single_follow_up["handled_by"] =$adminName; 

		$single_follow_up["date_added"]=date('d/m/Y H:i:s',strtotime($single_follow_up['date_added']));

		array_push($final_follow_up_details, $single_follow_up);

	}


	$subCategory = getSubCatFromEnquiryId($enquiry_form_id);
	if(is_numeric($subCategory[0][0]))
	{
		$product_details=array();
		foreach($subCategory as $subC)
		{
			$single_product_detail=array();

			$sub_cat_id=$subC['sub_cat_id'];
			$subCatNameArray = getsubCategoryById($sub_cat_id);
			$subCatName = $subCatNameArray['sub_cat_name'];

			$quantity_id = $subC['quantity_id'];
			$quantityDetails = getQuantityById($quantity_id);
			$quantity = $quantityDetails['quantity'];


			$unit_id = $subC['product_unit_id'];
			$unitDetails = getUnitById($unit_id);
			$unit_name = $unitDetails['unit_name'];

			$price = $subC['customer_price'];


			$attribute_type_names_array=getAttributeTypesForASubCatOfAnEnquiry($sub_cat_id,$enquiry_form_id);


			$single_product_detail['product_name']=$subCatName;	//product name			

			$types=array();

			foreach($attribute_type_names_array as $attribute_type_names)
			{
				//type
				$single_type=array();
				$single_type[]=$attribute_type_names['attribute_type']. " : " .$attribute_type_names['attribute_names_string'];    
				array_push($types,$single_type);              			

			}

			$single_product_detail["type"]=$types;


			$single_product_detail["quantity"]=$quantity; 

			$single_product_detail["price"]= $price. " ". $unit_name;



		}

		array_push($product_details, $single_product_detail);
		

	}



	$enquiry_details=array();


	if($enquiryDetails['customer_type_id']==NULL)
	{
		$EnquiryType =  "Not Available";
	}
	else
	{
		$customerTypeId = $enquiryDetails['customer_type_id'];
		$customerTypeDetails = getCustomerTypeById($customerTypeId);
		$EnquiryType= $customerTypeDetails['customer_type'];
	}

	$enquiry_details['enquiry_type']=$EnquiryType;


	if($enquiryDetails['customer_type_id']==3)
	{


		$refrence_details = getRefrenceForEnquiryId($enquiry_form_id);
		if($refrence_details['refrence_name'] != NULL)
		{
			$refrence_details= $refrence_details['refrence_name'];	 
		}

		$enquiry_details['refrence_name']=$refrence_details;

	}

	if($enquiryDetails['budget']==0)
	{
		$CustomerBudget= "Not Available"; 
	}
	else
	{
		$CustomerBudget= $enquiryDetails['budget']; 
	}

	$enquiry_details['customer_budget']=$CustomerBudget;


	$discussion = $enquiryDetails['enquiry_discussion'];

	if(!validateForNull($discussion))
	{
		$discussion= "No Discussion Available!"; 
	}

	$enquiry_details['discussion']=$discussion;




	if(date('d/m/Y H:i:s', strtotime($enquiryDetails['follow_up_date']))=="01/01/1970")
	{
		$fstFollowUpDate= "Reminder not set."; 
	}
	else
	{
		$fstFollowUpDate= date('d/m/Y H:i:s', strtotime($enquiryDetails['follow_up_date']));
	}

	$enquiry_details['fst_follow_up_date']=$fstFollowUpDate;

	$DateofEnquiry=date('d/m/Y H:i:s',strtotime($enquiryDetails['enquiry_date']));

	$enquiry_details['date_of_enquiry']=$DateofEnquiry;

	$adminUserID = $enquiryDetails['created_by']; 
	$adminUserDetails = getAdminUserByID($adminUserID);

	$EnquiryAddedBy= $adminUserDetails['admin_name'];

	$enquiry_details['enquiry_added_by']=$EnquiryAddedBy;

	$holderAdminID = $enquiryDetails['current_lead_holder']; 
	$adminUserDetails = getAdminUserByID($holderAdminID);

	$EnquiryCurrentlyHandledBy= $adminUserDetails['admin_name'];

	$enquiry_details['enquiry_currently_handled_by']=$EnquiryCurrentlyHandledBy;






	$root=array(
		"enquiry"=>$enquiry,
		"customer"=>$customer_detail_array,
		"contact"=>getCustomerContactNo($customer_id),
		"follow_up_details"=>$final_follow_up_details,
		"product_details"=>$product_details,
		"enquiry_details"=>$enquiry_details);

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
	
	$upcomming=viewFollowUps(getTodaysDate(),null,null,null,null,null,null,null,null,null,null,null,null,$admin_id);

	uasort($upcomming, "EMIPaymentDatesComparatorForEmiReports");

	$new_upcomming = array();

	foreach($upcomming as $d)
	{
		$exp=explode("^", $d["next_follow_up_date"]);

		$exp1 = explode("#", $exp[1]);

		$discussion = $exp1[0];
		$handled_by = $exp1[1];
		$d['discussion'] = $discussion;
		$d['handled_by'] = $handled_by;
		$new_upcomming[] = $d;
	}	


	$expired=viewFollowUps(null,getDateBeforeDaysFromTodaysDate(1),null,null,null,null,null,null,null,null,null,null,null,$admin_id);

	uasort($expired, "EMIPaymentDatesComparatorForEmiReports");

	$new_expired = array();

	foreach($expired as $d)
	{
		$exp=explode("^", $d["next_follow_up_date"]);

		$exp1 = explode("#", $exp[1]);

		$discussion = $exp1[0];
		$handled_by = $exp1[1];
		$d['discussion'] = $discussion;
		$d['handled_by'] = $handled_by;
		$new_expired[] = $d;
	}

	echo json_encode(array("upcoming"=>$new_upcomming,"expired"=>$new_expired));

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


	case 'get_product_dropdown_data':

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