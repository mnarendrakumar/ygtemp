<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of login
 *
 * @author RAJU
 */
class Booking_model extends MY_Model {
//put your code here
    public function __construct() {
        parent::__construct();
    }
    function login($post) {
        $sql = 'SELECT id,emp_fname,emp_lname,emp_id,user_name,`password`,emp_role FROM users
                WHERE user_name = "'.$post['username'].'" AND `password` = "'.$post['password'].'" AND `status` = 1';
        $rs = $this->db->query($sql);
        $data = $rs->first_row();
        if(!empty($data)) {
            $this->session->set_userdata('user_details',serialize($data));
            return true;
        }
        else {
            return false;
        }
    }
	
	function generateBookingID($blocks_id)
	{
		$sql = 'select count(id)+1 as booking_id from booking_details where YEAR(created_date) = "'.date('Y').'" and blocks_id = "'.$blocks_id.'"; ';
		$qry = $this->db->query($sql);
		$data = $qry->first_row();
		return $data->booking_id;
	}
	
	function getBlockAbbr($blocks_id)
	{
		$sql = 'select abbr from blocks where id = '.$blocks_id;
		$qry = $this->db->query($sql);
		$data = $qry->first_row();
		return $data->abbr;
	}

    function save_booking($post=array()) {
        /*echo '<pre>';
        print_r($post);die;*/
    //$post['url_key'] = str_replace(' ', '_', preg_replace('!\s+!', ' ', $post['title']));

        if(!empty($post['id'])) {
            $post['application_details_id'] = $this->saveRecord(conversion($post,'application_details_lib'),'application_details',array('id'=>$post['id']));
        }
        else {
            $post['application_details_id'] = $this->saveRecord(conversion($post,'application_details_lib'),'application_details');
        }

        if($post['application_details_id']>0) {
            $customer_id = generateCustID($post['application_details_id']);
			$block_abbr = $this->getBlockAbbr($post['blocks_id']);
			$booking_id = $this->generateBookingID($post['blocks_id']);
			$application_number = str_pad($booking_id,5,'0',STR_PAD_LEFT);
			$dev_type_arr = array(0=>'Don',1=>'Dov',2=>'Aut');
			$devotee_type = $dev_type_arr[$_POST['donor']];
			$application_id = date('Y').'-'.$block_abbr.'-'.str_pad($booking_id,5,0,STR_PAD_LEFT);
			//$application_id = $_POST['application_id'].'_'.$post['application_details_id'];
            $this->db->query('update application_details set customer_id = "'.$customer_id.'",application_id = "'.$application_id.'", application_number="'.$application_number.'" where id='.$post['application_details_id']);
            $this->save_other_details($post);
            if(isset($post['original_file_name']) && !empty($post['original_file_name']) ) {
                $db_file_name = $post['db_file_name'];
                $original_file_name = $post['original_file_name'];
                $global_id = $post['application_details_id'];
                $_POST['attachments_id'] = $this->fileupload_model->save_attachment($db_file_name,$original_file_name,$global_id);
            }
        }
        return $post['application_details_id'];
    }

    function save_other_details($post) {
        $tbl_array = array('booking_details','receipts');
        foreach($tbl_array as $table) {
            if(!empty($post['id'])) {
                $this->saveRecord(conversion($post,$table.'_lib'),$table);
            }
            else {
                $this->saveRecord(conversion($post,$table.'_lib'),$table,array('application_details_id'=>$post['application_details_id']));
            }
        }
    }

    function getMasterData() {
        $blocks_sql = 'select id,name from blocks where status = "1" order by name asc';
        $data['blocks'] = $this->getDBResult($blocks_sql, 'object');
        $rooms_sql = 'select id,name from rooms where status = "1"';
        $data['rooms'] = $this->getDBResult($rooms_sql, 'object');
        return $data;
    }

