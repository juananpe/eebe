<?

   session_start();

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'services/admin.php';
header("Location: http://$host$uri/$extra");
   exit();


