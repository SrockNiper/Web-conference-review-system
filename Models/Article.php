<?php

/**
 * Datová třída pro reprezentaci článku
 *
 * Obsahuje vlastnosti odpovídající sloupcům v databázové tabulce articles
 * a doplňkové informace o autorovi.
 */
class Article {
    public $id;
    public $title;
    public $abstract;
    public $content;
    public $pdf_filename;
    public $author_id;
    public $status;
    public $created_at;
    public $updated_at;
    public $name;
    public $surname;


}
?>