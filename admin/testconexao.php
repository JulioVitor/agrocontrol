<?php
// admin/teste_conexao.php
echo "<h1>Teste de Conexão</h1>";

// Testar conexão com banco
require_once '../config/database.php';
echo "<p>Conexão com banco: " . ($conn ? "✅ OK" : "❌ FALHA") . "</p>";

// Verificar se a tabela super_admin existe
$sql = "SHOW TABLES LIKE 'super_admin'";
$result = $conn->query($sql);
echo "<p>Tabela super_admin: " . ($result->num_rows > 0 ? "✅ EXISTE" : "❌ NÃO EXISTE") . "</p>";

// Listar admins
$sql = "SELECT id, nome, email, ultimo_acesso FROM super_admin";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<h3>Admins cadastrados:</h3>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>";
        echo "ID: {$row['id']} - ";
        echo "Nome: {$row['nome']} - ";
        echo "Email: {$row['email']} - ";
        echo "Último acesso: " . ($row['ultimo_acesso'] ?? 'Nunca');
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>❌ Nenhum admin encontrado!</p>";
}

// Testar senha
$teste_senha = 'admin123';
$hash_teste = password_hash($teste_senha, PASSWORD_DEFAULT);
echo "<p>Hash de 'admin123' gerado agora: " . $hash_teste . "</p>";
?>