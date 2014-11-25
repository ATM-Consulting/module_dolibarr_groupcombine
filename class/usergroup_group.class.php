<?php

class TUserGroup_Group extends TObjetStd {
	
	
	function __construct() { /* declaration */
		global $langs;

		parent::set_table(MAIN_DB_PREFIX.'usergroup_group');
		parent::add_champs('entity,fk_group,fk_usergroup','type=entier;index;');
		parent::add_champs('mode','type=chaine;index;');
				
		parent::_init_vars();
		parent::start();
		
		$this->TMode=array(
			'UNION'=>$langs->Trans('Union')
			,'INTERSEC'=>$langs->Trans('Intersect')
		);


		$this->TGroup = array();
	}
	
	static function getGroups(&$ATMdb, $fk_usergroup, $justId=false) {
		global $conf,$db;
		
		dol_include_once('/user/class/usergroup.class.php');
		
		$TGroup=array('UNION'=>array(), 'INTERSEC'=>array());
		$ATMdb->Execute("SELECT fk_group, mode FROM ".MAIN_DB_PREFIX."usergroup_group WHERE fk_usergroup=".(int)$fk_usergroup);
		while($obj = $ATMdb->Get_line()) {
			
			if($justId) {
				$TGroup[$obj->mode][] = $obj->fk_group;
			}
			else{
				$g=new UserGroup($db);
				$g->fetch($obj->fk_group);
				
				$TGroup[$obj->mode][] = $g;
					
			}
			
		}
		
		return $TGroup;
	}
	
	static function getUsers(&$ATMdb, $fk_usergroup) {
		
		$TUser=array();
	
		$TGroup = TUserGroup_Group::getGroups($ATMdb, $fk_usergroup);

		foreach($TGroup['UNION'] as $g) {
			
			$Tab = $g->listUsersForGroup('', 1);
			
			$TUser=array_merge($TUser, $Tab);
			
		}
		
		foreach($TGroup['INTERSEC'] as $g) {

			$Tab = $g->listUsersForGroup('',1);

			if(empty($TUser))$TUser=$Tab;
			else $TUser=array_intersect($TUser, $Tab);
		}
		
		$TUser = array_unique($TUser, SORT_NUMERIC);
		
		return $TUser;

	}
	
	static function updateUserLink($ATMdb, $fk_usergroup, $notUsers=array()) {
		global $db, $conf;
		
		$TUser = TUserGroup_Group::getUsers($ATMdb, $fk_usergroup);
		
		$g=new UserGroup($db);
		$g->fetch($fk_usergroup);
		$Tab = $g->listUsersForGroup('',1);
		
		foreach($TUser as $idu) {
					
			if(!in_array($idu, $Tab) && !in_array($idu, $notUsers)) {
						
				$u=new User($db);
				$u->fetch($idu);
				//print "ajout $idu $fk_usergroup<br/>";
				$u->SetInGroup($fk_usergroup, $conf->entity);
				
			}	
			
		}
		
		foreach($Tab as $idu) {
			
			if(!in_array($idu, $TUser) && !in_array($idu, $notUsers)) {
						
				$u=new User($db);
				$u->fetch($idu);
				//print "suppr $idu $fk_usergroup<br/>";
				$u->RemoveFromGroup($fk_usergroup, $conf->entity);
				
			}	
			
		}
		
	}
	
	static function linkGroupUsersToAnother(&$ATMdb, $fk_group, $fk_usergroup=-1) {
		global $conf,$db;
		
		$TGroupTo=array();
		
		if($fk_usergroup>0) {
			$TGroupTo[] = $fk_usergroup;
		}
		else{
			$sql = "SELECT fk_usergroup FROM ".MAIN_DB_PREFIX."usergroup_group WHERE fk_group=".(int)$fk_group;
			$ATMdb->Execute($sql);
			while($obj = $ATMdb->Get_line()) {
				$TGroupTo[] = $obj->fk_usergroup;
			}
				
			
		}
		
		
		foreach ($TGroupTo as $id_group_to) {
		
			TUserGroup_Group::updateUserLink($ATMdb, $id_group_to);
			
		}
		
	
		
	}
	
	static function linkGroupToAnother(&$ATMdb, $fk_group,$fk_usergroup=-1, $mode='UNION') {
		global $conf,$db;
		
		$o=new TUserGroup_Group;
		$o->fk_group = $fk_group;
		$o->fk_usergroup = $fk_usergroup;
		$o->entity = $conf->entity;
		$o->mode = $mode;
		$o->save($ATMdb);
		
		TUserGroup_Group::linkGroupUsersToAnother($ATMdb, $fk_group, $fk_usergroup);
		
	}
	
	static function deleteLinkGroup(&$ATMdb, $fk_group, $fk_usergroup) {
		global $db, $conf;
		
		$g=new UserGroup($db);
			
		$g->fetch($fk_group);
		$Tab = $g->listUsersForGroup('',1);
		foreach($Tab as $idUser=>$dummy) {
			
			$u=new User($db);
			$u->fetch($idUser);
			$u->RemoveFromGroup($fk_usergroup, $conf->entity);

		}
		
		$ATMdb->Execute("DELETE FROM ".MAIN_DB_PREFIX."usergroup_group WHERE fk_usergroup=".(int)$fk_usergroup." AND fk_group=".(int)$fk_group);		
		
	}
	
}
