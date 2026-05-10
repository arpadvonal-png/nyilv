<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/Response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Response::json(['ok' => true]);
}

$method = $_SERVER['REQUEST_METHOD'];
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
$path = preg_replace('#^.*api/#', '', $path);
$path = preg_replace('#^index\.php/?#', '', $path);
$segments = $path === '' ? [] : explode('/', $path);
$resource = $_GET['resource'] ?? ($segments[0] ?? 'dashboard');
$idValue = $_GET['id'] ?? ($segments[1] ?? null);
$id = is_string($idValue) && ctype_digit($idValue) ? (int) $idValue : null;
$pdo = Database::connection();

try {
    match ($resource) {
        'customers' => handleCustomers($pdo, $method, $id),
        'equipment' => handleEquipment($pdo, $method, $id),
        'maintenance' => handleMaintenance($pdo, $method, $id),
        'appointments' => handleAppointments($pdo, $method, $id),
        'work-orders' => handleWorkOrders($pdo, $method, $id),
        'reminders' => handleReminders($pdo),
        'dashboard' => handleDashboard($pdo),
        default => Response::json(['error' => 'Ismeretlen végpont.'], 404),
    };
} catch (Throwable $exception) {
    Response::json(['error' => 'Szerverhiba.', 'detail' => $exception->getMessage()], 500);
}

function handleCustomers(PDO $pdo, string $method, ?int $id): void
{
    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM customers ORDER BY name');
        Response::json($stmt->fetchAll());
    }

    if ($method === 'POST') {
        $data = Request::body();
        $missing = Request::requireFields($data, ['name', 'phone', 'email', 'address']);
        if ($missing !== []) {
            Response::json(['error' => 'Hiányzó mezők.', 'fields' => $missing], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO customers (name, phone, email, address, notes) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['notes'] ?? '',
        ]);
        Response::json(['id' => (int) $pdo->lastInsertId()], 201);
    }

    if ($method === 'PUT' && $id !== null) {
        $data = Request::body();
        $stmt = $pdo->prepare('UPDATE customers SET name = ?, phone = ?, email = ?, address = ?, notes = ? WHERE id = ?');
        $stmt->execute([
            $data['name'] ?? '',
            $data['phone'] ?? '',
            $data['email'] ?? '',
            $data['address'] ?? '',
            $data['notes'] ?? '',
            $id,
        ]);
        Response::json(['updated' => $stmt->rowCount()]);
    }

    if ($method === 'DELETE' && $id !== null) {
        $stmt = $pdo->prepare('DELETE FROM customers WHERE id = ?');
        $stmt->execute([$id]);
        Response::json(['deleted' => $stmt->rowCount()]);
    }

    Response::json(['error' => 'Nem támogatott művelet.'], 405);
}

function handleEquipment(PDO $pdo, string $method, ?int $id): void
{
    if ($method === 'GET') {
        $stmt = $pdo->query(
            'SELECT e.*, c.name AS customer_name
             FROM equipment e
             JOIN customers c ON c.id = e.customer_id
             ORDER BY e.next_maintenance_date ASC, e.name'
        );
        Response::json($stmt->fetchAll());
    }

    if ($method === 'POST') {
        $data = Request::body();
        $missing = Request::requireFields($data, ['customer_id', 'type', 'name', 'serial_number', 'installation_date', 'next_maintenance_date']);
        if ($missing !== []) {
            Response::json(['error' => 'Hiányzó mezők.', 'fields' => $missing], 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO equipment (customer_id, type, name, serial_number, installation_date, next_maintenance_date)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['customer_id'],
            $data['type'],
            $data['name'],
            $data['serial_number'],
            $data['installation_date'],
            $data['next_maintenance_date'],
        ]);
        Response::json(['id' => (int) $pdo->lastInsertId()], 201);
    }

    if ($method === 'DELETE' && $id !== null) {
        $stmt = $pdo->prepare('DELETE FROM equipment WHERE id = ?');
        $stmt->execute([$id]);
        Response::json(['deleted' => $stmt->rowCount()]);
    }

    Response::json(['error' => 'Nem támogatott művelet.'], 405);
}

function handleMaintenance(PDO $pdo, string $method, ?int $id): void
{
    if ($method === 'GET') {
        $stmt = $pdo->query(
            'SELECT m.*, e.name AS equipment_name, c.name AS customer_name
             FROM maintenance_records m
             JOIN equipment e ON e.id = m.equipment_id
             JOIN customers c ON c.id = e.customer_id
             ORDER BY m.service_date DESC'
        );
        Response::json($stmt->fetchAll());
    }

    if ($method === 'POST') {
        $data = Request::body();
        $missing = Request::requireFields($data, ['equipment_id', 'service_date', 'technician', 'description']);
        if ($missing !== []) {
            Response::json(['error' => 'Hiányzó mezők.', 'fields' => $missing], 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO maintenance_records (equipment_id, service_date, technician, description, parts_used, cost)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['equipment_id'],
            $data['service_date'],
            $data['technician'],
            $data['description'],
            $data['parts_used'] ?? '',
            $data['cost'] ?? 0,
        ]);
        Response::json(['id' => (int) $pdo->lastInsertId()], 201);
    }

    if ($method === 'DELETE' && $id !== null) {
        $stmt = $pdo->prepare('DELETE FROM maintenance_records WHERE id = ?');
        $stmt->execute([$id]);
        Response::json(['deleted' => $stmt->rowCount()]);
    }

    Response::json(['error' => 'Nem támogatott művelet.'], 405);
}

