<?php
$host = "localhost";   // XAMPP için her zaman localhost
$user = "root";        // XAMPP varsayılan kullanıcı
$password = "";        // XAMPP varsayılan şifre boş
$dbname = "login_system";    // phpMyAdmin'de oluşturduğun DB adı

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>
