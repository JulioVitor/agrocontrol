<?php
// respostas.php
require_once __DIR__ . '/util.php';

function montar_resposta(string $intencao, array $dados, string $pergunta): string {
    switch ($intencao) {

        case 'producao_leite':
            if (empty($dados['dias_registrados'])) {
                return "Não encontrei registros de produção de leite nesse período. 🐄";
            }
            $p = $dados['periodo'];
            $rotulo = $p === 'semana' ? 'últimos 7 dias'
                    : ($p === 'mes'   ? 'neste mês'
                    :                   'neste ano');
            return sprintf(
                "🥛 Sua produção %s foi de *%s*, com média de *%s/dia* em %d dias registrados.\n" .
                "Melhor dia: %s • Pior dia: %s",
                $rotulo,
                litros($dados['total_litros']),
                litros($dados['media_diaria']),
                $dados['dias_registrados'],
                litros($dados['melhor_dia']),
                litros($dados['pior_dia'])
            );

        case 'proximas_vacinas':
            if (empty($dados)) {
                return "✅ Nenhuma vacina pendente nos próximos dias. Tudo em dia!";
            }
            $linhas = ["💉 Próximas vacinas:"];
            foreach ($dados as $v) {
                $linhas[] = sprintf(
                    "• %s — %s (%s)",
                    data_br($v['data_prevista']),
                    $v['vacina'],
                    $v['animal']
                );
            }
            return implode("\n", $linhas);

        case 'proximo_evento':
            if (empty($dados)) {
                return "📭 Nenhum evento agendado por enquanto.";
            }
            $linhas = ["📅 Próximos eventos:"];
            foreach ($dados as $e) {
                $linhas[] = sprintf(
                    "• %s — %s%s",
                    data_hora_br($e['data']),
                    $e['titulo'],
                    $e['tipo'] ? " ({$e['tipo']})" : ''
                );
            }
            return implode("\n", $linhas);
    }

    return "🤔 Ainda não entendi essa pergunta. Tente algo como:\n" .
           "• \"Qual foi minha produção de leite da semana?\"\n" .
           "• \"Quais as próximas vacinas?\"\n" .
           "• \"Meu próximo evento?\"";
}