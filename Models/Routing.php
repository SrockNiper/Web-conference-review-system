<?php

/**
 * Třída pro správu routování aplikace
 *
 * Mapuje URL cesty na kontrolery a jejich metody,
 * načítá příslušné soubory a volá odpovídající akce.
 */
class Routing {

    private $routes = [];

    /**
     * Konstruktor - inicializuje výchozí routy aplikace
     */
    public function __construct() {
        $this->addRoute('', 'UvodController', 'index');
        $this->addRoute('uvod', 'UvodController', 'index');
        $this->addRoute('index', 'UvodController', 'index');
        $this->addRoute('login', 'LoginController', 'index');
        $this->addRoute('logout', 'LoginController', 'logout');
        $this->addRoute('register', 'RegisterController', 'index');
        $this->addRoute('article', 'ArticleController', 'index');
        $this->addRoute('manage-users', 'ManageUsersController', 'index');
        $this->addRoute('article-maker', 'ArticleMakerController', 'index');
        $this->addRoute('view-pdf', 'ArticleController', 'viewPdf');
        $this->addRoute('recenzents', 'RecenzentsController', 'index');
        $this->addRoute('manage-recenzents', 'ManageRecenzentsController', 'index');
        $this->addRoute('review', 'ReviewController', 'index');
        $this->addRoute('archive', 'ArchiveController', 'index');
    }

    /**
     * Přidá novou routu do systému
     *
     * @param string $path URL cesta
     * @param string $controller Název třídy kontroleru
     * @param string $method Název metody k zavolání
     * @return void
     */
    public function addRoute($path, $controller, $method) {
        $this->routes[$path] = [
            'controller' => $controller,
            'method' => $method
        ];
    }

    /**
     * Zpracuje HTTP požadavek a zavolá příslušný kontroler
     *
     * @return void
     */
    public function handleRequest() {
        // Získání cesty z URL
        $path = $this->getPath();

        // Kontrola, zda existuje route pro tuto cestu
        if(isset($this->routes[$path])) {
            $route = $this->routes[$path];
            $controllerName = $route['controller'];
            $methodName = $route['method'];

            $base = preg_replace('/Controller$/', '', $controllerName);
            $kebab = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $base));
            $controllerFile = 'Controlers/' . $kebab . '-control.php';

            if(file_exists($controllerFile)) {
                require_once $controllerFile;

                if(class_exists($controllerName)) {
                    $controller = new $controllerName();
                    if(method_exists($controller, $methodName)) {
                        $controller->$methodName();
                    } else {
                        $this->show404();
                    }
                } else {
                    $this->show404();
                }
            } else {
                $this->show404();
            }
        } else {
            $this->show404();
        }
    }

    /**
     * Získá cestu z URL parametru
     *
     * @return string URL cesta bez lomítek na začátku a konci
     */
    private function getPath() {
        $path = isset($_GET['path']) ? $_GET['path'] : '';
        $path = trim($path, '/');
        return $path;
    }

    /**
     * Zobrazí 404 chybovou stránku
     *
     * @return void
     */
    private function show404() {
        http_response_code(404);
        echo '<h1>404 - Stránka nenalezena</h1>';
        echo '<p>Požadovaná stránka neexistuje.</p>';
        echo '<a href="index.php">Zpět na úvod</a>';
    }
}

?>
