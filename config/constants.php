<?php

// Základní informace o webu
define('SITE_NAME', 'KIV/WEB Conference 2025');
define('SITE_DESCRIPTION', 'Mezinárodní konference o webových technologiích');
define('CONFERENCE_DATE', '15. prosinec 2025');
define('CONFERENCE_LOCATION', 'Plzeň, Univerzita Západních Čech');

// Meta informace
define('SITE_KEYWORDS', 'konference, webové technologie, programování, KIV, ZČU');
define('SITE_AUTHOR', 'KIV/WEB Team');

// Navigační položky
define('NAV_ITEMS', [
    'Úvod' => 'index.php',
    'Program' => 'index.php?path=program',
    'Řečníci' => 'index.php?path=speakers',
    'Registrace' => 'index.php?path=registration',
    'Kontakt' => 'index.php?path=contact'
]);

// Cesty k souborům
define('CSS_PATH', 'CSSs/');
define('JS_PATH', 'js/');
define('IMAGES_PATH', 'images/');

// Databázové konstanty (pro budoucí rozšíření)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'kiv/web');
define('DB_CHARSET', 'utf8mb4');
define('DB_USER', 'root');      // přidejte
define('DB_PASS', '');
?>

