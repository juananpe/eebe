<?php
/**
 * Babelium Project open source collaborative second language oral practice - http://www.babeliumproject.com
 * 
 * Copyright (c) 2013 GHyM and by respective authors (see below).
 * 
 * This file is part of Babelium Project.
 *
 * Babelium Project is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Babelium Project is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'utils/Config.php';
require_once 'utils/Datasource.php';
require_once 'vo/UserVO.php';
	
	session_start();

/**
 * 
 * @author Babelium Team
 *
 */
class Admin {

	private $conn;

	/**
	 * Constructor function
	 *
	 * @throws Exception
	 * 		Throws an error if the one trying to access this class is not successfully logged in on the system
	 * 		or there was any problem establishing a connection with the database.
	 */
	public function __construct() {
		try {
			$settings = new Config ( );
			$this->conn = new Datasource ( $settings->host, $settings->db_name, $settings->db_username, $settings->db_password );


		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	public function showMyGroups(){
		$groups= array();
        $sql = "select ID, name, description from groups where ID in 
                    (select DISTINCT fk_group_id from enrolment where role='teacher' and 
                        fk_user_id = %d)";
		$result = $this->conn->_multipleSelect($sql, $_SESSION['uid']);
	if ($result == ''){
		echo "You haven't created any group yet";
        } else {
		foreach($result as $key=>$group){
		    array_push($groups, $group);
		}
		echo "<form name='groupsForm' action='admin.php' method='post'>";
		echo "Select your group:";
		echo "<select name='group'>";
		foreach($groups as $group){
		    echo "<option value='$group->ID'>$group->name </option>";
		}
		echo "</select><p>";

		echo "<input type='hidden' name='action' value='showAddGroupMembers'>";
		echo "<input type='Submit' value='Show/Add members'>";
		echo "</form>";
	}
        echo "<p><a href='admin.php'>Main menu</a>"; 
    }

    public function isTeacher(){
        $sql = "select fk_group_id
                from enrolment where role='teacher'
                and fk_user_id=%d LIMIT 1";
        $result = $this->conn->_singleSelect($sql, $_SESSION['uid']);
        if ($result)
            return true;
        else
            return false;
    }

    public function addNewGroup($name, $description){
        $sql = "insert into groups set name='%s', description='%s', date=NOW()";
        $lastId = $this->conn->_insert($sql, $name, $description);
        $this->assignUserToGroup($_SESSION['uid'], $lastId, "teacher");
 
        echo "Group correctly created";
        echo "<p><a href='admin.php'>Main menu</a>"; 
    }

    public function showNewGroup(){
        echo "<form name='groupsForm' action='admin.php' method='post'>";
        echo "Set a name for the new group: <input type='text' name='name'><br>"; 
        echo "Insert a description for the new group: <br><textarea name='description' cols=30 rows=5></textarea><br>";
        echo "<input type='hidden' name='action' value='addNewGroup'>";
        echo "<input type='Submit' value='Create Group'>";
        echo "</form>";
        echo "<p><a href='admin.php'>Main menu</a>"; 

    }

	public function addUser($login, $dni, $email, $realName, $realSurname){
		$pass = sha1($dni);

		$this->conn->_startTransaction();
		$sql = "INSERT INTO users (ID, name, password, email, realName, realSurname, creditCount, joiningDate, active, activation_hash, isAdmin) VALUES('', '%s', '%s', '%s', '%s', '%s', '1000', NOW(), '1', '', '0')";

		$id = $this->conn->_insert($sql, $login,$pass,$email,$realName,$realSurname);

		$sql = "INSERT INTO user_languages (id, fk_user_id, language, level, positives_to_next_level, purpose) VALUES('', %d, 'en_GB', '5', '15', 'practice')";
		$this->conn->_insert($sql, $id);

		$sql = "INSERT INTO user_languages (id, fk_user_id, language, level, positives_to_next_level, purpose) VALUES('', %d, 'es_ES', '7', '15', 'evaluate')";
		$this->conn->_insert($sql, $id);

		$sql="INSERT INTO user_languages (id, fk_user_id, language, level, positives_to_next_level, purpose) VALUES('', %d, 'en_GB', '5', '15', 'evaluate')";
		$this->conn->_insert($sql, $id);

		$sql= "INSERT INTO user_languages (id, fk_user_id, language, level, positives_to_next_level, purpose) VALUES('', %d, 'eu_ES', '7', '15', 'evaluate')";
		$this->conn->_insert($sql, $id);

		$this->conn->_endTransaction();

	}

    public function assignUsersToGroup($newMembers, $group, $role='student'){
        $text = trim($newMembers);
        $textAr = explode("\n", $text);
        $textAr = array_filter($textAr, 'trim'); // remove any extra \r characters left behind

        foreach ($textAr as $line) {
            $user = trim($line);
		// find user_name in SO - We already have its user_id 
		// insert row_names, user_id, user_name in stack.info
		$api = "compress.zlib://https://api.stackexchange.com/2.2/users/$user?order=desc&sort=reputation&site=stackoverflow";
		$resJSON = file_get_contents($api, false, stream_context_create(array('http'=>array('header'=>"Accept-Encoding: gzip\r\n"))));
		$res = json_decode($resJSON)->items[0];

		// $res = new stdclass;
		// $res->user_name	
            $this->addNewUser($res);
            $this->assignUserToGroup($res->user_id, $group, $role);
            echo "User " . $line . " : " . $res->display_name  . " correctly assigned. <br>\n";
        }
        echo "<form name='showAddUserForm' action='admin.php' method='post'>";
		echo "<input type='hidden' name='group' value='$group'>";
		echo "<input type='hidden' name='action' value='showAddGroupMembers'>";
		echo "<input type='Submit' value='Show Users of this group'>";
		echo "</form>";
		echo "<p><a href='admin.php'>Back to Administration Panel</a>"; 
    }

	public function addNewUser($user){
	 $sql = "SELECT max(cast(row_names as unsigned)) as lastrow from info";
		 $row = $this->conn->_singleSelect($sql);

		 $sql = "INSERT INTO info(row_names, user_id, user_name)
			VALUES ('%d', %d, '%s')";
		 $this->conn->_insert($sql, ($row->lastrow)+1, $user->user_id, $user->display_name);

		// error_log("User: " . $user->user_id ."\n", 3, "/tmp/error.log");

	}
	public function assignUserToGroup($user, $groupid, $role){
			$sql = "INSERT INTO enrolment(fk_group_id, fk_user_id, role)
			VALUES (%d, %d, '%s')";
		$this->conn->_insert($sql, $groupid,  $user,  $role);
	}

	public function showOptions(){
		echo "<h1> Administration Panel </h1>";
		echo "<br><a href='?action=showMyGroups'>Show my Groups</a>";
		echo "<br><a href='?action=showNewGroup'>Add New Group</a>";
		echo "<br><a href='index.php?action=logout'>Logout</a> ( ".$_SESSION['uid'] .")";
/*
		echo "<br><a href='?action=impersonate'>Log in as another user</a>";
		echo "<br><a href='?action=assign'>Assign User to Group</a>";
		echo "<br><a href='?action=add'>Add new User </a>";
		echo "<br><a href='?action=statistics'>Show statistics</a>";
		echo "<br><a href='?action=reloadResponsesCache'>Reload Responses Cache</a>";
		echo "<br><a href='informe.php?action=attempts'>Show specific user attempts</a>";
		echo "<br><a href='informe.php'>Show all users' attempts</a>";
*/
		}

	public function showAddUser(){

		echo "<form name='assign' action='admin.php' method='post'>";
		echo "Login: <input type='text' name='login'><br>";
		echo "Dni: <input type='text' name='dni'><br>";
		echo "Email: <input type='text' name='email'><br>";
		echo "Real Name: <input type='text' name='realName'><br>";
		echo "Real Surname: <input type='text' name='realSurname'><br>";
		echo "<input type='hidden' name='action' value='add'>";
		echo "<input type='Submit' value='Save'>";
		echo "</form>";
		echo "<p><a href='admin.php'>Main menu</a>";
	}

	public function showAddGroupMembers($group){

		$users = array();

          $sql = "select ID, name, description 
                    from groups where ID = %d";

        $result = $this->conn->_singleSelect($sql, $group);
        $groupName = $result->name;
        $groupDescription = $result->description;
        echo "<h1>Group: $groupName - $groupDescription </h1>";

		$sql = "SELECT i.user_name as uname, i.user_id as uid, e.role 
                FROM info i, enrolment e where e.fk_user_id = i.user_id and fk_group_id=%d";
		$result = $this->conn->_multipleSelect($sql,$group);
		foreach($result as $key=>$user){
			array_push($users, $user);
		}
        echo "<table>";
        echo "<tr><td>SO uid</td><td>name</td><td>email</td><td>role</td></tr>";
		foreach($users as $user){
            echo "<tr>";

            if ($user->role == 'teacher') $newrole = 'student';
            else $newrole = 'teacher';

			echo "<td><a href='http://stackoverflow.com/users/$user->uid'>$user->uid</a></td><td> $user->uname</td><td> $user->email</td><td> <a href='admin.php?action=changeRole&group=$group&user=$user->uid&role=$newrole'>$user->role</a></td>";
            echo "</tr>";  
      }
        echo "</table><p><h2>Assign new users to this group</h2>Insert one user_id per line.</h2><br>";
        echo "We will check that such ID actually exists in SO and will assign it to this group <br>";
        echo "<form name='newMembersForm' action='admin.php' method='post'>";
        echo "<textarea name='newMembers' cols='40' rows='10'></textarea><br>";

		echo "<input type='hidden' name='group' value='$group'>";
		echo "<input type='hidden' name='action' value='assignUsersToGroup'>";
		echo "<input type='Submit' value='Assign Users To Group'>";
		echo "</form>";
		echo "<p><a href='admin.php'>Back to Administration Panel</a>";

   }	

	public static function goAway(){

		$host  = $_SERVER['HTTP_HOST'];
		$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra = 'index.php';
		header("Location: http://$host$uri/$extra");
		   exit();


	}
	public function showUsersAndGroups(){

		$users = array();

		$this->conn->_startTransaction();
		// $sql = "SELECT u.name as uname, u.ID as uid, g.ID as gid, g.name as gname, g.description FROM users AS u, enrolment as e, groups as g WHERE u.ID = e.fk_user_id and e.fk_group_id = g.ID";
		$sql = "SELECT u.username as uname, u.ID as uid FROM user AS u";
		$result = $this->conn->_multipleSelect($sql);
		foreach($result as $key=>$user){
			array_push($users, $user);
		}
		echo "<form name='asignargrupo' action='admin.php' method='post'>";
		echo "Select user:";
		echo "<select name='user'>";
		foreach($users as $user){
			echo "<option value='$user->uid'>$user->uname</option>";
		}
		echo "</select><p>";

		echo "Select the group to assign user to:<br>";
		echo "Grupo A = Aintzane, B = Begoña, C = Begoña, D= Christian";
		echo "<select name='group'>";
		echo "<option value='1'>A</option>";
		echo "<option value='2'>B</option>";
		echo "<option value='3'>C</option>";
		echo "<option value='4'>D</option>";
		echo "</select><p>";

		echo "Rol: "; 
		echo "<select name='role'>";
		echo "<option value='student'>Student</option>";
		echo "<option value='teacher'>Teacher</option>";
		echo "</select><p>";

		echo "<input type='hidden' name='action' value='assign'>";
		echo "<input type='Submit' value='Save'>";
		echo "</form>";
		echo "<p><a href='admin.php'>Volver</a>";
	}

}

if(php_sapi_name() != 'cli' && !empty($_SERVER['REMOTE_ADDR'])) {
	$userData = $_SESSION['user-data'];
        // error_log(print_r($_SESSION,1), 3, "/tmp/error.log");
	if (!$userData->isAdmin)
		Admin::goAway();

	$admin = new Admin();
	if (empty($_POST) && empty($_GET)){
		$admin->showOptions();

	} else if (empty($_POST)){ // there is something in GET

		switch ($_GET['action']){
			case 'add':
				$admin->showAddUser(); 
				break;
			case 'assign':
				$admin->showUsersAndGroups(); 
				break;
			case 'impersonate':
				$admin->showImpersonate(); 
				break;
			case 'statistics':
				$admin->showStatistics();
				break;
			case 'reloadResponsesCache':
				$admin->reloadResponsesCache();
				break;		
			case 'showMyGroups':
				$admin->showMyGroups();
				break;		
        	case 'showNewGroup':
				$admin->showNewGroup();
				break;		
        	case 'changeRole':
				$admin->assignUserToGroup($_GET['user'],$_GET['group'], $_GET['role']);
                echo "<form name='showAddUserForm' action='admin.php' method='post'>";
                echo "<input type='hidden' name='group' value=".$_GET['group'].">";
                echo "<input type='hidden' name='action' value='showAddGroupMembers'>";
                echo "<input type='Submit' value='Show Users of this group'>";
                echo "</form>";
                echo "<p><a href='admin.php'>Back to Administration Panel</a>";
                break;		
			default:
				echo "mmmh... this shouldn't happen";	
		}	

	} else { // there is something in POST

		switch($_POST['action']){
			case 'assign':
				$admin->assignUserToGroup($_POST['user'], $_POST['group'], $_POST['role'] );
				echo "Assignment completed. <a href='admin.php'>Main menu</a>";
				break;
            case 'assignUsersToGroup':
                $admin->assignUsersToGroup($_POST['newMembers'], $_POST['group']);
                break;
			case 'add':
				$admin->addUser($_POST['login'], $_POST['dni'], $_POST['email'], $_POST['realName'],$_POST['realSurname'] );
				echo "User has been correctly added. <a href='admin.php'>Main menu</a>";
				break; 
			case 'impersonate':
				$name = $admin->impersonate($_POST['user']);
				echo "Now, your are working as $name <a href='admin.php'>Main menu</a>";
				break; 
            case 'showAddGroupMembers':
                $admin->showAddGroupMembers($_POST['group']);
                break;
	        case 'addNewGroup':
                $admin->addNewGroup($_POST['name'], $_POST['description']);
                break;		
            case 'reloadResponsesCache':
				$admin->reloadResponsesCache();	
				break;
			default:
				echo "mmmhh... this shouldn't happen";
		}
	}

} // end if (we are running this on the webserver, not on cli)
?>
