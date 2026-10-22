<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$additionalCSS = ['CSSs/register.css'];
$data['activePage'] = 'register';
$data['title'] = $data['title'] ?? 'Registrace';

require_once 'views/header.php';
?>

<main class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow">
                        <div class="card-body">
                            <h2 class="card-title text-center mb-4">Registrace</h2>

                            <?php if (!empty($data['message'])): ?>
                                <div class="alert alert-<?php echo $data['message_type']; ?>" role="alert">
                                    <?php echo $data['message']; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">Jméno <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="first_name" name="first_name"
                                                   value="<?php echo htmlspecialchars($data['old_data']['first_name'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Příjmení <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="last_name" name="last_name"
                                                   value="<?php echo htmlspecialchars($data['old_data']['last_name'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">Uživatelské jméno <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username"
                                           value="<?php echo htmlspecialchars($data['old_data']['username'] ?? ''); ?>" required>
                                    <div class="form-text">Minimálně 3 znaky</div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($data['old_data']['email'] ?? ''); ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Heslo <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <div class="form-text">Minimálně 6 znaků</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Potvrdit heslo <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success w-100 mb-3">Registrovat se</button>
                            </form>

                            <div class="text-center">
                                <p class="text-muted mb-3">Už máte účet?</p>
                                <a href="index.php?path=login" class="btn btn-outline-primary w-100">
                                    Přihlásit se
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php require_once 'views/footer.php'; ?>