function handleAppointments(PDO $pdo, string $method, ?int $id): void
{
    if ($method === 'GET') {
        $stmt = $pdo->query(
            'SELECT a.*, c.name AS customer_name, e.name AS equipment_name
             FROM appointments a
             JOIN customers c ON c.id = a.customer_id
             LEFT JOIN equipment e ON e.id = a.equipment_id
             ORDER BY a.appointment_at ASC'
        );
        Response::json($stmt->fetchAll());
    }

    if ($method === 'POST') {
        $data = Request::body();
        $missing = Request::requireFields($data, ['customer_id', 'appointment_at', 'service_type']);
        if ($missing !== []) {
            Response::json(['error' => 'Hiányzó mezők.', 'fields' => $missing], 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO appointments (customer_id, equipment_id, appointment_at, service_type, status, notes)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['customer_id'],
            $data['equipment_id'] ?? null,
            str_replace('T', ' ', $data['appointment_at']),
            $data['service_type'],
            $data['status'] ?? 'tervezett',
            $data['notes'] ?? '',
        ]);
        Response::json(['id' => (int) $pdo->lastInsertId()], 201);
    }

    if ($method === 'PUT' && $id !== null) {
        $data = Request::body();
        $stmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
        $stmt->execute([$data['status'] ?? 'tervezett', $id]);
        Response::json(['updated' => $stmt->rowCount()]);
    }

    if ($method === 'DELETE' && $id !== null) {
        $stmt = $pdo->prepare('DELETE FROM appointments WHERE id = ?');
        $stmt->execute([$id]);
        Response::json(['deleted' => $stmt->rowCount()]);
    }

    Response::json(['error' => 'Nem támogatott művelet.'], 405);
}

function handleWorkOrders(PDO $pdo, string $method, ?int $id): void
{
    if ($method === 'GET') {
        $stmt = $pdo->query(
            'SELECT w.*, a.appointment_at, c.name AS customer_name, c.address, e.name AS equipment_name
             FROM work_orders w
             JOIN appointments a ON a.id = w.appointment_id
             JOIN customers c ON c.id = a.customer_id
             LEFT JOIN equipment e ON e.id = a.equipment_id
             ORDER BY w.created_at DESC'
        );
        Response::json($stmt->fetchAll());
    }

    if ($method === 'POST') {
        $data = Request::body();
        $missing = Request::requireFields($data, ['appointment_id', 'work_summary']);
        if ($missing !== []) {
            Response::json(['error' => 'Hiányzó mezők.', 'fields' => $missing], 422);
        }

        $number = 'ML-' . date('Ymd') . '-' . random_int(1000, 9999);
        $stmt = $pdo->prepare(
            'INSERT INTO work_orders (appointment_id, work_order_number, work_summary, materials, customer_signature)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['appointment_id'],
            $number,
            $data['work_summary'],
            $data['materials'] ?? '',
            $data['customer_signature'] ?? '',
        ]);
        Response::json(['id' => (int) $pdo->lastInsertId(), 'work_order_number' => $number], 201);
    }

    if ($method === 'DELETE' && $id !== null) {
        $stmt = $pdo->prepare('DELETE FROM work_orders WHERE id = ?');
        $stmt->execute([$id]);
        Response::json(['deleted' => $stmt->rowCount()]);
    }

    Response::json(['error' => 'Nem támogatott művelet.'], 405);
}

function handleReminders(PDO $pdo): void
{
    $stmt = $pdo->query(
        'SELECT e.id, e.type, e.name, e.next_maintenance_date, c.name AS customer_name, c.phone, c.email
         FROM equipment e
         JOIN customers c ON c.id = e.customer_id
         WHERE e.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 45 DAY)
         ORDER BY e.next_maintenance_date ASC'
    );
    Response::json($stmt->fetchAll());
}

function handleDashboard(PDO $pdo): void
{
    $counts = [];
    foreach (['customers', 'equipment', 'appointments', 'work_orders'] as $table) {
        $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }

    $today = $pdo->query(
        "SELECT COUNT(*) FROM appointments WHERE DATE(appointment_at) = CURDATE() AND status <> 'lemondva'"
    )->fetchColumn();

    Response::json([
        'counts' => $counts,
        'today_appointments' => (int) $today,
    ]);
}
