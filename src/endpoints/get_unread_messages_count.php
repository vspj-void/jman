<?php
require_once "includes/db_connect.php";
$mysqli = DbConnect::connect();

function getUnreadMessagesCount() {
    global $mysqli;
    $loggedUser = SessionInfo::getLoggedOsoba();
    $userId = $loggedUser->getId();
    $query = "SELECT COUNT(*) AS unreadCount FROM ZPRAVY WHERE KOMU = ? AND PRECTENO = 0";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['unreadCount'];
}
?>
