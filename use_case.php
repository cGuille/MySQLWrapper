<?php
require_once 'MySQL_Wrapper.class.php';

// Connexion :
try {
    // D'autres paramètres peuvent être donnés, voir la description du constructeur dans
    // le fichier MySQL_Wrapper.class.php.
    $mysql = new MySQL_Wrapper('database');
} catch (MySQLException $e) {
    // var_dump($e);
    trigger_error($e->getMessage(), E_USER_ERROR);
}

// -------------------------------------------------------------------------------------------

// Un exemple de sélection :
try {
    $date_format = '%W %d %M %Y à %Hh%i';
    $nb_max_billets = 10;
    $billets = $mysql->query('SELECT `id`, `auteur`, `titre`, `contenu`, DATE_FORMAT(`date_ajout`, :str) AS date_fr
                              FROM `billets`
                              ORDER BY `id` DESC
                              LIMIT :int',
                              $date_format,
                              $nb_max_billets);
} catch (MySQLException $e) {
    // var_dump($e);
    trigger_error($e->getMessage(), E_USER_ERROR);
}
// var_dump($billets);

foreach ($billets as $billet) {
    // …
}

// -------------------------------------------------------------------------------------------

// Un exemple d'insertion :
if (empty($_POST['pseudo']) OR empty($_POST['titre']) OR empty($_POST['contenu'])) {
    header('location: form.php?error=missing_field');
    exit();
}

try {
    $inserted_rows  = $mysql->query('INSERT INTO `billets`(`auteur`, `titre`, `contenu`, `date_ajout`)
                                     VALUES(:str, :str, :str, NOW())';
                                     $_POST['pseudo'],
                                     $_POST['titre'],
                                     $_POST['contenu']);
    if ($inserted_rows) {
        header('location: form.php?status=success');
        exit();
    } else {
        header('location: form.php?error=no_insert');
        exit();
    }
} catch (MySQLException $e) {
    // var_dump($e);
    trigger_error($e->getMessage(), E_USER_ERROR);
}