    function getAvaliableBlocksRooms($post) {
    //print_r($post);die;
        if($post['booking_type'] == 1)
        {
            $from_date = isset($post['from_date'])?date('Y-m-d',strtotime($post['from_date'])):NULL;
            $to_date = isset($post['to_date'])?date('Y-m-d',strtotime($post['to_date'])):NULL;
        }
        else
        {
            $from_date = isset($post['from_date'])?date('Y-m-d',strtotime($post['from_date'])-86400):NULL;
            $to_date = isset($post['to_date'])?date('Y-m-d',strtotime($post['to_date'])):NULL;
        }
        $sql = 'SELECT r.id AS id, r.name AS roomname, b.id AS blocks_id, b.name AS blockname
                FROM rooms r
                JOIN blocks b ON r.blocks_id = b.id';
        if(isset($post['vip_quota'])) {
            $sql .= ' WHERE r.vip_quota = 1';
        }
        else {
            $sql .= ' WHERE r.vip_quota != 1';
        }
        $sql .= ' and b.status = 1 and r.status = 1';
        $data = $this->getDBResult($sql, 'object');
        $room_details = array();
        if(!empty($data)) {
            foreach($data as $rooms_data) {
                $room_details[$rooms_data->blocks_id][$rooms_data->id] = array('id'=>$rooms_data->id,'roomname'=>$rooms_data->roomname,'blocks_id'=>$rooms_data->blocks_id,'blockname'=>$rooms_data->blockname);
            }
        }
        if(($from_date != NULL) && ($to_date != NULL)) {
            $booked_rooms_sql = 'SELECT * FROM booking_details bd
                                    WHERE bd.booked_status = "1" and ("'.$from_date.'" >= date_format(bd.from_date,"%Y-%m-%d") AND "'.$from_date.'" <= date_format(bd.to_date,"%Y-%m-%d"))
                                    OR ("'.$to_date.'" >= date_format(bd.from_date,"%Y-%m-%d") AND "'.$to_date.'" <= date_format(bd.to_date,"%Y-%m-%d"))';
            $booked_data = $this->getDBResult($booked_rooms_sql, 'object');
        }
        $booked_rooms = array();
        if(!empty($booked_data)) {
            foreach($booked_data as $details) {
                $booked_rooms[$details->blocks_id][$details->rooms_id] = $details->rooms_id;
            }
        }
        //print_r($booked_rooms);
        //print_r($room_details);
        foreach($booked_rooms as $blockid=>$rooms) {
            foreach($rooms as $roomid=>$rid) {
                unset($room_details[$blockid][$roomid]);
            }
        }
        if(!empty($post['php'])) // if request is from booking/index, return array
        {
            return $room_details;
        }
        //print_r($room_details);
        if($post['blocks_id'] == 0) {
            $block_options = '<option value="">Select Block</option>';
            $room_options = '<option value="">Select Room</option>';
            foreach($room_details as $blockid=>$rooms) {
                if(!empty($rooms)) {
                    foreach($rooms as $roomid=>$roomdetails) {
                        $room_options .= '<option value="'.$roomid.'">'.$roomdetails['roomname'].'</option>';
                        $blockname = $roomdetails['blockname'];
                    }
                    $block_options .= '<option value="'.$blockid.'">'.$blockname.'</option>';
                }
            }
            $ret_data['block_options'] = $block_options;
            $ret_data['room_options'] = $room_options;
            return $ret_data;
        }
        else {
        //print_r($room_details[$post['blocks_id']]);
            $rooms_id = '';
            if(isset($post['rooms_id']))
            {
                $rooms_id = $post['rooms_id'];
            }
            $room_options = '<option value="">Select Room</option>';
            foreach($room_details[$post['blocks_id']] as $blockid=>$rooms) {
                $selected = '';
                if($rooms['id'] == $rooms_id)
                {
                    $selected = 'selected=selected';
                }
                $room_options .= '<option value="'.$rooms['id'].'" '.$selected.'>'.$rooms['roomname'].'</option>';
            }
            $ret_data['block_options'] = false;
            $ret_data['room_options'] = $room_options;
            return $ret_data;
        }
    //echo $block_options;
    //echo '<br>'.$room_options;
        /*$sql = 'SELECT bd.id AS bdid, r.id AS roomid, r.name AS roomname, b.id AS blockid, b.name AS blockname
                FROM rooms r
                LEFT JOIN booking_details bd ON r.id = bd.rooms_id
                LEFT JOIN blocks b ON b.id = r.blocks_id
                WHERE COALESCE("'.$from_date.'" NOT BETWEEN bd.from_date AND bd.to_date, TRUE)
                AND COALESCE("'.$to_date.'" NOT BETWEEN bd.from_date AND bd.to_date, TRUE)
                OR (bd.from_date IS NULL AND bd.to_date IS NULL) AND bd.rooms_id IS NULL';
        $data = $this->getDBResult($sql, 'object');

        foreach($data as $blocks_rooms)
        {
            $br_data['blocks'][$blocks_rooms->blockid] = $blocks_rooms->blockname;
            $br_data['rooms'][$blocks_rooms->blockid][$blocks_rooms->roomid] = $blocks_rooms->roomname;
        }
        $booked_rooms_sql = 'SELECT * FROM booking_details bd
                            WHERE "2012-09-05" BETWEEN bd.from_date AND bd.to_date
                            AND "2012-09-07" BETWEEN bd.from_date AND bd.to_date';
        $booked_rooms = $this->getDBResult($booked_rooms_sql, 'object');
        print_r($booked_rooms);
        if($post['blocks_id'] == 0)
        {
            $block_options = '<option value="0">Select Block</option>';
            foreach($br_data['blocks'] as $blockid=>$blockname)
            {
                $block_options .= '<option value="'.$blockid.'">'.$blockname.'</option>';
            }
            $room_options = '<option value="0">Select Room</option>';
            foreach($br_data['rooms'] as $blockid=>$rooms)
            {
                foreach($rooms as $roomid=>$roomname)
                {
                    $room_options .= '<option value="'.$roomid.'">'.$roomname.'</option>';
                }
            }
            $ret_data['block_options'] = $block_options;
            $ret_data['room_options'] = $room_options;
            return $ret_data;
        }
        else
        {
            $room_options = '<option value="0">Select Room</option>';
            foreach($br_data['rooms'][$post['blocks_id']] as $roomid=>$roomname)
            {
                $room_options .= '<option value="'.$roomid.'">'.$roomname.'</option>';
            }
            $ret_data['block_options'] = false;
            $ret_data['room_options'] = $room_options;
            return $ret_data;
        }*/
    }

