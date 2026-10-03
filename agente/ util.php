<?php
// util.php

/**
 * Normaliza a pergunta: minúsculas, sem acento, sem pontuação extra.
 * "Qual foi minha PRODUÇÃO de leite??" → "qual foi minha producao de leite"
 */
function normalizar(string $t): string {
    $t = mb_strtolower(trim($t), 'UTF-8');

    // remove acentos
    $t = strtr($t, [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c','ñ'=>'n',
    ]);

    // troca pontuação por espaço e colapsa espaços
    $t = preg_replace('/[^\w\s]/u', ' ', $t);
    $t = preg_replace('/\s+/', ' ', $t);
    return trim($t);
}

/**
 * Converte números escritos em texto para dígito (até 60, suficiente para "dias").
 */
function numero_em_texto(string $t): string {
    $map = [
        'um'=>1,'dois'=>2,'tres'=>3,'quatro'=>4,'cinco'=>5,
        'seis'=>6,'sete'=>7,'oito'=>8,'nove'=>9,'dez'=>10,
        'quinze'=>15,'vinte'=>20,'trinta'=>30,'sessenta'=>60,
    ];
    foreach ($map as $palavra => $n) {
        $t = preg_replace('/\b' . $palavra . '\b/u', (string)$n, $t);
    }
    return $t;
}

/**
 * Datas amigáveis em português.
 */
function data_br(string $ymd): string {
    $d = DateTime::createFromFormat('Y-m-d', $ymd);
    return $d ? $d->format('d/m/Y') : $ymd;
}

function data_hora_br(string $ymdhm): string {
    $d = new DateTime($ymdhm);
    return $d->format('d/m/Y \à\s H\hi');
}

/**
 * Formata número em litros com separador brasileiro.
 */
function litros(float $n): string {
    return number_format($n, 0, ',', '.') . ' L';
}