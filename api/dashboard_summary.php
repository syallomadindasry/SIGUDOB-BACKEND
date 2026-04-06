<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/db.php";

try {
    $pdo = db();

    $id_gudang = isset($_GET["id_gudang"]) ? (int) $_GET["id_gudang"] : 0;
    $role = isset($_GET["role"]) ? strtolower(trim($_GET["role"])) : "";

    $isDinkes = ($role === "dinkes");

    if ($isDinkes) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total_item_obat FROM data_obat");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT b.id_obat) AS total_item_obat
            FROM stok_batch sb
            JOIN data_batch b ON sb.id_batch = b.id_batch
            WHERE sb.id_gudang = ?
              AND sb.stok > 0
        ");
        $stmt->execute([$id_gudang]);
    }
    $totalItemObat = (int) ($stmt->fetch(PDO::FETCH_ASSOC)["total_item_obat"] ?? 0);

    if ($isDinkes) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(stok), 0) AS total_stok FROM stok_batch");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(stok), 0) AS total_stok
            FROM stok_batch
            WHERE id_gudang = ?
        ");
        $stmt->execute([$id_gudang]);
    }
    $totalStok = (int) ($stmt->fetch(PDO::FETCH_ASSOC)["total_stok"] ?? 0);

    if ($isDinkes) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS jumlah_stok_kritis
            FROM (
                SELECT b.id_obat
                FROM stok_batch sb
                JOIN data_batch b ON sb.id_batch = b.id_batch
                GROUP BY b.id_obat
                HAVING SUM(sb.stok) < 100
            ) x
        ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS jumlah_stok_kritis
            FROM (
                SELECT b.id_obat
                FROM stok_batch sb
                JOIN data_batch b ON sb.id_batch = b.id_batch
                WHERE sb.id_gudang = ?
                GROUP BY b.id_obat
                HAVING SUM(sb.stok) < 100
            ) x
        ");
        $stmt->execute([$id_gudang]);
    }
    $jumlahStokKritis = (int) ($stmt->fetch(PDO::FETCH_ASSOC)["jumlah_stok_kritis"] ?? 0);

    if ($isDinkes) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS jumlah_segera_expired
            FROM stok_batch sb
            JOIN data_batch b ON sb.id_batch = b.id_batch
            WHERE sb.stok > 0
              AND b.exp_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS jumlah_segera_expired
            FROM stok_batch sb
            JOIN data_batch b ON sb.id_batch = b.id_batch
            WHERE sb.id_gudang = ?
              AND sb.stok > 0
              AND b.exp_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$id_gudang]);
    }
    $jumlahSegeraExpired = (int) ($stmt->fetch(PDO::FETCH_ASSOC)["jumlah_segera_expired"] ?? 0);

    echo json_encode([
        "success" => true,
        "data" => [
            "total_item_obat" => $totalItemObat,
            "total_stok" => $totalStok,
            "jumlah_stok_kritis" => $jumlahStokKritis,
            "jumlah_segera_expired" => $jumlahSegeraExpired
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Gagal mengambil ringkasan dashboard",
        "error" => $e->getMessage()
    ]);
}