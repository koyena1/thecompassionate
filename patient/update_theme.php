<?php
session_start();
include '../config/db.php';

if (isset($_SESSION['user_id']) && isset($_POST['pref_dark'])) {
    $patient_id = $_SESSION['user_id'];
    $pref_dark = intval($_POST['pref_dark']);

    // Update Database
    $sql = "UPDATE patients SET pref_dark_mode = $pref_dark WHERE patient_id = '$patient_id'";
    if ($conn->query($sql)) {
        // Update Session so other pages see it immediately
        $_SESSION['pref_dark_mode'] = $pref_dark;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>