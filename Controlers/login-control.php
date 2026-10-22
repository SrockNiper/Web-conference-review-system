<?php

class LoginController {

    private $userModel;

    public function __construct() {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }


        require_once 'Models/Database.php';
        require_once 'Models/UserModel.php';
        $this->userModel = new UserModel();
    }

    public function index() {

        if(isset($_SESSION['user']) && !empty($_SESSION['user'])) {
            header('Location: index.php');
            exit;
        }


        $data = [
            'title' => 'Přihlášení',
            'message' => '',
            'message_type' => 'danger'
        ];


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->handleLogin();
            if ($result['success']) {

                $_SESSION['flash'] = [
                    'message' => 'Úspěšně jste se přihlásili!',
                    'type' => 'success'
                ];
                header('Location: index.php');
                exit;
            } else {
                $data['message'] = $result['message'];
                $data['message_type'] = $result['type'];
            }
        }


        require_once 'Views/login.php';
    }

    public function logout() {

        $_SESSION['flash'] = [
            'message' => 'Úspěšně jste se odhlásili.',
            'type' => 'info'
        ];


        $flash = $_SESSION['flash'];
        session_destroy();
        session_start();
        $_SESSION['flash'] = $flash;

        header('Location: index.php');
        exit;
    }

    private function handleLogin() {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if(empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Všechna pole jsou povinná.',
                'type' => 'danger'
            ];
        }


        $user = $this->userModel->verifyCredentials($username, $password);

        if ($user) {

            $_SESSION['user'] = $user;
            return [
                'success' => true,
                'message' => 'Přihlášení bylo úspěšné.',
                'type' => 'success'
            ];
        }



        return [
            'success' => false,
            'message' => 'Neplatné přihlašovací údaje.',
            'type' => 'danger'
        ];
    }
}
?>
