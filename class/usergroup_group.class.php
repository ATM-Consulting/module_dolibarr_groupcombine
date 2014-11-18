<?php

class TUserGroup_Group extends TObjetStd {
	
	function __construct() { /* declaration */
		global $langs;

		parent::set_table(MAIN_DB_PREFIX.'usergroup_group');
		parent::add_champs('entity,fk_group,fk_usergroup','type=entier;index;');
				
		parent::_init_vars();
		parent::start();

	}
	
	static function linkGroupUsersToAnother(&$ATMdb, $fk_group, $fk_usergroup=-1) {
		global $conf,$db;
		
		$TGroupTo=array();
		
		if($fk_usergroup>0) {
			$TGroupTo[] = $fk_usergroup;
		}
		else{
			$ATMdb->Execute("SELECT fk_usergroup FROM ".MAIN_DB_PREFIX."usergroup_group WHERE fk_group=".(int)$fk_group);
			while($obj = $ATMdb->Get_line()) {
				$TGroupTo[] = $obj->fk_usergroup;
			}
				
			
		}
		
		$g=new UserGroup($db);
		$g->fetch($fk_group);
		$Tab = $g->listUsersForGroup('',1);
		
		foreach($Tab as $idUser=>$dummy) {
				
			$TGroupIn = $g->listGroupsForUser($idUser);
			
			foreach ($TGroupTo as $id_group_to) {
			
				if(!isset($TGroupIn[$id_group_to])) {
					$u=new User($db);
					$u->fetch($idUser);
					$u->SetInGroup($id_group_to, $conf->entity);
					
				}
			}
			
		}
		
	}
	
	static function linkGroupToAnother(&$ATMdb, $fk_group, $fk_usergroup=-1) {
		global $conf,$db;
		
		$o=new TUserGroup_Group;
		$o->fk_group = $fk_group;
		$o->fk_usergroup = $fk_usergroup;
		$o->entity = $conf->entity;
		
		$o->save($ATMdb);
		
		TUserGroup_Group::linkGroupUsersToAnother($ATMdb, $fk_group, $fk_usergroup);
		
	}
	
	static function unlinkUserFromGroup(&$ATMdb, $fk_group, $fk_user) {
		global $conf, $db;
			
		$TGroupTo=array();
		$ATMdb->Execute("SELECT fk_usergroup FROM ".MAIN_DB_PREFIX."usergroup_group WHERE fk_group=".(int)$fk_group);
		while($obj = $ATMdb->Get_line()) {
			$TGroupTo[] = $obj->fk_usergroup;
		}
				
		foreach ($TGroupTo as $id_group_to) {

			$g=new UserGroup($db);
			$g->fetch($id_group_to);
			$Tab = $g->listUsersForGroup('',1);

			if(isset($Tab[$fk_user])) {
				
				$ATMdb->Execute("SELECT count(*) as nb
				FROM ".MAIN_DB_PREFIX."usergroup_group ugg INNER JOIN ".MAIN_DB_PREFIX."usergroup_user ugu ON (ugg.fk_group = ugu.fk_usergroup)
				WHERE ugg.fk_usergroup=".(int)$id_group_to." AND ugu.fk_user=".$fk_user." AND ugg.fk_group!=".$fk_group);
				$obj=$ATMdb->Get_line();
				
				if($obj->nb == 0) {
					$u=new User($db);
					$u->fetch($fk_user);
					$u->RemoveFromGroup($id_group_to, $conf->entity);
				}
				
			}
		}
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
		
			TUserGroup_Group::unlinkUserFromGroup($ATMdb, $fk_usergroup, $u->id);	
		}
		
		$ATMdb->Execute("DELETE FROM ".MAIN_DB_PREFIX."usergroup_group WHERE fk_usergroup=".(int)$fk_usergroup." AND fk_group=".(int)$fk_group);		
		
	}
	
}