    function getRoomBookingDates($post) {
        $sql = 'SELECT bd.from_date, bd.to_date FROM booking_details bd
                WHERE bd.blocks_id = "'.$post['blocks_id'].'" AND bd.rooms_id = "'.$post['rooms_id'].'"';
        $bookeddates = $this->getDBResult($sql, 'object');
        if(!empty($bookeddates)) {
            foreach($bookeddates as $dates) {
                $fromdate = strtotime($dates->from_date);
                $todate = strtotime($dates->to_date);
                for($date = $fromdate;$date < $todate;$date=$date+86400) {
                    $booked_dates[] = array(date('m/d/Y',$date));
                }
            }
        }
        $booked_dates[] = array(); // last record empty to allow all dates to be disabled in date picker
        return $booked_dates;
    }

    function getRoomRentDetails($post) {
        $sql = 'select deposit_amt, rent_amt from rooms where id="'.$post['rooms_id'].'" limit 1';
        $rs = $this->db->query($sql);
        $data = $rs->first_row();
        if(empty($data)) {
            $data = new stdClass();
        }
        $days = (strtotime($post['to_date'])-strtotime($post['from_date']))/86400;
        $data->days = $days;
        $data->act_rent = $data->rent_amt;
        if($post['donorRef'] == "1") {
            $data->act_rent = 0;
            //$data->deposit_amt = 0;
            $data->rent_amt = 0;
        }
        else if($post['donorRef'] == "2") {
                $day1 = new stdclass();
                $nodiscount_days = array('1','7');
                $day1->act_rent = $data->act_rent;
                $day1->deposit_amt = $data->deposit_amt;
                //$day1->rent_amt = $data->rent_amt;
                $day2 = new stdClass();
                if(!in_array(date('N',strtotime($post['from_date'])),$nodiscount_days)) {
                    $day1->act_rent = $data->act_rent/2;
                //$day1->deposit_amt = $data->deposit_amt/2;
                //$day1->rent_amt = $data->rent_amt/2;
                }
                if($days == 2) {
                    $day2->act_rent = $data->act_rent;
                    $day2->deposit_amt = $data->deposit_amt;
                    //$day2->rent_amt = $data->rent_amt;
                    if(!in_array(date('N',strtotime($post['from_date'])+86400),$nodiscount_days)) {
                        $day2->act_rent = $data->act_rent/2;
                    //$day2->deposit_amt = $data->deposit_amt/2;
                    //$day2->rent_amt = $data->rent_amt/2;
                    }
                }
                if(isset($day2->act_rent)) {
                    $data->act_rent = $day1->act_rent+$day2->act_rent;
                    $data->deposit_amt = $day1->deposit_amt;
                /*if($day1->rent_amt > $day2->rent_amt)
                {
                    $data->rent_amt = $day1->rent_amt;
                }
                else
                {
                    $data->rent_amt = $day2->rent_amt;
                }*/
                }
                else {
                    $data->act_rent = $day1->act_rent;
                    $data->deposit_amt = $day1->deposit_amt;
                //$data->rent_amt = $day1->rent_amt;
                }
                $data->rent_amt = 0;
            }
            else {
                $data->act_rent = $days*$data->rent_amt;
            }
        return $data;
    }

