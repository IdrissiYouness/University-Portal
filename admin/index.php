<?php
session_start();


    if (isset($_SESSION['role'])) {
        $adminData = $_SESSION['role'];
    } else {
        echo "No admin data found in session. Please log in.";
    }

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../");
        exit();
    }

    require_once '../includes/db_helpers.php';
    require_once '../includes/update_status.php';


    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;


    $limit = 10;


    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';


    $students = getStudents($page, $limit, $search, $status);


    $totalStudents = countStudents($search, $status);
    $totalPages = ceil($totalStudents / $limit);


    $statusLabels = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected'
    ];

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <title>Another</title>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <?php
            include 'sidebar.php';
            ?>
        </div>
        <div class="content">
            <div class="content-header">
                <h1>Student Inscriptions</h1>
                <p>Manage student inscription records | Total: <?php echo $totalStudents; ?></p>
            </div>

             <div class="table-controls">
                <form action="" method="GET" class="search-filter-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                    </div>
                    <div class="filter-controls">
                        <select name="status" onchange="this.form.submit()">
                            <option value="all" <?php echo ($status == 'all') ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo ($status == 'approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo ($status == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                </form>
                <a href="../includes/exportcsv.php<?php

                        $params = [];


                        if (isset($_GET['search'])) $params['search'] = $_GET['search'];


                        $params['status'] = isset($_GET['status']) ? $_GET['status'] : 'all';

                        echo !empty($params) ? '?' . http_build_query($params) : '';
                    ?>" class="btn btn-export">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>cloud-download</title><path d="M6.5 20Q4.22 20 2.61 18.43 1 16.85 1 14.58 1 12.63 2.17 11.1 3.35 9.57 5.25 9.15 5.83 7.13 7.39 5.75 8.95 4.38 11 4.08V12.15L9.4 10.6L8 12L12 16L16 12L14.6 10.6L13 12.15V4.08Q15.58 4.43 17.29 6.39 19 8.35 19 11 20.73 11.2 21.86 12.5 23 13.78 23 15.5 23 17.38 21.69 18.69 20.38 20 18.5 20Z" /></svg>
                        Export
                </a>
               </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>CIN</th>
                            <th>Massar</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>City</th>
                            <th>Academy</th>
                            <th>National Avg</th>
                            <th>Regional Avg</th>
                            <th>Inscription Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="12" class="no-results">No student records found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $student['id']; ?></td>
                                <td><?php echo htmlspecialchars($student['cin']); ?></td>
                                <td><?php echo htmlspecialchars($student['massar']); ?></td>
                                <td><?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['ville_nom']); ?></td>
                                <td><?php echo htmlspecialchars($student['academie_nom']); ?></td>
                                <td><?php echo number_format($student['moyenne_nationale'], 2); ?></td>
                                <td><?php echo number_format($student['moyenne_regionale'], 2); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($student['date_inscription'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $student['status']; ?>">
                                        <?php echo $statusLabels[$student['status']]; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <?php if ($student['status'] == 'pending'): ?>
                                        <button class="btn-action approve" onclick="updateStatus(<?php echo $student['id']; ?>, 'approved')">Approve</button>
                                        <button class="btn-action reject" onclick="updateStatus(<?php echo $student['id']; ?>, 'rejected')">Reject</button>
                                    <?php elseif ($student['status'] == 'approved'): ?>
                                        <button class="btn-action view" onclick="viewStudent(<?php echo $student['id']; ?>)">View</button>
                                        <button class="btn-action reject" onclick="updateStatus(<?php echo $student['id']; ?>, 'rejected')">Reject</button>
                                    <?php elseif ($student['status'] == 'rejected'): ?>
                                        <button class="btn-action view" onclick="viewStudent(<?php echo $student['id']; ?>)">View</button>
                                        <button class="btn-action approve" onclick="updateStatus(<?php echo $student['id']; ?>, 'approved')">Approve</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>


            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" class="pagination-btn">Previous</a>
                <?php else: ?>
                <span class="pagination-btn disabled">Previous</span>
                <?php endif; ?>

                <div class="pagination-numbers">
                    <?php

                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);


                    if ($startPage > 1) {
                        echo '<a href="?page=1&search=' . urlencode($search) . '&status=' . $status . '" class="' . (1 == $page ? 'active' : '') . '">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                    }


                    for ($i = $startPage; $i <= $endPage; $i++) {
                        echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '&status=' . $status . '" class="pagination-btn ' . ($i == $page ? 'active' : '') . '">' . $i . '</a>';
                    }


                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                        echo '<a href="?page=' . $totalPages . '&search=' . urlencode($search) . '&status=' . $status . '" class="' . ($totalPages == $page ? 'active' : '') . '">' . $totalPages . '</a>';
                    }
                    ?>
                </div>

                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" class="pagination-btn">Next</a>
                <?php else: ?>
                <span class="pagination-btn disabled">Next</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <script>

        function updateStatus(studentId, status) {
            if (confirm('Are you sure you want to set this student\'s status to ' + status + '?')) {

                const formData = new FormData();
                formData.append('student_id', studentId);
                formData.append('status', status);


                fetch('../includes/update_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);

                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the status');
                });
            }
        }


        function viewStudent(studentId) {
            window.location.href = 'view_student.php?id=' + studentId;
        }
    </script>
</body>
</html>