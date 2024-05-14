<?php
/*
 * Created on 26 Feb 2009
 * By E.E. Gilbert
 */

class Person{

	private $uid;
	private $userName;
	private $lastLoginDate;
	private $firstName;
	private $lastName;
	private $title;
	private $institution;
	private $department;
	private $address;
	private $city;
	private $state;
	private $zip;
	private $country;
	private $phone;
	private $email;
	private $guid;
	private $url;
	private $password;
	private $userTaxonomy = array();		// = array($category => array($utid => array($fieldTitle => $fieldValue))); e.g. array("OccurrenceEditor" => array(24 => array("sciname" => "Asteraceae", "geographicScope" => "Maine")))
	private $isTaxonomyEditor = false;

	public  function __construct(){
	}

	public function getUid(){
		return $this->uid;
	}

	public function setUid($id){
		if(is_numeric($id)){
			$this->uid = $id;
		}
	}

	public function getUserName(){
		return $this->cleanOutStr($this->userName);
	}

	public function setUserName($idName){
		if($idName) $this->userName = trim($idName);
	}

	public function getFirstName(){
		return $this->cleanOutStr($this->firstName);
	}

	public function setFirstName($firstName){
		if($firstName) $this->firstName = trim($firstName);
	}

	public function getLastName(){
		return $this->cleanOutStr($this->lastName);
	}

	public function setLastName($lastName){
		if($lastName) $this->lastName = trim($lastName);
	}

	public function getDepartment(){
		return $this->cleanOutStr($this->department);
	}

	public function setDepartment($department){
		if($department) $this->department = trim($department);
	}

	public function getTitle(){
		return $this->cleanOutStr($this->title);
	}

	public function setTitle($title){
		if($title) $this->title = trim($title);
	}

	public function getInstitution(){
		return $this->cleanOutStr($this->institution);
	}

	public function setInstitution($institution){
		if($institution) $this->institution = trim($institution);
	}

	public function getCity(){
		return $this->cleanOutStr($this->city);
	}

	public function setCity($city){
		if($city) $this->city = trim($city);
	}

	public function getState(){
		return $this->cleanOutStr($this->state);
	}

	public function setState($state){
		if($state) $this->state = trim($state);
	}

	public function getCountry(){
		return $this->cleanOutStr($this->country);
	}

	public function setCountry($country){
		if($country) $this->country = trim($country);
	}

	public function getZip(){
		return $this->cleanOutStr($this->zip);
	}

	public function setZip($zip){
		if($zip) $this->zip = trim($zip);
	}

	public function getPhone (){
		return $this->cleanOutStr($this->phone);
	}

	public function setPhone($phone){
		if($phone) $this->phone = trim($phone);
	}

	public function getUserTaxonomy($cat = ''){
		if($cat){
			if(isset($this->userTaxonomy[$cat])){
				return $this->userTaxonomy[$cat];
			}
			else{
				return null;
			}
		}
		return $this->userTaxonomy;
	}

	public function setUserTaxonomy($utArr){
		if(is_array($utArr)){
			$this->userTaxonomy = $utArr;
		}
	}

	public function addUserTaxonomy( $category, $id, $utKey, $utValue){
		$this->userTaxonomy[$category][$id][$utKey] = $utValue;
		if($category == 'OccurrenceEditor'){
			$this->isTaxonomyEditor = true;
		}
	}

    /** Test to see if person has any taxon interest
     * self expressed or assigned with or without an editor role.
     *
     * @return true if person has any entry in usertaxonomy, otherwise false.
     */
    public function getIsHasTaxonInterest(){
        if (count($this->userTaxonomy) > 0) {
           return true;
        } else {
           return false;
        }
    }

	public function getIsTaxonomyEditor(){
		return $this->isTaxonomyEditor;
	}

	public function getEmail(){
		return $this->cleanOutStr($this->email);
	}

	public function setEmail($email){
		if($email) $this->email = trim($email);
	}

	public function getGUID(){
		return $this->cleanOutStr($this->guid);
	}

	public function setGUID($guid){
		if($guid) $this->guid = trim($guid);
	}

	public function getPassword(){
		return $this->password;
	}

	public function setPassword($password){
		$this->password = $password;
	}

	public function getLastLoginDate(){
		return $this->lastLoginDate;
	}

	public function setLastLoginDate($loginDate){
		$this->lastLoginDate = $loginDate;
	}

	private function cleanOutStr($str){
		if($str !== null){
			$quoteReplacements = ['"' => '&quot;'];
			$newStr = strtr($str, $quoteReplacements);
			$apoReplacements = ["'" => '&apos;'];
			$newStr = strtr($newStr, $apoReplacements);
			return $newStr;
		} else{
			return '';
		}
	}

}
?>