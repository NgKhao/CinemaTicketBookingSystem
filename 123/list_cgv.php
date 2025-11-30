<?php
session_start();
require_once 'config.php';

// Lấy danh sách rạp từ database
$stmt = $pdo->prepare("SELECT * FROM cinemas ORDER BY name");
$stmt->execute();
$cinemas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$userRole = $isLoggedIn ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CGV - Danh sách rạp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="ASST1.css" />
    <link rel="icon" href="./img/4.png" />
</head>

<body>
    <?php
    // Include header component chung
    include 'header_user.php';
    ?>

    <article>
        <div class="cinema">
            <h1>DANH SÁCH RẠP CGV</h1>
            <div class="cinema-grid">
                <?php foreach ($cinemas as $cinema): ?>
                    <div class="cinema-card">
                        <img src="<?php echo $cinema['image']; ?>" alt="<?php echo $cinema['name']; ?>"
                            style="width: 200px; height: 150px; object-fit: cover;" />
                        <h3><?php echo $cinema['name']; ?></h3>
                        <p><?php echo $cinema['address']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </article>
    <footer>
        <span>chăm sóc khách hàng</span>
    </footer>
    </div>
</body>

</html>