    function gettabledetails($tablenames=array()) {
        $tbl_fields = new stdclass();
        foreach($tablenames as $tablename) {
            $sql = "show columns from `".$tablename."`";
            $fields = $this->getDBResult($sql, 'object');
            foreach($fields as $values) {
                $fld = $values->Field;
                $tbl_fields->$fld = '';
            }
        }
        return $tbl_fields;
    }
    public function getDayReport($ip_array) {
        $user_id=$ip_array['user_id'];
        $date=$ip_array['date'];
        $data = array();
		/*
		if(rc.received_by<>rc.modified_by,(rc.advance_amount-rc.advance_amount_old),rc.advance_amount) as advance_amount,
				if(rc.received_by<>rc.modified_by,(rc.deposit_amt-rc.deposit_amt_old),rc.deposit_amt) as deposit_amt,
				if(rc.received_by<>rc.modified_by,(rc.rent_amount-rc.rent_amount_old),rc.rent_amount) as rent_amount,
				if(rc.received_by<>rc.modified_by,(rc.total_amount_paid-rc.total_amount_paid_old),rc.total_amount_paid) as total_amount_paid
		*/
        $sql = "select
				b.name as blockname,r.name as roomname, 
				bd.blocks_id,bd.rooms_id,
				if(rc.received_by=$user_id,if(rc.received_by<>rc.modified_by,rc.advance_amount_old,rc.advance_amount),if(rc.modified_by=$user_id,(rc.advance_amount-rc.advance_amount_old),rc.advance_amount)) as advance_amount,
				if(rc.received_by=$user_id,if(rc.received_by<>rc.modified_by,rc.deposit_amt_old,rc.deposit_amt),if(rc.modified_by=$user_id,(rc.deposit_amt-rc.deposit_amt_old),rc.deposit_amt)) as deposit_amt,
				if(rc.received_by=$user_id,if(rc.received_by<>rc.modified_by,rc.rent_amount_old,rc.rent_amount),if(rc.modified_by=$user_id,(rc.rent_amount-rc.rent_amount_old),rc.rent_amount)) as rent_amount,
				if(rc.received_by=$user_id,if(rc.received_by<>rc.modified_by,rc.total_amount_paid_old,rc.total_amount_paid),if(rc.modified_by=$user_id,(rc.total_amount_paid-rc.total_amount_paid_old),rc.total_amount_paid)) as total_amount_paid,
				if(rc.received_by<>rc.modified_by,'Replaced Room','') as replaced
				from receipts rc
				left join booking_details bd on bd.application_details_id = rc.application_details_id
				left join blocks b on b.id=bd.blocks_id
				left join rooms r on r.id=bd.rooms_id 
				where (rc.received_by=".$user_id." or rc.modified_by=".$user_id.")  and DATE_FORMAT(rc.received_date,'%Y-%m-%d') = '".$date."' and rc.`status`=1
				order by blockname,roomname";
        //echo '<pre>'; echo $sql; die;
        $bookedreport = $this->getDBResult($sql, 'object');

        $booked_report_arr = array();
        $con_total_amount = 0;
        if(!empty($bookedreport)) {
            foreach($bookedreport as $val) {
                $booked_report_arr[$val->blockname][] = array('room_name'=>$val->roomname,
                    'advance_amount'=>$val->advance_amount,
                    'deposit_amt'=>$val->deposit_amt,
                    'rent_amount'=>$val->rent_amount,
                    'total_amount_paid'=>$val->total_amount_paid,
                    'amt_deposit_bank'=>$val->advance_amount+$val->rent_amount,
					'replaced'=>$val->replaced);
                $con_total_amount += $val->total_amount_paid;
            }
        }

        $sql1 = "select
				b.name as blockname,r.name as roomname, 
				bd.blocks_id,bd.rooms_id,p.deposit_refund_amount,p.damage_amount
				from payments p 
				left join receipts rc on p.receipt_id = rc.id
				left join booking_details bd on bd.application_details_id = rc.application_details_id
				left join blocks b on b.id=bd.blocks_id
				left join rooms r on r.id=bd.rooms_id 
				where p.deposit_refund_by=".$user_id." and DATE_FORMAT(p.deposit_refund_date,'%Y-%m-%d') = '".$date."' and p.`status`=1
				order by blockname,roomname";
        //echo $sql1; die;
        $refundreport = $this->getDBResult($sql1, 'object');

        $refund_report_arr = array();
        $con_ref_total_amount = 0;
        $con_damage_total_amount = 0;
        if(!empty($refundreport)) {
            foreach($refundreport as $val) {
                $refund_report_arr[$val->blockname][] = array('room_name'=>$val->roomname,
                    'deposit_refund_amount'=>$val->deposit_refund_amount,
                    'damage_amount'=>$val->damage_amount);
                $con_ref_total_amount += $val->deposit_refund_amount;
                $con_damage_total_amount += $val->damage_amount;
            }
        }

        $data['con_total_amount'] =$con_total_amount;
        $data['booked_report_arr'] =$booked_report_arr;
        $data['con_ref_total_amount'] =$con_ref_total_amount;
        $data['refund_report_arr'] =$refund_report_arr;
        $data['con_damage_total_amount'] =$con_damage_total_amount;
        return $data;
    }
	
