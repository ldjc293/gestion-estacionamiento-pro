<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "--- INICIANDO LIMPIEZA TOTAL ---\n";
    $tables = ['pago_mensualidad', 'mensualidades', 'controles_estacionamiento', 'solicitudes_cambios', 'apartamento_usuario', 'apartamentos'];
    foreach ($tables as $t) {
        $pdo->exec("TRUNCATE TABLE public.$t RESTART IDENTITY CASCADE");
    }

    echo "--- CARGANDO 255 APARTAMENTOS (PB-4) ---\n";
    $data = [
        '27' => [1=>3, 2=>4, 3=>3, 4=>5],
        '28' => [1=>4],
        '29' => [1=>5, 2=>4],
        '30' => [1=>5],
        '31' => [1=>5],
        '32' => [1=>4, 2=>4, 3=>5]
    ];

    $stmt = $pdo->prepare("INSERT INTO public.apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES (?, ?, ?, ?, TRUE)");
    $total = 0;

    foreach ($data as $bloque => $escaleras) {
        foreach ($escaleras as $escalera => $aptos_por_piso) {
            for ($piso = 0; $piso <= 4; $piso++) {
                for ($n = 1; $n <= $aptos_por_piso; $n++) {
                    $numero = $piso . "0" . $n;
                    $stmt->execute([$bloque, $escalera, $piso, $numero]);
                    $total++;
                }
            }
        }
    }
    echo "--- TOTAL APARTAMENTOS CREADOS: $total ---\n";

    echo "--- GENERANDO 500 CONTROLES ---\n";
    $stmt_c = $pdo->prepare("INSERT INTO public.controles_estacionamiento (posicion_numero, receptor, numero_control_completo, estado) VALUES (?, ?, ?, 'vacio')");
    $pdo->beginTransaction();
    for ($i = 1; $i <= 250; $i++) {
        $stmt_c->execute([$i, 'A', $i . 'A']);
        $stmt_c->execute([$i, 'B', $i . 'B']);
    }
    $pdo->commit();
    echo "--- 500 CONTROLES GENERADOS ---\n";

    echo "--- ACTIVANDO RLS (SEGURIDAD) EN TODAS LAS TABLAS ---\n";
    $all_tables = ['usuarios', 'apartamentos', 'apartamento_usuario', 'controles_estacionamiento', 'mensualidades', 'pagos', 'pago_mensualidad', 'solicitudes_cambios', 'logs_actividad', 'tasa_cambio_bcv', 'notificaciones', 'configuracion_tarifas', 'configuracion_cron', 'login_intentos', 'password_reset_tokens'];
    foreach ($all_tables as $t) {
        $pdo->exec("ALTER TABLE public.$t ENABLE ROW LEVEL SECURITY");
    }
    echo "--- RLS ACTIVADO CON ÉXITO ---\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo "ERROR FATAL: " . $e->getMessage() . "\n";
}
