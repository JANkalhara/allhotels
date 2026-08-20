<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$q            = trim($_GET['q'] ?? '');
$district     = trim($_GET['district'] ?? '');
$functionType = (int) ($_GET['function_type'] ?? 0);
$guests       = (int) ($_GET['guests'] ?? 0);
$maxPrice     = (float) ($_GET['max_price'] ?? 0);

$sql = "
    SELECT h.*,
           (SELECT image_path FROM hotel_images WHERE hotel_id = h.id AND is_main = 1 LIMIT 1) AS main_image,
           (SELECT AVG(rating) FROM reviews WHERE hotel_id = h.id) AS avg_rating,
           (SELECT GROUP_CONCAT(ft.name SEPARATOR ',') FROM hotel_function_types hft
                JOIN function_types ft ON ft.id = hft.function_type_id WHERE hft.hotel_id = h.id) AS functions
    FROM hotels h
    WHERE h.status = 'approved'
";
$params = [];

if ($q !== '') {
    $sql .= " AND h.name LIKE ? ";
    $params[] = "%$q%";
}
if ($district !== '') {
    $sql .= " AND h.district = ? ";
    $params[] = $district;
}
if ($functionType > 0) {
    $sql .= " AND EXISTS (SELECT 1 FROM hotel_function_types hft WHERE hft.hotel_id = h.id AND hft.function_type_id = ?) ";
    $params[] = $functionType;
}
if ($guests > 0) {
    $sql .= " AND (h.max_guests = 0 OR h.max_guests >= ?) ";
    $params[] = $guests;
}
if ($maxPrice > 0) {
    $sql .= " AND h.starting_price <= ? ";
    $params[] = $maxPrice;
}

$sql .= " ORDER BY h.is_premium DESC, h.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hotels = $stmt->fetchAll();

echo json_encode(['hotels' => $hotels]);
