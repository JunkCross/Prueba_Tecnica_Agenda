<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

$routes = new RouteCollection();
$routes->add('schedules_list', new Route('/api/schedules', ['_controller' => 'list'], [], [], '', [], ['GET']));
$routes->add('schedules_create', new Route('/api/schedules', ['_controller' => 'create'], [], [], '', [], ['POST']));
$routes->add('options', new Route('/api/schedules', ['_controller' => 'options'], [], [], '', [], ['OPTIONS']));
$routes->add('schedules_delete', new Route('/api/schedules/{id}',   ['_controller' => 'delete'],  ['id'=>'\d+'], [], '', [], ['DELETE']));
$routes->add('options_id',       new Route('/api/schedules/{id}',   ['_controller' => 'options'], [], [], '', [], ['OPTIONS']));

$context = new RequestContext();
$context->fromRequest(Request::createFromGlobals());
$matcher = new UrlMatcher($routes, $context);

function get_db_pdo() {
    $dsn = getenv('DATABASE_URL') ?: 'pgsql:host=db;port=5432;dbname=calendario_db';
    // "Permitir DATABASE_URL en el formato postgresql://usuario:contraseña@host:puerto/nombre_base_de_datos"
    if (strpos($dsn, 'postgresql://') === 0 || strpos($dsn, 'postgres://') === 0) {
        $parts = parse_url($dsn);
        $user = $parts['user'] ?? null;
        $pass = $parts['pass'] ?? null;
        $host = $parts['host'] ?? 'db';
        $port = $parts['port'] ?? 5432;
        $db = ltrim($parts['path'],'/');
        $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
    }
    try {
        $pdo = new PDO($dsn, $user ?? 'user', $pass ?? 'password', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        return $pdo;
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'db_connection_failed', 'message' => $e->getMessage()]);
        exit;
    }
}

function json_response($data, $status=200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
}

$request = Request::createFromGlobals();
$path = $request->getPathInfo();
$method = $request->getMethod();

try {
    $params = $matcher->match($path);
    $action = $params['_controller'];
} catch (Exception $e) {
    json_response(['error'=>'not_found'], 404);
    exit;
}

if ($action === 'options') {
    json_response(['ok'=>true], 200);
    exit;
}

$pdo = get_db_pdo();

if ($action === 'list' && $method === 'GET') {
    $stmt = $pdo->query('SELECT id, day_of_week, to_char(start_time,\'HH24:MI\') as start_time, to_char(end_time,\'HH24:MI\') as end_time, created_at FROM schedules ORDER BY day_of_week, start_time');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json_response($rows);
    exit;
}

if ($action === 'create' && $method === 'POST') {
    $body = json_decode($request->getContent(), true);
    if (!isset($body['day_of_week'], $body['start_time'], $body['end_time'])) {
        json_response(['error'=>'invalid_body'], 400);
        exit;
    }
    $dow = (int)$body['day_of_week'];
    $start = $body['start_time'];
    $end = $body['end_time'];
    
    if ($dow < 1 || $dow > 7) {
        json_response(['error'=>'day_of_week must be 1..7'], 400);
        exit;
    }
    // Insert
    $stmt = $pdo->prepare('INSERT INTO schedules (day_of_week, start_time, end_time) VALUES (:dow, :start, :end) RETURNING id');
    $stmt->execute([':dow'=>$dow, ':start'=>$start, ':end'=>$end]);
    $id = $stmt->fetchColumn();
    json_response(['status'=>'ok','id'=>$id], 201);
    exit;

}

// Delete
if ($action === 'delete' && $method === 'DELETE') {
    $id = isset($params['id']) ? (int)$params['id'] : 0;
    if ($id <= 0) {
        json_response(['error'=>'invalid_id'], 400);
        exit;
    }
    $stmt = $pdo->prepare('DELETE FROM schedules WHERE id = :id');
    $stmt->execute([':id'=>$id]);
    $deleted = $stmt->rowCount();
    if ($deleted) {
        json_response(['status'=>'ok','deleted'=>1], 200);
    } else {
        json_response(['error'=>'not_found'], 404);
    }
    exit;
}

json_response(['error'=>'method_not_allowed'], 405);
