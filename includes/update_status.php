<?php
require_once 'db_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if (!in_array($status, ['approved', 'rejected', 'pending'])) {
        $response = [
            'success' => false,
            'message' => 'Invalid status provided'
        ];
        echo json_encode($response);
        exit;
    }

    $result = updateStudentStatus($studentId, $status);

    if ($result) {
        $response = [
            'success' => true,
            'message' => 'Student status updated successfully'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Failed to update student status'
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    header('HTTP/1.1 405 Method Not Allowed');
}