	public function monthly_report($month)
	{
		$month = $_POST['year'].'-'.$_POST['month'];
		$day = date('Y-m-d',strtotime($_POST['rep_date']));		
		
		if($_POST['report_type']==1)
		{
			$sub_sql = "DATE_FORMAT(rc.received_date,'%Y-%m') = '".$month."'";
			$heading = date("M", mktime(0, 0, 0, $_POST['month'], 10)).'/'.$_POST['year'].' Monthly Report';
		}
		else
		{
			$sub_sql = "DATE_FORMAT(rc.received_date,'%Y-%m-%d') = '".$day."'";
			$heading = date('d/m/Y',strtotime($_POST['rep_date'])).' Day Report';
		}
		
		$sql = "select count(bd.rooms_id) as roomscount,
				concat(u.emp_fname,' ',u.emp_lname) as ope_name,  
				b.name as blockname,r.name as roomname,bd.blocks_id,bd.rooms_id,
				SUM(rc.advance_amount) as advance_amount,
				SUM(rc.deposit_amt) as deposit_amt,
				SUM(rc.rent_amount) as rent_amount,
				SUM(rc.total_amount_paid) as total_amount_paid,
				SUM(p.deposit_refund_amount) as refund_amount,
				SUM(p.damage_amount) as damage_amount
				from receipts rc
				left join booking_details bd on bd.application_details_id = rc.application_details_id
				left join blocks b on b.id=bd.blocks_id
				left join rooms r on r.id=bd.rooms_id
				left join users u on u.id=rc.received_by
				left join payments p  on p.receipt_id = rc.id
				where ".$sub_sql." and rc.`status`=1
				group by bd.rooms_id
				order by blockname,roomname"; 
		$bookedreport = $this->getDBResult($sql, 'object');
		$total_data = array();
		$booked_report_arr = array();
        $con_total_amount = 0;
        if(!empty($bookedreport)) {
            foreach($bookedreport as $val) {
                $booked_report_arr[$val->blockname][] = array('room_name'=>$val->roomname,
															'no_of_bookings'=>$val->roomscount,
															'advance_amount'=>$val->advance_amount,
															'deposit_amt'=>$val->deposit_amt,
															'rent_amount'=>$val->rent_amount,
															'total_amount_paid'=>$val->total_amount_paid,
															'refund_amount'=>$val->refund_amount,
															'damage_amount'=>$val->damage_amount);
           }
        }
		$total_data['booking_data'] = $booked_report_arr;
		$total_data['heading'] = $heading;
		return $total_data;
	}
	
