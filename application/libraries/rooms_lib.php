<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Rooms_lib{
    private $id;
    private $name;
    private $blocks_id;
    private $vip_quota;
    private $deposit_amt;
    private $rent_amt;
    private $status;
	private $reason;
    private $created_by;
    private $created_date;
    private $modified_by;
    private $modified_date;
    private $ipaddress;
    
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getBlocks_id() {
        return $this->blocks_id;
    }

    public function setBlocks_id($blocks_id) {
        $this->blocks_id = $blocks_id;
    }

    public function getVip_quota() {
        return $this->vip_quota;
    }

    public function setVip_quota($vip_quota) {
        $this->vip_quota = $vip_quota;
    }

    public function getDeposit_amt() {
        return $this->deposit_amt;
    }

    public function setDeposit_amt($deposit_amt) {
        $this->deposit_amt = $deposit_amt;
    }

    public function getRent_amt() {
        return $this->rent_amt;
    }

    public function setRent_amt($rent_amt) {
        $this->rent_amt = $rent_amt;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }
	
	public function getReason() {
        return $this->reason;
    }

    public function setReason($reason) {
        $this->reason = $reason;
    }

    public function getCreated_by() {
        return $this->created_by;
    }

    public function setCreated_by($created_by) {
        $this->created_by = $created_by;
    }

    public function getCreated_date() {
        return $this->created_date;
    }

    public function setCreated_date($created_date) {
        $this->created_date = $created_date;
    }

    public function getModified_by() {
        return $this->modified_by;
    }

    public function setModified_by($modified_by) {
        $this->modified_by = $modified_by;
    }

    public function getModified_date() {
        return $this->modified_date;
    }

    public function setModified_date($modified_date) {
        $this->modified_date = $modified_date;
    }

    public function getIpaddress() {
        return $this->ipaddress;
    }

    public function setIpaddress($ipaddress) {
        $this->ipaddress = $ipaddress;
    }
}
?>
