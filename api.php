<?php
// api.php — REST API sederhana untuk CRUD laporan
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = getDB();

    // ── GET: ambil semua / satu laporan ──────────────────────────────────
    if ($method === 'GET') {

        if ($action === 'detail' && isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM laporan WHERE id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $row = $stmt->fetch();
            echo json_encode($row ?: ['error'=>true,'message'=>'Tidak ditemukan']);
            exit;
        }

        // List dengan filter
        $where = [];
        $params = [];

        if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
            $where[] = "status = ?";
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['q'])) {
            $where[] = "(kategori LIKE ? OR ringkasan LIKE ? OR catatan_admin LIKE ?)";
            $like = '%' . $_GET['q'] . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }

        $sql = "SELECT id, waktu, kategori, status, ringkasan, latitude, longitude, catatan_admin
                FROM laporan"
             . ($where ? " WHERE " . implode(" AND ", $where) : "")
             . " ORDER BY waktu DESC LIMIT 500";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // KPI counts
        $kpi = $db->query(
            "SELECT status, COUNT(*) AS cnt FROM laporan GROUP BY status"
        )->fetchAll();
        $kpiMap = array_column($kpi, 'cnt', 'status');

        echo json_encode([
            'rows' => $rows,
            'kpi'  => [
                'pending'     => (int)($kpiMap['pending']     ?? 0),
                'verified'    => (int)($kpiMap['verified']    ?? 0),
                'in_progress' => (int)($kpiMap['in_progress'] ?? 0),
                'resolved'    => (int)($kpiMap['resolved']    ?? 0),
                'rejected'    => (int)($kpiMap['rejected']    ?? 0),
            ]
        ]);
        exit;
    }

    // ── POST: tambah laporan baru ─────────────────────────────────────────
    if ($method === 'POST' && $action === 'tambah') {
        $data = json_decode(file_get_contents('php://input'), true);

        $stmt = $db->prepare(
            "INSERT INTO laporan (kategori, status, ringkasan, deskripsi, latitude, longitude)
             VALUES (:kategori, :status, :ringkasan, :deskripsi, :lat, :lng)"
        );
        $stmt->execute([
            ':kategori'  => trim($data['kategori']  ?? ''),
            ':status'    => $data['status']          ?? 'pending',
            ':ringkasan' => trim($data['ringkasan']  ?? ''),
            ':deskripsi' => trim($data['deskripsi']  ?? ''),
            ':lat'       => $data['latitude']        ?? null,
            ':lng'       => $data['longitude']       ?? null,
        ]);
        echo json_encode(['success'=>true, 'id'=>$db->lastInsertId()]);
        exit;
    }

    // ── PUT: update status + catatan admin ────────────────────────────────
    if ($method === 'POST' && $action === 'update') {
        $data = json_decode(file_get_contents('php://input'), true);

        $stmt = $db->prepare(
            "UPDATE laporan
             SET status = :status, catatan_admin = :note
             WHERE id = :id"
        );
        $stmt->execute([
            ':status' => $data['status']  ?? 'pending',
            ':note'   => $data['catatan'] ?? '',
            ':id'     => (int)($data['id'] ?? 0),
        ]);
        echo json_encode(['success'=>true, 'affected'=>$stmt->rowCount()]);
        exit;
    }

    // ── DELETE ────────────────────────────────────────────────────────────
    if ($method === 'POST' && $action === 'hapus') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare("DELETE FROM laporan WHERE id = ?");
        $stmt->execute([(int)($data['id'] ?? 0)]);
        echo json_encode(['success'=>true]);
        exit;
    }

    echo json_encode(['error'=>true,'message'=>'Action tidak dikenal']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error'=>true,'message'=> $e->getMessage()]);
}