	public function getconsolidatedReport($month)
	{
		$month = $_POST['year'].'-'.$_POST['month'];
		$day = date('Y-m-d',strtotime($_POST['rep_date']));		
		
		if($_POST['report_type']==2)
		{
			$sub_sql = "DATE_FORMAT(rc.received_date,'%Y-%m') = '".$month."'";
			$heading = date("M", mktime(0, 0, 0, $_POST['month'], 10)).'/'.$_POST['year'].' Monthly Report';
		}
		else
		{
			$sub_sql = "DATE_FORMAT(rc.received_date,'%Y-%m-%d') = '".$day."'";
			$heading = date('d/m/Y',strtotime($_POST['rep_date'])).' Day Report';
		}
		
		$sql = "select u.id as user_id,b.id as block_id,
				concat(u.emp_fname,' ',u.emp_lname) as ope_name,  
				b.name as blockname,r.name as roomname,bd.blocks_id,bd.rooms_id,
				SUM(rc.advance_amount) as advance_amount,
				SUM(rc.deposit_amt) as deposit_amt,
				SUM(rc.rent_amount) as rent_amount,
				SUM(rc.total_amount_paid) as total_amount_paid,
				IFNULL(SUM(p.deposit_refund_amount),'0.00') as refund_amount,
				IFNULL(SUM(p.damage_amount),'0.00') as damage_amount
				from receipts rc
				left join booking_details bd on bd.application_details_id = rc.application_details_id
				left join blocks b on b.id=bd.blocks_id
				left join rooms r on r.id=bd.rooms_id
				left join users u on u.id=rc.received_by
				left join payments p  on p.receipt_id = rc.id
				where ".$sub_sql." and rc.`status`=1
				group by bd.created_by, bd.blocks_id
				order by u.id,blockname,roomname"; 
		$bookedreport = $this->getDBResult($sql, 'object');
		$total_data = array();
		$booked_report_arr = array();
		$users = array();
        $con_total_amount = 0;
        if(!empty($bookedreport)) {
            foreach($bookedreport as $val) {
                $users[$val->user_id] = $val->ope_name;
				$booked_report_arr[$val->user_id][$val->block_id] = array('blockname'=>$val->blockname,
															'ope_name'=>$val->ope_name,
															'advance_amount'=>$val->advance_amount,
															'deposit_amt'=>$val->deposit_amt,
															'rent_amount'=>$val->rent_amount,
															'total_amount_paid'=>$val->total_amount_paid,
															'refund_amount'=>$val->refund_amount,
															'damage_amount'=>$val->damage_amount);
           }
        }
	
		$users = array_unique($users);
		$m_data = $this->getMasterData();
		
		$final_data = array();
		$details=array();
		foreach ($m_data['blocks'] as $val)
		{
			$block_id = $val->id;
			
			foreach($users as $k_u=>$k_v)
			{
				$details['block_name'] = $val->name;
				if(isset($booked_report_arr[$k_u][$block_id]))
				{
					$total_collected_amount = $booked_report_arr[$k_u][$block_id]['advance_amount']+$booked_report_arr[$k_u][$block_id]['deposit_amt']+$booked_report_arr[$k_u][$block_id]['rent_amount'];
					$refund_amount = $booked_report_arr[$k_u][$block_id]['refund_amount'];
					$damage_amount = $booked_report_arr[$k_u][$block_id]['damage_amount'];
					$total_amt = $total_collected_amount-$refund_amount;
					
					$details['total_collected_amount'.$k_u] = $total_collected_amount;
					$details['refund_amount'.$k_u] = $refund_amount;
					$details['damage_amount'.$k_u] = $damage_amount;
					$details['total_amt'.$k_u] = $total_amt;
				}
				else
				{
					$details['total_collected_amount'.$k_u] = '0.00';
					$details['refund_amount'.$k_u] = '0.00';
					$details['damage_amount'.$k_u] = '0.00';
					$details['total_amt'.$k_u] = '0.00';
				}

			}
			
			$final_data[$block_id] = $details;
		}
		
		$total_data['booking_data'] = $final_data;
		$total_data['users'] = $users;
		$total_data['heading'] = $heading;
		/*echo '<pre>';
		print_r($total_data);
		die;*/
		return $total_data;
	}

    public function getBookingDetails($where_cond = 1) {
        $sql = "select b.id as block_id, r.id as room_id, ad.id as app_det_id,ad.application_id, ad.customer_id,
				ad.applicant_name, ad.no_of_persons,
                concat(ad.applicant_address,' ','Ph No:',ad.phone_no)as applicant_address,
                concat(u.emp_fname,' ',u.emp_lname) as user_name,att.url as image_path,
                bd.id as booking_det_id,date_format(bd.from_date,'%d/%m/%Y %h:%i %p') as from_date, date_format(bd.to_date,'%d/%m/%Y %h:%i %p') as to_date,
                date_format(bd.checkout_date,'%d/%m/%Y %h:%i %p') as checkout_date,date_format(ad.created_date,'%d/%m/%Y') as created_date,
                bd.no_of_days, bd.booking_type,bd.booked_status,bd.booking_type,
                b.name as block_name,b.telugu_name,r.name as room_name,
                rp.id as rcpt_id,rp.deposit_amt,rp.rent_amount,rp.advance_amount,rp.total_amount_paid,rp.total_amount_paid_old
                from application_details ad
                left join booking_details bd on ad.id = bd.application_details_id
                left join blocks b on bd.blocks_id = b.id
                left join rooms r on bd.rooms_id = r.id
                left join receipts rp on rp.application_details_id = ad.id
                left join users u on u.id = ad.created_by
                left join attachments att on att.global_id = ad.id
                where ".$where_cond;
        $booking_details = $this->getDBResult($sql, 'object');

        return $booking_details;
    }
	

    public function getBookingStatus($where_cond = 1) {
        $sql = "select booked_status from booking_details where ".$where_cond;
        $booked_status = $this->getDBResult($sql, 'object');
        return $booked_status;
    }

    public function updateBookingDetails($ip_array = array()) {
        $return_response = false;
        if(!empty($ip_array)) {
            foreach($ip_array as $table=>$data) {
                $this->saveRecord(conversion($data,$table.'_lib'),$table);
            }
            $sql = 'insert into booking_history_details (select * from booking_details where id = '.$ip_array['booking_details']['id'].')';

            $this->db->query($sql);

            $return_response = 'success';
        }
        return $return_response;
    }

