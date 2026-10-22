<?php

// Spuštění session pro správu uživatelů
session_start();
require_once __DIR__ . '/config/constants.php';
// Autoload funkcionalita
spl_autoload_register(function($class) {
    $paths = [
        'Controlers/',  // Opravená cesta
        'Models/',
        'Views/'
    ];

    foreach($paths as $path) {
        $file = __DIR__ . '/' . $path . $class . '.php';
        if(file_exists($file)) {
            require_once $file;
            break;
        }
    }
});

// Načtení routing třídy
require_once 'Models/Routing.php';

// Inicializace routingu
$routing = new Routing();
$routing->handleRequest();

?>
