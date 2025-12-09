<?php
// create_database.php — Create the MySQL database without manual SQL typing
// Usage (CMD):
//   cd C:\xampp\htdocs\nurse
//   php -f create_database.php -- dbname=nurse_portal user=root pass=

$defaults = [
  'host' => 'localhost',
  'dbname' => 'nurse_portal',
  'user' => 'root',
  'pass' => '',
];

// Parse key=value args from CLI
$args = $defaults;
foreach (array_slice($argv ?? [], 1) as $arg) {
  if (strpos($arg, '=') !== false) {
    [$k,$v] = explode('=', $arg, 2);
    $k = strtolower(trim($k));
    if (isset($args[$k])) { $args[$k] = $v; }
  }
}

$host = $args['host'];
$dbname = $args['dbname'];
$user = $args['user'];
$pass = $args['pass'];

try {
  $pdo = new PDO("mysql:host=$host", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
  $pdo->exec($sql);
  echo "Database '$dbname' ready.".PHP_EOL;
} catch (Exception $e) {
  echo "Error creating database: ".$e->getMessage().PHP_EOL;
  exit(1);
}
?>

