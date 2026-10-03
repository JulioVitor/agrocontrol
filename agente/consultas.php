<?php
// consultas.php
require_once __DIR__ . '/config.php';

function intervalo_periodo(string $periodo): array {
    $hoje = new DateTime('today');
    switch ($periodo) {
        case 'semana': $ini = (clone $hoje)->modify('-7 days'); break;
        case 'mes':    $ini = new DateTime($hoje->format('Y-m-01')); break;
        case 'ano':    $ini = new DateTime($hoje->format('Y-01-01')); break;
        default:       $ini = (clone $hoje)->modify('-7 days');
    }
    return [$ini->format('Y-m-d'), $hoje->format('Y-m-d')];
}

function producao_leite(int $fazenda_id, string $periodo): array {
    [$ini, $fim] = intervalo_periodo($periodo);
    $st = db()->prepare("
        SELECT data, SUM(litros) AS litros
        FROM producao_leite
        WHERE fazenda_id = :f AND data BETWEEN :i AND :fim
        GROUP BY data ORDER BY data
    ");
    $st->execute([':f'=>$fazenda_id, ':i'=>$ini, ':fim'=>$fim]);
    $rows = $st->fetchAll();

    $total = 0; $melhor = 0; $pior = PHP_FLOAT_MAX;
    foreach ($rows as $r) {
        $v = (float)$r['litros'];
        $total += $v;
        $melhor = max($melhor, $v);
        $pior   = min($pior, $v);
    }
    $media = count($rows) ? $total / count($rows) : 0;

    return [
        'periodo'          => $periodo,
        'inicio'           => $ini,
        'fim'              => $fim,
        'total_litros'     => round($total, 1),
        'media_diaria'     => round($media, 1),
        'melhor_dia'       => $melhor,
        'pior_dia'         => $pior === PHP_FLOAT_MAX ? 0 : $pior,
        'dias_registrados' => count($rows),
    ];
}

function proximas_vacinas(int $fazenda_id, int $dias = 30): array {
    $lim = (new DateTime("+{$dias} days"))->format('Y-m-d');
    $st = db()->prepare("
        SELECT a.nome AS animal, v.vacina, v.data_prevista
        FROM vacinas v
        JOIN animais a ON a.id = v.animal_id
        WHERE a.fazenda_id = :f
          AND v.aplicada = 0
          AND v.data_prevista <= :lim
        ORDER BY v.data_prevista ASC
        LIMIT 20
    ");
    $st->execute([':f'=>$fazenda_id, ':lim'=>$lim]);
    return $st->fetchAll();
}

function proximo_evento(int $fazenda_id): array {
    $st = db()->prepare("
        SELECT titulo, data, tipo
        FROM eventos
        WHERE fazenda_id = :f AND data >= NOW()
        ORDER BY data ASC LIMIT 3
    ");
    $st->execute([':f'=>$fazenda_id]);
    return $st->fetchAll();
}

function executar_intencao(int $fazenda_id, string $intencao, array $params): array {
    switch ($intencao) {
        case 'producao_leite':
            return producao_leite($fazenda_id, $params['periodo'] ?? 'semana');
        case 'proximas_vacinas':
            return proximas_vacinas($fazenda_id, (int)($params['dias'] ?? 30));
        case 'proximo_evento':
            return proximo_evento($fazenda_id);
        default:
            return ['erro' => true];
    }
}