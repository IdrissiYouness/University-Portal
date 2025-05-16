<?php
    require_once '../config/db.php';

    $pdo = getDbConnection();


    function countStudents($search = '', $status = 'all') {
        $pdo = getDbConnection();

        $sql = "SELECT COUNT(*) AS total FROM etudiant e";
        $params = [];


        $conditions = [];


        if (!empty($search)) {
            $conditions[] = "(e.nom LIKE :search OR e.prenom LIKE :search OR e.email LIKE :search OR e.cin LIKE :search OR e.massar LIKE :search)";
            $params[':search'] = "%$search%";
        }


        if ($status != 'all') {
            $conditions[] = "e.status = :status";
            $params[':status'] = $status;
        }


        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch();
        return $result['total'];
    }

    function getStudents($page = 1, $limit = 10, $search = '', $status = 'all') {
        $pdo = getDbConnection();


        $offset = ($page - 1) * $limit;


        $sql = "
            SELECT
                e.*,
                v.nom AS ville_nom,
                a.nom AS academie_nom
            FROM
                etudiant e
            LEFT JOIN
                ville v ON e.ville_id = v.id
            LEFT JOIN
                academie a ON e.academie_id = a.id
        ";

        $params = [];


        $conditions = [];

        if (!empty($search)) {
            $conditions[] = "(e.nom LIKE :search OR e.prenom LIKE :search OR e.email LIKE :search OR e.cin LIKE :search OR e.massar LIKE :search)";
            $params[':search'] = "%$search%";
        }


        if ($status != 'all') {
            $conditions[] = "e.status = :status";
            $params[':status'] = $status;
        }


        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }


        $sql .= " ORDER BY e.date_inscription DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = $pdo->prepare($sql);

        // PDO::PARAM_INT needs to be explicitly set for limit and offset
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        // Bind other parameters
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $stmt->bindValue($key, $value);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }


    function getStudentById($studentId) {
        $pdo = getDbConnection();

        $sql = "
            SELECT
                e.*,
                v.nom AS ville_nom,
                a.nom AS academie_nom
            FROM
                etudiant e
            LEFT JOIN
                ville v ON e.ville_id = v.id
            LEFT JOIN
                academie a ON e.academie_id = a.id
            WHERE
                e.id = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $studentId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    function updateStudentStatus($studentId, $newStatus) {
        $pdo = getDbConnection();


        if (!in_array($newStatus, ['approved', 'rejected', 'pending'])) {
            return false;
        }

        $sql = "UPDATE etudiant SET status = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':id', $studentId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    //Statistics functions

    function getAveragesByCity() {
        global $pdo;
        $query = "SELECT v.nom as city_name,
                        AVG(e.moyenne_nationale) as avg_national,
                        AVG(e.moyenne_regionale) as avg_regional,
                        COUNT(e.id) as student_count
                FROM etudiant e
                JOIN ville v ON e.ville_id = v.id
                GROUP BY v.nom
                ORDER BY student_count DESC
                LIMIT 10";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function getAveragesByAcademy() {
        global $pdo;
        $query = "SELECT a.nom as academy_name,
                        AVG(e.moyenne_nationale) as avg_national,
                        AVG(e.moyenne_regionale) as avg_regional,
                        COUNT(e.id) as student_count
                FROM etudiant e
                JOIN academie a ON e.academie_id = a.id
                GROUP BY a.nom
                ORDER BY student_count DESC
                LIMIT 10";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function getStatusCounts() {
        global $pdo;
        $query = "SELECT status, COUNT(*) as count
                FROM etudiant
                GROUP BY status";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function getRegistrationsByDate() {
        global $pdo;
        $query = "SELECT DATE(date_inscription) as date, COUNT(*) as count
                FROM etudiant
                GROUP BY DATE(date_inscription)
                ORDER BY date
                LIMIT 30";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function getAveragesBySubject() {
        global $pdo;
        $query = "SELECT m.nom as subject_name, AVG(n.valeur) as avg_score
                FROM note n
                JOIN matiere m ON n.matiere_id = m.id
                GROUP BY m.nom
                ORDER BY avg_score DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function getScoreDistribution() {
        global $pdo;
        $query = "SELECT
                    CASE
                        WHEN moyenne_nationale < 10 THEN 'Below 10'
                        WHEN moyenne_nationale BETWEEN 10 AND 12 THEN '10-12'
                        WHEN moyenne_nationale BETWEEN 12 AND 14 THEN '12-14'
                        WHEN moyenne_nationale BETWEEN 14 AND 16 THEN '14-16'
                        WHEN moyenne_nationale BETWEEN 16 AND 18 THEN '16-18'
                        ELSE 'Above 18'
                    END as score_range,
                    COUNT(*) as count
                FROM etudiant
                GROUP BY score_range
                ORDER BY CASE
                        WHEN score_range = 'Below 10' THEN 1
                        WHEN score_range = '10-12' THEN 2
                        WHEN score_range = '12-14' THEN 3
                        WHEN score_range = '14-16' THEN 4
                        WHEN score_range = '16-18' THEN 5
                        ELSE 6
                    END";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
