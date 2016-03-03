<?

require_once 'utils/Config.php';
require_once 'utils/Datasource.php';
session_start();

// print_r($_SESSION);
class Login {

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



	/**
	 *   Returns: uid of existing teacher in info table (or 0)
	 */
	public function check($login, $pass){
		$sql = "SELECT user_id FROM info where user_name='%s' and password=MD5('%s')";
		$row = $this->conn->_singleSelect($sql, $login, $pass);
		// error_log(print_r($row, 1), 3, "/tmp/error.log");
		return $row->user_id;

	}

	public function logout(){
		unset($_SESSION['uid']);
		session_destroy();

	}

	public function go($nora){
		$uneko_zerbitzaria  = $_SERVER['HTTP_HOST'];
		$uneko_karpeta   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		header("Location: http://$uneko_zerbitzaria$uneko_karpeta/$nora");
		exit;
	}


	public function prepare($uid){
			$obj = new stdClass;
			$obj->isAdmin = 1;
			$_SESSION['user-data'] = $obj;
			$_SESSION['uid'] = $uid;
	}

} // end Login class

$error="";

$page = new Login();

if (isset($_GET['action']) && $_GET['action']=='logout'){
	$page->logout();
}
if (isset($_SESSION['uid'])){
	$page->go("admin.php");
}else{
	if (isset($_POST['login']) && isset($_POST['password'])){
		if($uid = $page->check($_POST['login'], $_POST['password'])){
				$page->prepare($uid);
				$page->go("admin.php");
		}else{
			$error = "Incorrect login or password";
		}
	}
}
?>
<!DOCTYPE html>
<html lang="eus">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="../../favicon.ico">
<title> External Evidence Based Evaluation - Login </title>
<!-- Bootstrap core CSS -->
<link href="dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Custom styles for this template -->
<link href="signin.css" rel="stylesheet">
</head>

<body>
<div class="container">

<form class="form-signin"  action="?action=kautotu" method="POST">
<h2 class="form-signin-heading">Log in</h2>
<label for="login" class="sr-only">Login</label>
<input type="text" id="inputEmail" class="form-control" placeholder="Login" required autofocus name="login">
<label for="inputPassword" class="sr-only">Password</label>
<input type="password" id="inputPassword" class="form-control" placeholder="Password" required name="password">
<? if ($error!='') { ?><div class="alert alert-danger" role="alert"><label><?=$error?></label></div> <? } ?>
<button class="btn btn-lg btn-primary btn-block" type="submit">Log in</button>
</form>

</div> <!-- /container -->

</body>
</html>

