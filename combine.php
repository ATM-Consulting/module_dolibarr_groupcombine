<?php

	require('config.php');
	
	dol_include_once('/user/class/usergroup.class.php');
	dol_include_once('/core/lib/usergroups.lib.php');
	dol_include_once('/groupcombine/class/usergroup_group.class.php');

	$langs->load("users");
	$langs->load("other");
	$langs->load("groupcombine@groupcombine");
	
	llxHeader('',$langs->trans("GroupCard"));
	
	$action=GETPOST('action');
	
	$ATMdb=new TPDOdb;
	
	if($action==='addgroup') {
		
		TUserGroup_Group::linkGroupToAnother($ATMdb, GETPOST('fk_group'), GETPOST('id'), GETPOST('mode'));
	
		setEventMessage($langs->trans('GroupLinked'));	
	}
	else if($action==='dellink') {
		TUserGroup_Group::deleteLinkGroup($ATMdb, GETPOST('fk_group'), GETPOST('id'));
		setEventMessage($langs->trans('GroupLinkDeleted'));
	}
	
	// Defini si peux lire/modifier utilisateurs et permisssions
	$canreadperms=($user->admin || $user->rights->user->user->lire);
	$caneditperms=($user->admin || $user->rights->user->user->creer);
	$candisableperms=($user->admin || $user->rights->user->user->supprimer);
	// Advanced permissions
	if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
	{
	    $canreadperms=($user->admin || $user->rights->user->group_advance->read);
	    $caneditperms=($user->admin || $user->rights->user->group_advance->write);
	    $candisableperms=($user->admin || $user->rights->user->group_advance->delete);
	}
		
	
	$result = restrictedArea($user, 'user', $id, 'usergroup&usergroup', 'user');
	
	print_fiche_titre($langs->trans("MultiGroup"));
	
	$object=new UserGroup($db);
	$object->fetch(GETPOST('id'));
	
	$head = group_prepare_head($object);
    $title = $langs->trans("MultiGroup");
    dol_fiche_head($head, 'multigroup', $title, 0, 'group');
	
	
	$form = new Form($db);
	
	print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
			print '<td colspan="2">';
			print $form->showrefnav($object,'id','',$user->rights->user->user->lire || $user->admin);
			print '</td>';
			print '</tr>';

			// Name
			print '<tr><td width="25%" valign="top">'.$langs->trans("Name").'</td>';
			print '<td width="75%" class="valeur">'.$object->name;
			if (empty($object->entity))
			{
				print img_picto($langs->trans("GlobalGroup"),'redstar');
			}
			print "</td></tr>\n";

			// Multicompany
			if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
			{
				$mc->getInfo($object->entity);
				print "<tr>".'<td valign="top">'.$langs->trans("Entity").'</td>';
				print '<td width="75%" class="valeur">'.$mc->label;
				print "</td></tr>\n";
			}

			// Note
			print '<tr><td width="25%" valign="top">'.$langs->trans("Note").'</td>';
			print '<td class="valeur">'.dol_htmlentitiesbr($object->note).'&nbsp;</td>';
			print "</tr>\n";

			print "</table>\n";

			print '</div>';
	
			$ugg=new TUserGroup_Group;
			$TGroupLinked = TUserGroup_Group::getGroups($ATMdb, $object->id, true);
			
			$formATM=new TFormCore('auto','formCG', 'post');
			echo $formATM->hidden('action', 'addgroup');
			echo $formATM->hidden('id', $object->id);
	
			echo $form->select_dolgroups(-1, 'fk_group',1, array_merge($TGroupLinked['UNION'],$TGroupLinked['INTERSEC'],array($object->id)) );
			echo ' - '.$formATM->combo($langs->trans('MultiGroupMode'), 'mode', $ugg->TMode, 'INTERSEC' );
			
			echo $formATM->btsubmit($langs->trans('Add'), 'btadd');
	
			$formATM->end();
			
			
			echo '<br />';
	
			$l=new TListviewTBS('listCombine');
			
			$sql = "SELECT ug.rowid as 'Id', ug.nom, ugg.mode, '' as 'Actions'
			FROM ".MAIN_DB_PREFIX."usergroup_group ugg	LEFT JOIN ".MAIN_DB_PREFIX."usergroup ug ON (ugg.fk_group=ug.rowid) 
			WHERE ugg.fk_usergroup = ".$object->id."
			ORDER BY  ug.nom
			";
						
			print $l->render($ATMdb, $sql,array(
			
				'liste'=>array(
					'titre'=>$langs->trans("ListOfGroupCombineInGroup")
				)
				,'translate'=>array(
					'mode'=>$ugg->TMode
				)
				,'link'=>array(
					'Actions'=>'<a href="?action=dellink&fk_group=@Id@&id='.$object->id.'">'.img_delete().'</a>'
					,'nom'=>img_picto('', 'object_group.png').' <a href="'.dol_buildpath('/user/group/fiche.php',1).'?id=@Id@">@val@</a>'
				)
				
			));
			
			
		print_fiche_titre($langs->trans("MultiGroupUserLinked"));	
		
		?>
		<table class="liste" width="100%">
			<tr class="liste_titre">
				<td class="liste_titre"><?php echo $langs->trans('User') ?></td>
			</tr>
			<?php
			
			$TUser = TUserGroup_Group::getUsers($ATMdb, $object->id);
			foreach($TUser as $idu) {
								
				$u=new User($db);
				$u->fetch($idu);			
							
				$class = ($class =='impair') ? 'pair' : 'impair';		
						
				?><tr class="<?php echo $class ?>">
					<td>
						<?php echo $u->getNomUrl(1); ?>
					</td>
				</tr>
				
				<?php
				
			}
			
			?>
		</table>
		<?php
	
	
	llxFooter();

	
