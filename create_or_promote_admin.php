<?php
// create_or_promote_admin.php — Create an Admin user or promote an existing email to Admin
// Usage (CMD examples):
//   php -f create_or_promote_admin.php -- email=admin@example.com name="Admin User" pass=Admin@123
//   php -f create_or_promote_admin.php -- email=admin@example.com pass=Admin@123 (name optional if updating)

require_once 'db.php';

// Parse key=value args
$params = ['email'=>'','name'=>'','pass'=>'','phone'=>'0000000000','address'=>'Admin HQ'];
foreach (array_slice($argv ?? [], 1) as $arg) {
  if (strpos($arg, '=') !== false) { [$k,$v] = explode('=', $arg, 2); $k=strtolower(trim($k)); if(isset($params[$k])) $params[$k]=$v; }
}

if (!$params['email'] || !$params['pass']) {
  echo "Usage: php -f create_or_promote_admin.php -- email=you@example.com name=YourName pass=YourPass".PHP_EOL;
  exit(1);
}

try {
  // Check if user exists
  $check = $pdo->prepare('SELECT id,name,role FROM users WHERE email=?');
  $check->execute([$params['email']]);
  $user = $check->fetch(PDO::FETCH_ASSOC);

  if ($user) {
    // Promote to Admin and optionally update password
    $updates = [];
    $args = [];
    if ($params['name']) { $updates[] = 'name=?'; $args[] = $params['name']; }
    if ($params['pass']) { $updates[] = 'password=?'; $args[] = password_hash($params['pass'], PASSWORD_DEFAULT); }
    $updates[] = 'role=\'Admin\''; // forced
    $sql = 'UPDATE users SET '.implode(',', $updates).' WHERE id=?';
    $args[] = $user['id'];
    $pdo->prepare($sql)->execute($args);
    echo "Updated user and set role=Admin for ".$params['email'].PHP_EOL;
  } else {
    // Insert new Admin
    $hash = password_hash($params['pass'], PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO users (name,email,password,phone,address,role) VALUES (?,?,?,?,?,\'Admin\')');
    $name = $params['name'] ?: 'Admin';
    $ins->execute([$name,$params['email'],$hash,$params['phone'],$params['address']]);
    echo "Created Admin user ".$params['email'].PHP_EOL;
  }
} catch (Exception $e) {
  echo 'Error: '.$e->getMessage().PHP_EOL;
  exit(1);
}
?>

