<?php

class RegisterController {

    public function index() {

        if(isset($_SESSION['user']) && !empty($_SESSION['user'])) {
            header('Location: index.php');
            exit;
        }

        $data = [
            'title' => 'Registrace',
            'message' => '',
            'message_type' => 'danger',
            'old_data' => []
        ];

        // Kontrola POST requestu
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->handleRegister();
            $data['message'] = $result['message'];
            $data['message_type'] = $result['type'];
            $data['old_data'] = $result['old_data'] ?? [];
        }


        require_once 'Views/register.php';
    }

    private function handleRegister() {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Uchování dat pro případ erroru
        $old_data = [
            'username' => $username,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name
        ];

        // Základní validace
        if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($password) || empty($confirm_password)) {
            return ['message' => 'Všechna povinná pole musí být vyplněna.', 'type' => 'danger', 'old_data' => $old_data];
        }

        if (strlen($username) < 3) {
            return ['message' => 'Uživatelské jméno musí mít alespoň 3 znaky.', 'type' => 'danger', 'old_data' => $old_data];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['message' => 'Neplatný formát emailové adresy.', 'type' => 'danger', 'old_data' => $old_data];
        }

        if (strlen($password) < 6) {
            return ['message' => 'Heslo musí mít alespoň 6 znaků.', 'type' => 'danger', 'old_data' => $old_data];
        }

        if ($password !== $confirm_password) {
            return ['message' => 'Hesla se neshodují.', 'type' => 'danger', 'old_data' => $old_data];
        }

        // Kontrola v databázi
        $userModel = new UserModel();

        if ($userModel->getByUsername($username)) {
            return ['message' => 'Uživatelské jméno je již obsazené.', 'type' => 'danger', 'old_data' => $old_data];
        }

        if ($userModel->getByEmail($email)) {
            return ['message' => 'Email je již registrován.', 'type' => 'danger', 'old_data' => $old_data];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Příprava dat pro vložení
        $insertData = [
            'username' => $username,
            'email' => $email,
            'password' => $passwordHash,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 1,
        ];
        $newId = $userModel->createUser($insertData);

        if ($newId === false) {
            return ['message' => 'Registrace selhala, zkuste to prosím znovu.', 'type' => 'danger', 'old_data' => $old_data];
        }


        $newUser = $userModel->getById($newId);
        if ($newUser) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['user'] = $newUser;
            $_SESSION['flash'] = ['message' => 'Registrace proběhla úspěšně. Přihlášen.', 'type' => 'success'];
            header('Location: index.php');
            exit;
        }

        return ['message' => 'Registrace dokončena, ale nepodařilo se načíst uživatele.', 'type' => 'warning', 'old_data' => []];
    }
}
?>
