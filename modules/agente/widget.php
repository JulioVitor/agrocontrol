<?php
// modules/agente/widget.php
// Widget do agente de IA — para incluir em qualquer página do produtor

// Garante que BASE_URL está definido
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/constants.php';
}

$fazenda_id_widget = $_SESSION['fazenda_id'] ?? 0;
?>

<link rel="stylesheet" href="<?= BASE_URL ?>agente/css/agente.css">

<div id="agente-widget">
  <div id="agente-chat"></div>
  <form id="agente-form">
    <input id="agente-input"
           placeholder="Pergunte algo… ex: produção de leite da semana"
           autocomplete="off">
    <button type="submit">Enviar</button>
  </form>
  <input type="hidden" id="agente-fazenda" value="<?= (int)$fazenda_id_widget ?>">
</div>

<script>
  window.AGENTE_BASE_URL = "<?= BASE_URL ?>";
  window.AGENTE_FAZENDA_ID = <?= (int)$fazenda_id_widget ?>;
</script>
<script src="<?= BASE_URL ?>agente/js/agente.js"></script>