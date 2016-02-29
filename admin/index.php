<?

   session_start();

// hard-coded for testing purposes

   $obj = new stdClass;
   $obj->isAdmin = 1;
   $_SESSION['user-data'] = $obj;
   $_SESSION['uid'] = 243532;

   header("Location: http://localhost/so/services/admin.php");
   exit();


