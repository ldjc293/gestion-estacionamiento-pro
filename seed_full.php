<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => true // RECONEXION SUPABASE
    ]);

    echo "Limpiando datos previos...\n";
    $pdo->exec("TRUNCATE TABLE pago_mensualidad RESTART IDENTITY CASCADE");
    $pdo->exec("TRUNCATE TABLE mensualidades RESTART IDENTITY CASCADE");
    $pdo->exec("TRUNCATE TABLE controles_estacionamiento RESTART IDENTITY CASCADE");
    $pdo->exec("TRUNCATE TABLE solicitudes_cambios RESTART IDENTITY CASCADE");
    $pdo->exec("TRUNCATE TABLE apartamento_usuario RESTART IDENTITY CASCADE");
    $pdo->exec("TRUNCATE TABLE apartamentos RESTART IDENTITY CASCADE");

    echo "Insertando Apartamentos (144 total)...\n";
    
    // Configuración de bloques (escalera => aptos_por_piso)
    $bloques_config = [
        '27' => ['1' => 3, '2' => 4, '3' => 3, '4' => 5],
        '28' => ['1' => 4],
        '29' => ['1' => 5, '2' => 4],
        '30' => ['1' => 5],
        '31' => ['1' => 5],
        '32' => ['1' => 4, '2' => 4, '3' => 5]
    ];

    $stmt = $pdo->prepare("INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES (?, ?, ?, ?, TRUE)");
    
    $total_aptos = 0;
    foreach ($bloques_config as $bloque => $escaleras) {
        foreach ($escaleras as $escalera => $num_aptos) {
            // Pisos: 0 (PB), 1, 2, 3, 4
            for ($piso = 0; $piso <= 4; $piso++) {
                for ($n = 1; $n <= $num_aptos; $n++) {
                    // Formato: 0001, 0101, 0201, etc.
                    $numero = sprintf("%02d%02d", $piso, $n);
                    
                    // Insertamos el entero $piso (0 para PB)
                    $stmt->execute([$bloque, $escalera, $piso, $numero]);
                    $total_aptos++;
                }
            }
        }
    }
    echo "✅ $total_aptos apartamentos creados.\n";

    $values = [];
    $params = [];
    for ($i = 1; $i <= 250; $i++) {
        $values[] = "(?, 'A', ?, 'vacio')";
        $params[] = $i;
        $params[] = $i . 'A';
        
        $values[] = "(?, 'B', ?, 'vacio')";
        $params[] = $i;
        $params[] = $i . 'B';
    }
    
    $sql_control = "INSERT INTO controles_estacionamiento (posicion_numero, receptor, numero_control_completo, estado) VALUES " . implode(", ", $values);
    $pdo->prepare($sql_control)->execute($params);
    echo "✅ 500 controles generados.\n";

    echo "Poblando algunos datos de prueba (Usuarios @test.com a apartamentos)...\n";
    // María González -> Bloque 29, A, 501
    $stmt_apto = $pdo->prepare("SELECT id FROM apartamentos WHERE bloque = ? AND escalera = ? AND numero_apartamento = ? LIMIT 1");
    
    $users = [
        ['email' => 'admin@test.com', 'bloque' => '27', 'esc' => '1', 'apto' => '101'],
        ['email' => 'operador@test.com', 'bloque' => '27', 'esc' => '1', 'apto' => '102'],
        ['email' => 'consultor@test.com', 'bloque' => '27', 'esc' => '2', 'apto' => '101'],
        ['email' => 'cliente@test.com', 'bloque' => '29', 'esc' => '1', 'apto' => '501']
    ];

    $stmt_assign = $pdo->prepare("INSERT INTO apartamento_usuario (apartamento_id, usuario_id, cantidad_controles, activo) VALUES (?, ?, ?, TRUE)");
    $stmt_user = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");

    foreach ($users as $u) {
        $stmt_user->execute([$u['email']]);
        $user_id = $stmt_user->fetchColumn();
        
        $stmt_apto->execute([$u['bloque'], $u['esc'], $u['apto']]);
        $apto_id = $stmt_apto->fetchColumn();
        
        if ($user_id && $apto_id) {
            $stmt_assign->execute([$apto_id, $user_id, 2]);
        }
    }
    echo "✅ Usuarios de prueba re-asignados.\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "FAILURE: " . $e->getMessage() . "\n";
}
