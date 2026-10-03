<?php
// intencoes.php
require_once __DIR__ . '/util.php';

/**
 * Retorna ['intencao' => string, 'params' => array]
 * Ordem importa: as mais específicas primeiro.
 */
function reconhecer_intencao(string $pergunta, array $historico = []): array {
    $t = numero_em_texto(normalizar($pergunta));

    // 🔹 PRODUÇÃO DE LEITE -----------------------------------------------
    if (preg_match('/\b(producao|produziu|litros|leite)\b/', $t)) {
        // "e no mes?" / "e no ano?" / "e na semana?" → herda intenção anterior
        if (preg_match('/\b(semana|mes|ano)\b/', $t)) {
            $periodo = preg_match('/\bsemana\b/', $t) ? 'semana'
                     : (preg_match('/\bmes\b/', $t)   ? 'mes'
                     : 'ano');
            return ['intencao' => 'producao_leite', 'params' => ['periodo' => $periodo]];
        }
        // "produção de leite da semana" já cai aqui também
        if (preg_match('/\b(da|na|nesta|essa)\s+semana\b/', $t)) {
            return ['intencao' => 'producao_leite', 'params' => ['periodo' => 'semana']];
        }
        return ['intencao' => 'producao_leite', 'params' => ['periodo' => 'semana']];
    }

    // 🔹 PRÓXIMAS VACINAS -------------------------------------------------
    if (preg_match('/\b(vacina|vacinacao|vacinas|imunizacao)\b/', $t)) {
        $dias = 30;
        if (preg_match('/\b(\d{1,3})\s*dias?\b/', $t, $m)) {
            $dias = (int)$m[1];
        } elseif (preg_match('/\b(mes|30 dias)\b/', $t)) {
            $dias = 30;
        } elseif (preg_match('/\bsemana\b/', $t)) {
            $dias = 7;
        }
        return ['intencao' => 'proximas_vacinas', 'params' => ['dias' => $dias]];
    }

    // 🔹 PRÓXIMO EVENTO ---------------------------------------------------
    if (preg_match('/\b(evento|agenda|compromisso|leilao|reuniao|visita)\b/', $t)) {
        return ['intencao' => 'proximo_evento', 'params' => []];
    }

    // 🔹 Herança de contexto: "e no mes?" sozinho ------------------------
    if (preg_match('/\b(e|e no|e na|agora|tambem)\b.*\b(mes|ano|semana)\b/', $t) && $historico) {
        $ultima = end($historico)['intencao'] ?? '';
        if ($ultima === 'producao_leite') {
            $periodo = preg_match('/\bsemana\b/', $t) ? 'semana'
                     : (preg_match('/\bmes\b/', $t)   ? 'mes'
                     : 'ano');
            return ['intencao' => 'producao_leite', 'params' => ['periodo' => $periodo]];
        }
    }

    return ['intencao' => 'desconhecida', 'params' => []];
}