<?php
// OWNER: Anthony Kariuki | BR-03 login page: pre-registered operators sign in with a hashed password

/** Checks username + password and signs the operator in. */
function panel_login_handle(): void
{
    $user = UserRepository::find(trim($_POST['user'] ?? ''));
    $ok = $user && $user['role'] === 'operator'                         // only operators can open the dashboard
          && Security::verifyPassword($_POST['password'] ?? '', $user['password_hash']);
    if (!$ok) { flash("Invalid username or password.", "err"); return; }   // same message for every failure, so attackers learn nothing

    session_regenerate_id(true);          // new session id after sign-in blocks session fixation
    $_SESSION['user'] = $user['username'];
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = 'operator';
    flash("Welcome, {$user['username']}.");
}

function panel_login_render(): void { ?>
    <section class="card login">
        <h2>Operator login</h2>
        <form method="post">
            <input type="hidden" name="action" value="login">
            <label>Username <input name="user" required autofocus></label>
            <label>Password <input name="password" type="password" required></label>
            <button>Sign in</button>
        </form>
    </section>
<?php }