    public function getPendingRooms() {
        $end_datetime = date('Y-m-d H:i:s',time()+3600); // get 23hrs previous time 82800
        $sql = 'select bd.id,bd.blocks_id,b.name as block_name, bd.rooms_id,r.name as room_name,bd.from_date,bd.to_date, 
				date_format(bd.from_date,"%d/%m/%Y %h:%i %p") as dis_from_date, date_format(bd.to_date,"%d/%m/%Y %h:%i %p") as dis_to_date from 
				booking_details bd
				LEFT JOIN blocks b on b.id = bd.blocks_id
				LEFT JOIN rooms r ON r.id = bd.rooms_id 
				where to_date <= "'.$end_datetime.'" and  booked_status = 1';
        $data_flds = array('block_name','room_name','dis_from_date','dis_to_date','<span style="color:{%color%}">{%hours%}</span>');
        $extra_logic = true;
        return $this->display_grid($_POST,$sql,$data_flds,$extra_logic);
    //$rs = $this->db->query($sql);
        /*echo '<pre>';
        print_r($rs->result());die;*/
    }

    public function getPendingRoomsCnt() {
        $end_datetime = date('Y-m-d H:i:s',time()+3600); // get 23hrs previous time 82800
        $sql = 'select count(id) as cnt from booking_details where to_date <= "'.$end_datetime.'" and  booked_status = 1';
        $rs = $this->db->query($sql);
        $data = $rs->first_row();
        if(!empty($data)) {
            return $data->cnt;
        }
        else {
            return '0';
        }
        /*echo '<pre>';
        print_r($rs->result());die;*/
    }

    public function after4Hours() {
        $b4hours = date('Y-m-d H:i:s',time()-4*3600); // get 23hrs previous time 82800
        $sql = 'select id,application_details_id,to_date from booking_details where to_date <= "'.$b4hours.'" and  booked_status = 1';
        $rs = $this->db->query($sql);
        $result = $rs->result();
        if ($result) {
            foreach ($result as $row) {
                $excess_hrs = round((time()-strtotime($row->to_date))/(60*60));
                if($excess_hrs >= 4) {
                    $sql = 'UPDATE receipts SET rent_amount = (rent_amount+deposit_amt), deposit_amt = 0 WHERE application_details_id = '.$row->application_details_id;
                    $sql2 = 'Update booking_details SET booked_status = "0" where id='.$row->id;
                    $this->db->query($sql);
                    $this->db->query($sql2);
                }
            }
        }
    }

    //users SET
    function getusers() {
        $sql = 'SELECT u.id,concat(u.emp_fname," ",u.emp_lname) as emp_name, u.emp_id, u.user_name, u.password, u.emp_role as emp_role,r.name as role,
    CASE WHEN u.status =1 THEN "Active" ELSE "Inactive" END as status
    FROM users u
    LEFT JOIN roles r on u.emp_role = r.id
    where u.emp_role!=1 ';

        $data_flds = array('emp_name','emp_id','role','user_name','status',"<a class='btn edit_ecur' href='".site_url()."admin/userview/{%id%}' id='{%id%}'><span class='inner-btn'><span class='label'>Edit</span></span></a>");
        return $this->display_grid($_POST,$sql,$data_flds);
    }

    function display_grid($postvals,$sql,$array_fields,$extra_logic=false) {
        return $this->jqgrid($postvals,$sql,$array_fields,$extra_logic);
    }

    function replaceRoom($post)
    {
        $user_details = unserialize($this->session->userdata('user_details'));
        $receipt_details_sql = 'SELECT * FROM receipts WHERE id = '.$post['rcpt_id'];
        $rs = $this->db->query($receipt_details_sql);
        $receipt_details = $rs->result();

        $sql = 'UPDATE `booking_details` SET `blocks_id`='.$post['blocks_id'].', `rooms_id`='.$post['rooms_id'].', `modified_by`='.$user_details->id.', `modified_date`="'.date('Y-m-d h:i:s').'", `ipaddress`="'.ipaddress().'" WHERE  `application_details_id`='.$post['app_det_id'].' LIMIT 1;';
        if($this->db->query($sql))
        {
            //$receipt_details = $receipt_details[0];
            $update_receipt_sql = 'UPDATE receipts SET deposit_amt="'.$post['deposit_amt'].'",rent_amount="'.$post['rent_amount'].'",
                                    advance_amount="'.$post['advance_amount'].'",total_amount_paid="'.($post['deposit_amt']+$post['rent_amount']).'",
                                    deposit_amt_old="'.$receipt_details[0]->deposit_amt.'",rent_amount_old="'.$receipt_details[0]->rent_amount.'",
                                    advance_amount_old="'.$receipt_details[0]->advance_amount.'",total_amount_paid_old="'.$receipt_details[0]->total_amount_paid.'",
                                    modified_by="'.$user_details->id.'" WHERE id='.$post['rcpt_id'];
            $this->db->query($update_receipt_sql);
            return $post['app_det_id'];
        }
        else
        {
            return false;
        }
    }
	
