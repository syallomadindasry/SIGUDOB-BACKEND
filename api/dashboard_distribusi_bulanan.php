<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/db.php";

try {
    $pdo = db();

    $months = isset($_GET["months"]) ? (int) $_GET["months"] : 6;
    $months = max(1, $months);

    $id_gudang = isset($_GET["id_gudang"]) ? (int) $_GET["id_gudang"] : 0;
    $role = isset($_GET["role"]) ? strtolower(trim($_GET["role"])) : "";

    $monthNames = [
        1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr",
        5 => "Mei", 6 => "Jun", 7 => "Jul", 8 => "Agu",
        9 => "Sep", 10 => "Okt", 11 => "Nov", 12 => "Des"
    ];

    $whereAnchor = "";
    $anchorParams = [];

    if ($role !== "dinkes" && $id_gudang > 0) {
        $whereAnchor = " WHERE m.sumber = ? ";
        $anchorParams[] = $id_gudang;
    }

    $sqlLatest = "
        SELECT MAX(DATE(m.tanggal)) AS latest_tanggal
        FROM mutasi m
        $whereAnchor
    ";

    $stmtLatest = $pdo->prepare($sqlLatest);
    $stmtLatest->execute($anchorParams);
    $latestRow = $stmtLatest->fetch(PDO::FETCH_ASSOC);

    if (empty($latestRow["latest_tanggal"])) {
        $now = new DateTime();
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $d = (clone $now)->modify("-$i months");
            $bulanAngka = (int) $d->format("n");

            $data[] = [
                "tahun" => (int) $d->format("Y"),
                "bulanAngka" => $bulanAngka,
                "bulan" => $monthNames[$bulanAngka],
                "total" => 0
            ];
        }

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
        exit;
    }

    $anchorDate = new DateTime($latestRow["latest_tanggal"]);
    $startDate = (clone $anchorDate)
        ->modify("-" . ($months - 1) . " months")
        ->modify("first day of this month");
    $endDate = (clone $anchorDate)->modify("last day of this month");

    $params = [
        $startDate->format("Y-m-d"),
        $endDate->format("Y-m-d")
    ];

    $sql = "
        SELECT
            YEAR(m.tanggal) AS tahun,
            MONTH(m.tanggal) AS bulan,
            COALESCE(SUM(md.jumlah), 0) AS total
        FROM mutasi m
        JOIN mutasi_detail md ON m.id = md.id_mutasi
        WHERE DATE(m.tanggal) BETWEEN ? AND ?
    ";

    if ($role !== "dinkes" && $id_gudang > 0) {
        $sql .= " AND m.sumber = ? ";
        $params[] = $id_gudang;
    }

    $sql .= "
        GROUP BY YEAR(m.tanggal), MONTH(m.tanggal)
        ORDER BY YEAR(m.tanggal), MONTH(m.tanggal)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $row) {
        $key = $row["tahun"] . "-" . $row["bulan"];
        $map[$key] = (int) $row["total"];
    }

    $data = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $d = (clone $anchorDate)->modify("-$i months");
        $tahun = (int) $d->format("Y");
        $bulanAngka = (int) $d->format("n");
        $key = $tahun . "-" . $bulanAngka;

        $data[] = [
            "tahun" => $tahun,
            "bulanAngka" => $bulanAngka,
            "bulan" => $monthNames[$bulanAngka],
            "total" => isset($map[$key]) ? (int) $map[$key] : 0
        ];
    }

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Gagal mengambil distribusi bulanan",
        "error" => $e->getMessage()
    ]);
}