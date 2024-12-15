<?php
// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'documents';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

session_start(); // Start session to access logged-in user information

// Handle file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false];
    $uploadDir = 'uploads/';
    $table = $_POST['table']; // Get the table name from the request

    // Ensure the uploads directory exists
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $uploadedBy = $_SESSION['username']; // Get the username of the logged-in user

    foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
        $fileName = basename($_FILES['files']['name'][$index]);
        $fileType = $_FILES['files']['type'][$index];
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($tmpName, $filePath)) {
            $stmt = $conn->prepare("INSERT INTO $table (file_name, file_path, file_type, uploaded_at, uploaded_by) VALUES (?, ?, ?, NOW(), ?)");
            $stmt->bind_param('ssss', $fileName, $filePath, $fileType, $uploadedBy);
            $stmt->execute();
            $stmt->close();
            $response['success'] = true;
        }
    }

    echo json_encode($response);
    exit;
}

// Handle listing files with pagination
if (isset($_GET['list'])) {
    $limit = 10; // Number of files per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $table = $_GET['table']; // Get the table name from the request

    // Get total file count
    $role = $_SESSION['role']; // Get the role of the logged-in user
    $username = $_SESSION['username']; // Get the username of the logged-in user

    if ($role === 'admin') {
        $result = $conn->query("SELECT COUNT(*) AS total FROM $table");
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM $table WHERE uploaded_by = ? OR uploaded_by = 'admin' OR uploaded_by IN (SELECT username FROM users WHERE role = ?)");
        $stmt->bind_param('ss', $username, $role);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    $totalFiles = $result->fetch_assoc()['total'];

    // Get files for the current page
    if ($role === 'admin') {
        $stmt = $conn->prepare("SELECT file_name, file_path, file_type, uploaded_at, uploaded_by FROM $table ORDER BY uploaded_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $limit, $offset);
    } else {
        $stmt = $conn->prepare("SELECT file_name, file_path, file_type, uploaded_at, uploaded_by FROM $table WHERE uploaded_by = ? OR uploaded_by = 'admin' OR uploaded_by IN (SELECT username FROM users WHERE role = ?) ORDER BY uploaded_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('ssii', $username, $role, $limit, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $files = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Include pagination metadata
    echo json_encode([
        'files' => $files,
        'totalPages' => ceil($totalFiles / $limit),
        'currentPage' => $page
    ]);
    exit;
}

$conn->close();
?>