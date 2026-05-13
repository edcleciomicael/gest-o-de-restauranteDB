<?php

require_once 'config/database.php';

function db()
{
    return Database::connect();
}

function verificarSessao()
{
    if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

    if (!isset($_SESSION['user'])) {

        header(
            'Location: login.php'
        );

        exit;
    }
}

function formatarMoeda($valor)
{
    return 'R$ ' .
    number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}

function formatarData($data)
{
    if (!$data) {
        return '';
    }

    return date(
        'd/m/Y',
        strtotime($data)
    );
}

function formatarDataHora($data)
{
    if (!$data) {
        return '';
    }

    return date(
        'd/m/Y H:i',
        strtotime($data)
    );
}
?>

