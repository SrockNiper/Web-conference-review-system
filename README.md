# Konferenční a recenzní systém pro odborné publikace (PHP MVC)

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![Architecture](https://img.shields.io/badge/Architecture-Custom%20MVC-blue?style=flat)]()
[![Bootstrap](https://img.shields.io/badge/Frontend-Bootstrap%205-7952B3?style=flat&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Semestrální práce z předmětu **KIV/WEB (Vývoj webových aplikací)** na **Fakultě aplikovaných věd ZČU v Plzni**.

Webová aplikace implementuje kompletní redakční a recenzní systém pro vědecké konference postavený na **čisté MVC architektuře (Model-View-Controller)** v čistém PHP bez použití externích těžkopádných frameworků.

---

## 🌟 Hlavní funkcionality

- **Vlastní MVC architektura:**
  - `Controlers/`: Zpracování HTTP požadavků, routování akcí a validace vstupů.
  - `Models/`: Zapouzdření databázových operací (PDO s Prepared Statements) a aplikační logiky.
  - `views/`: Oddělené šablony a komponenty grafického rozhraní.
- **Řízení přístupových práv (RBAC):**
  - **Návštěvník:** Prohlížení programu konference, informací a přehledu schválených článků.
  - **Autor:** Registrace, přihlášení, správa vlastních příspěvků a nahrávání článků ve formátu PDF.
  - **Recenzent:** Hodnocení přidělených článků ve strukturovaném recenzním formuláři (bodová kritéria + textové posudky).
  - **Administrátor:** Správa uživatelů a rolí, přidělování článků recenzentům, finální rozhodnutí o přijetí či zamítnutí příspěvku.
- **Bezpečnost:**
  - Ochrana proti SQL Injection pomocí parametrizovaných dotazů (PDO).
  - Bezpečné ukládání hesel pomocí silného kryptografického hashování (`password_hash` / `password_verify`).
  - Ošetření výstupů proti XSS (Cross-Site Scripting).

---

## 📁 Struktura projektu

```text
├── config/
│   └── constants.php         # Globální konfigurace webu a DB připojení
├── Controlers/
│   ├── archive-control.php   # Archiv proběhlých ročníků
│   ├── article-control.php   # Zobrazení detailu článku
│   ├── article-maker-control.php # Formulář pro odeslání nového článku
│   ├── login-control.php     # Autentizace uživatele
│   ├── manage-recenzents-control.php # Administrace přiřazení recenzentů
│   ├── manage-users-control.php      # Správa uživatelských účtů a rolí
│   ├── recenzents-control.php        # Rozhraní pro recenzenty
│   ├── register-control.php  # Registrace nových autorů
│   └── review-control.php    # Zadání recenzního posudku
├── Models/
│   ├── ArticleModel.php      # Práce s příspěvky a jejich stavy
│   ├── Database.php          # PDO databázový singleton
│   ├── ReviewModel.php       # Správa recenzí a hodnocení
│   └── UserModel.php         # Uživatelské účty a ověřování hesel
├── views/                    # Šablony stránek (Bootstrap 5)
├── csss/                     # Kaskádové styly a vlastní úpravy
└── index.php                 # Centrální vstupní bod (Front Controller) a autoloader
```

---

## 🚀 Lokální spuštění

### Požadavky:
- PHP 8.0+
- Webový server (Apache / Nginx / vestavěný PHP server)
- Databáze MySQL nebo MariaDB

### Nastavení připojení:
Upravte parametry připojení k vaší databázi v souboru [`config/constants.php`](config/constants.php):
```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'nazev_databaze');
define('DB_USER', 'uzivatel');
define('DB_PASS', 'heslo');
```

Spuštění pomocí vestavěného PHP serveru:
```bash
php -S localhost:8000
```
Aplikace je následně dostupná v prohlížeči na adrese `http://localhost:8000`.

---

## 👤 Autor

- **Marek Jirušek** (ZČU FAV, Katedra informatiky a výpočetní techniky)
