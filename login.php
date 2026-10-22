<?php
require __DIR__.'/includes/config.php'; require __DIR__.'/includes/functions.php';
verify_csrf(); $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email=strtolower(trim($_POST['email']??'')); $password=$_POST['password']??'';
    $st=$pdo->prepare('SELECT id,full_name,email,password_hash,role,phone FROM users WHERE email=? AND is_active=1 LIMIT 1'); $st->execute([$email]); $u=$st->fetch();
    if($u && password_verify($password,$u['password_hash'])) { session_regenerate_id(true); $_SESSION['user']=['id'=>(int)$u['id'],'full_name'=>$u['full_name'],'email'=>$u['email'],'role'=>$u['role'],'phone'=>$u['phone']]; if($u['role']==='owner') redirect('owner/index.php'); if($u['role']==='operator') redirect('operator/index.php'); redirect('provider/index.php'); }
    $error='Email or password was not recognised.';
}
$pageTitle='Sign in'; require __DIR__.'/includes/header.php'; ?>
<div class="login-page"><div class="hero"><div><div class="eyebrow">YOUR TOWNSHIP, CONNECTED TO HELP</div><h2>Welcome to <span style="color:var(--sd-red)">Sime Darby Care</span></h2><p>Report an issue, coordinate assistance and track updates from Elmina Township.</p></div><div class="hero-art">🛡️</div></div>
<div class="card" style="margin-top:16px"><h3>Sign in to your account</h3><?php if($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="field"><label>Email address</label><input type="email" name="email" required autocomplete="username" placeholder="you@example.com"></div><div class="field"><label>Password</label><input type="password" name="password" required autocomplete="current-password" placeholder="Enter password"></div><button class="btn btn-primary btn-block">Sign in</button></form><p class="muted" style="font-size:12px;text-align:center">New premise owner? <a class="red" href="<?= BASE_URL ?>/register.php"><strong>Create an account</strong></a></p></div>
<?php require __DIR__.'/includes/footer.php'; ?>