	function getdcrdetails()
	{
		$month = $_POST['year'].'-'.$_POST['month'];
		$day = date('Y-m-d',strtotime($_POST['rep_date']));		
		
		if($_POST['report_type']==2)
		{
			$sub_sql = "DATE_FORMAT(bd.created_date,'%Y-%m') = '".$month."'";
			$heading = 'Monthly Collection Report of '.date("M", mktime(0, 0, 0, $_POST['month'], 10)).'/'.$_POST['year'];
		}
		else
		{
			$sub_sql = "DATE_FORMAT(bd.created_date,'%Y-%m-%d') = '".$day."'";
			$heading = 'Daily Collection Report of '.date('d/m/Y',strtotime($_POST['rep_date']));
		}
		
		$no_of_bookings_sql = "SELECT b.id AS block_id,
								bd.no_of_days, count(bd.blocks_id) as no_of_bookings
								FROM receipts rc
								LEFT JOIN booking_details bd ON bd.application_details_id = rc.application_details_id
								LEFT JOIN blocks b ON b.id=bd.blocks_id
								LEFT JOIN rooms r ON r.id=bd.rooms_id
								WHERE ".$sub_sql." AND rc.`status`=1
								GROUP BY bd.no_of_days, bd.blocks_id";
		$no_of_bookings_qry = $this->db->query($no_of_bookings_sql);
		$no_of_bookings_rs = $no_of_bookings_qry->result();
		$dcr_arr1 = array();
		foreach($no_of_bookings_rs as $data)
		{
			$dcr_arr1[$data->block_id][$data->no_of_days] = $data->no_of_bookings;
		}
		
		$app_nos_sql = "select bd.blocks_id AS block_id,concat(b.name,' (',b.abbr,')') AS blockname,rm.rent_amt,count(ad.id) as total_bookings, sum(r.rent_amount) as total_rent_amount, 
						min(ad.application_number) as app_start, 
						max(ad.application_number) as app_end from receipts r
						inner join application_details ad on ad.id = r.application_details_id
						inner join booking_details bd on bd.application_details_id = ad.id
						inner JOIN blocks b ON b.id=bd.blocks_id
						inner JOIN rooms rm ON rm.id=bd.rooms_id
						WHERE ".$sub_sql." 
						group by bd.blocks_id";
		$app_nos_qry = $this->db->query($app_nos_sql);
		$app_nos_rs = $app_nos_qry->result();
		$dcr_arr2 = array();
		foreach($app_nos_rs as $data)
		{
			$dcr_arr2[$data->block_id] = array('blockname'=>$data->blockname,'rent_amt'=>$data->rent_amt,'total_bookings'=>$data->total_bookings,'total_rent_amount'=>$data->total_rent_amount,'app_start'=>$data->app_start,'app_end'=>$data->app_end);
		}
		
		$m_data = $this->getblocksinfo();
		$dcr_final_arr = array();
		foreach($m_data as $val)
		{
			if(isset($dcr_arr2[$val->id]))
			{
				$dcr_final_arr[$val->id] = $dcr_arr2[$val->id];
				$dcr_final_arr[$val->id][1] = (isset($dcr_arr1[$val->id][1])?$dcr_arr1[$val->id][1]:0);
				$dcr_final_arr[$val->id][2] = (isset($dcr_arr1[$val->id][2])?$dcr_arr1[$val->id][2]:0);
			}
			else
			{
				$dcr_final_arr[$val->id] = array('blockname'=>$val->name,'rent_amt'=>$val->rent_amt,'total_bookings'=>0,'total_rent_amount'=>0,'app_start'=>0,'app_end'=>0);
				$dcr_final_arr[$val->id][1] = 0;
				$dcr_final_arr[$val->id][2] = 0;
			}
		}
		
		$total_data['dcr_details'] = $dcr_final_arr;
		$total_data['heading'] = $heading;
		return $total_data;
		
	}
	
	function getblocksinfo()
	{
		$sql="select b.id,concat(b.name,' (',b.abbr,')') as name, r.rent_amt from
				blocks b 
				left join rooms r on b.id = r.blocks_id 
				where b.`status`='1'
				group by r.blocks_id ";
		$data = $this->getDBResult($sql, 'object');
        return $data;		
	}
}