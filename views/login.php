<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$additionalCSS = ['CSSs/login.css'];
$data['activePage'] = 'login';
$data['title'] = $data['title'] ?? 'Přihlášení';

require_once 'views/header.php';
?>

<main class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow">
                        <div class="card-body">
                            <h2 class="card-title text-center mb-4">Přihlášení</h2>

                            <?php if (!empty($data['message'])): ?>
                                <div class="alert alert-<?php echo $data['message_type']; ?>" role="alert">
                                    <?php echo $data['message']; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="loginForm">
                                <input type="hidden" name="action" value="login">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Uživatelské jméno</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Heslo</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mb-3">Přihlásit se</button>
                            </form>

                            <div class="text-center">
                                <p class="text-muted mb-3">Nemáte účet?</p>
                                <a href="index.php?path=register" class="btn btn-success w-100">
                                    Registrovat se
                                </a>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php require_once 'views/footer.php'; ?>