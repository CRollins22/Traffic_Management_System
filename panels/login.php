<?php
// OWNER: Anthony Kariuki | BR-03 login page (password + MFA code)
function panel_login_handle(): void
{
    $ok = Security::login(trim($_POST['user'] ?? ''), $_POST['password'] ?? '', trim($_POST['mfa'] ?? ''));
    if ($ok) { $_SESSION['user'] = $_POST['user']; flash("Welcome, operator."); }
    else     { flash("Login failed. Check username, password and MFA code.", "err"); }
}

function panel_login_render(): void { ?>
    <section class="card login">
        <h2>Operator login</h2>
        <form method="post">
            <input type="hidden" name="action" value="login">
            <label>Username <input name="user" required autofocus></label>
            <label>Password <input name="password" type="password" required></label>
            <label>MFA code <input name="mfa" inputmode="numeric" required></label>
            <button>Sign in</button>
        </form>
        <p class="hint">Demo login: operator / traffic123 / MFA 123456</p>
    </section>
<?php }
