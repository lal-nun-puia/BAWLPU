<?php
require_once 'db.php';
session_start();

// If already logged in, redirect to index
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$message = '';

function passwordMatchesInput($input, $stored) {
    if (!$stored) return false;
    if (password_verify($input, $stored)) return true;
    if (strlen($stored) === 32 && ctype_xdigit($stored) && md5($input) === strtolower($stored)) return true;
    return $stored === $input;
}

function ensureAdminUserExists($pdo, $email, $password) {
    $adminStmt = $pdo->prepare("SELECT username, email, password FROM admin WHERE email = ?");
    $adminStmt->execute([$email]);
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin || !passwordMatchesInput($password, $admin['password'])) {
        return null;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $userStmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $userStmt->execute([$email]);
    $existing = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $pdo->prepare("UPDATE users SET password = ?, role = 'Admin' WHERE id = ?")->execute([$hash, $existing['id']]);
    } else {
        $name = $admin['username'] ?: 'Admin';
        $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'Admin')")->execute([$name, $email, $hash]);
    }

    $userStmt->execute([$email]);
    return $userStmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate email: must be valid and end with @gmail.com
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/', $email)) {
        $message = "Only Gmail addresses (@gmail.com) are allowed.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $passwordOk = $user && passwordMatchesInput($password, $user['password']);
        if (!$user || !$passwordOk) {
            $user = ensureAdminUserExists($pdo, $email, $password);
            $passwordOk = $user !== null;
        }

        if ($user && $passwordOk) {
            if (!password_verify($password, $user['password'])) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                $user['password'] = $newHash;
            }

            if ($user['role'] === 'Nurse' && ($user['approval_status'] ?? 'Pending') !== 'Approved') {
                $message = $user['approval_status'] === 'Rejected'
                    ? "Your nurse application was rejected. Please contact support for assistance."
                    : "Your nurse profile is pending admin approval. You'll be able to access the portal once approved.";
            } else {
                $_SESSION['user'] = [
                    'user_id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];

                if ($user['role'] === 'Admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            }
        } else {
            $message = $user ? "Incorrect password!" : "No account found with this email!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Sign up Form with Flip effect</title>
    <link rel="stylesheet" href="https://public.codepenassets.com/css/normalize-5.0.0.min.css">
    <link rel="stylesheet" href="./style.css">
    <script src="assets/theme.js"></script>
  </head>
  <body>
<button id="themeToggle" type="button" style="position:fixed;top:16px;right:16px;z-index:1000;" class="btn btn-outline">Toggle Theme</button>
<div id="container">
        <div class="login">
            <div class="content">
                    <h1>Log In</h1>
                <?php if($message) echo "<p class='message'>".htmlspecialchars($message)."</p>"; ?>
                <form method="POST" autocomplete="off">
                    <input type="email" name="email" placeholder="email" required autocomplete="username">
                    <input type="password" id="login-password" name="password" placeholder="password" required autocomplete="current-password">
                    <label class="show-pass-row">
                        <input type="checkbox" id="showLoginPassword">
                        <span>Show password</span>
                    </label>
                    <a class="forgot-password-link" href="forgot_password.php">Forgot password?</a>
                    <button type="submit" name="login">Log In</button>
                </form>
                    <span class="loginwith">Or Connect with</span>
                <a href="#"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                    <a href="#"><svg class="feather feather-twitter sc-dnqmqq jxshSx" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg></a>
                    <a href="#"><svg class="feather feather-github sc-dnqmqq jxshSx" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg></a>
                <a href="#">	<svg class="feather feather-linkedin sc-dnqmqq jxshSx" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg></a>
                <span class="copy">&copy; <?php echo date('Y'); ?></span>
            </div>
        </div>
        <div class="page front">
            <div class="content">
                 <svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user-plus"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    <h1>Hello, friend!</h1>
                    <p>Enter your personal details and start journey with us</p>
                    <button type="button" id="register">Register <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right-circle"><circle cx="12" cy="12" r="10"/><polyline points="12 16 16 12 12 8"/><line x1="8" y1="12" x2="16" y2="12"/></svg></button>
            </div>
        </div>
        <div class="page back">
                <div class="content">
                    <svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-log-in"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us please login with your personal info</p>
                    <button type="button" id="login"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left-circle"><circle cx="12" cy="12" r="10"/><polyline points="12 8 8 12 12 16"/><line x1="16" y1="12" x2="8" y2="12"/></svg> Log In</button>
            </div>
        </div>
        <div class="register">
            <div class="content">
                    <h1>Sign Up</h1>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    <svg class="feather feather-twitter sc-dnqmqq jxshSx" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
                    <svg class="feather feather-github sc-dnqmqq jxshSx" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                    <svg class="feather feather-linkedin sc-dnqmqq jxshSx" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                
                                    <span class="loginwith">Or</span>

                    <a href="register.php" class="btn btn-outline" style="display:block;text-align:center;margin-top:10px;">Register</a>
            </div>        
        </div>
</div>

    <script  src="./script.js"></script>
    <script>
      (function(){
        var toggle = document.getElementById('showLoginPassword');
        var input = document.getElementById('login-password');
        if(toggle && input){
          toggle.addEventListener('change', function(){
            input.type = this.checked ? 'text' : 'password';
          });
        }
      })();
    </script>


  </body>

</html>
