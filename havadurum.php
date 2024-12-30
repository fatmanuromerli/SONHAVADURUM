<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$host = "localhost";
$user = "root";
$password = "";
$dbName = "cities";

try {
    $dsn = "mysql:host=" . $host . ";dbname=" . $dbName;
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = $_POST['city'] ?? '';
        $operation = $_POST['operation'] ?? '';
        $id = $_POST['id'] ?? '';


        if ($operation === 'sehir') {

            $stmt = $pdo->prepare("SELECT * FROM sehirler WHERE name LIKE :name");
            $searchTerm = '%' . $name . '%';
            $stmt->bindParam(':name', $searchTerm);
            $stmt->execute();


            $cities = $stmt->fetchAll();


            echo json_encode(['data' => $cities, 'status' => '200']);
        } else if ($operation === 'favori') {

            $cityName = $_POST['city'];
            $stmt = $pdo->prepare("SELECT id FROM sehirler WHERE name = :name");
            $stmt->bindParam(':name', $cityName);
            $stmt->execute();
            $cityId = $stmt->fetchColumn();

            if ($cityId) {

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM favsehir WHERE u_cityID = :u_cityID");
                $stmt->bindParam(':u_cityID', $cityId);
                $stmt->execute();
                $isFavorite = $stmt->fetchColumn();

                if ($isFavorite > 0) {

                    echo json_encode(['message' => 'Bu şehir zaten favori listenizde', 'status' => '202']);
                } else {

                    $stmt = $pdo->prepare("INSERT INTO favsehir (u_cityID) VALUES (:u_cityID)");
                    $stmt->bindParam(':u_cityID', $cityId);
                    $stmt->execute();

                    echo json_encode(['data' => $cityId, 'status' => '200']);
                }
            } else {
                echo json_encode(['message' => 'Şehir bulunamadı', 'status' => '404']);
            }
        } else if ($operation === 'favorigetir') {

            $stmt = $pdo->query("SELECT COUNT(*) FROM favsehir");
            $count = $stmt->fetchColumn();

            if ($count > 0) {

                $stmt = $pdo->prepare("SELECT sehirler.name FROM favsehir INNER JOIN sehirler ON favsehir.u_cityID = sehirler.id");
                $stmt->execute();
                $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['data' => $favorites, 'status' => '200']);
            } else {

                echo json_encode(['status' => 'no_favorites']);
            }
        } else if ($operation === 'check_fav_empty') {

            $stmt = $pdo->query("SELECT COUNT(*) FROM favsehir");
            $count = $stmt->fetchColumn();

            if ($count > 0) {

                echo json_encode(['status' => 'has_favorites']);
            } else {

                echo json_encode(['status' => 'no_favorites']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'sehir adi eksik veya baska bi hata']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gecersiz islem- HER SEY YANLIS!']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Bağlantı hatası: ' . $e->getMessage()]);
}
