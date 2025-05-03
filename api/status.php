<?php
require_once '../db/pdo_conn.php';

function setUserStatus($userId, $status) {
    $conn = getPDOConnection('weevibes');
    $sql = "UPDATE member SET status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$status, $userId]);
}

if (isset($_POST['username']) && !isset($_POST['logout_on_close'])) {
    $username = $_POST['username'];
    $conn = getPDOConnection('weevibes');
    $sql = "SELECT id FROM member WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$username]);
    $fetch = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fetch) {
        setUserStatus($fetch['id'], 'active');
        echo "Status updated";
    } else {
        http_response_code(400); 
        echo "User not found";
    }
} elseif (isset($_SESSION['user']) && isset($_POST['logout_status'])) {
    setUserStatus($_SESSION['user'], 'not active');
    echo "Status set to not active"; 
} elseif (isset($_POST['username']) && isset($_POST['logout_on_close']) && $_POST['logout_on_close'] === 'true') {
    $username = $_POST['username'];
    $conn = getPDOConnection('weevibes');
    $sql = "SELECT id FROM member WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$username]);
    $fetch = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fetch) {
        setUserStatus($fetch['id'], 'not active');
    } else {
        http_response_code(400); 
    }
}
?>