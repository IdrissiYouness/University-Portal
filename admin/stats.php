<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: ../");
    exit();
}

require_once '../includes/db_helpers.php';
require_once '../config/db.php';

$pdo = getDbConnection();




$cityData = getAveragesByCity();
$academyData = getAveragesByAcademy();
$statusData = getStatusCounts();
$registrationData = getRegistrationsByDate();
$subjectData = getAveragesBySubject();
$scoreDistribution = getScoreDistribution();


// Prepare data for JSON encoding
$cityNames = [];
$cityNationalAvgs = [];
$cityRegionalAvgs = [];
$cityStudentCounts = [];

foreach ($cityData as $city) {
    $cityNames[] = $city['city_name'];
    $cityNationalAvgs[] = round($city['avg_national'], 2);
    $cityRegionalAvgs[] = round($city['avg_regional'], 2);
    $cityStudentCounts[] = $city['student_count'];
}

$academyNames = [];
$academyNationalAvgs = [];
$academyRegionalAvgs = [];
$academyStudentCounts = [];

foreach ($academyData as $academy) {
    $academyNames[] = $academy['academy_name'];
    $academyNationalAvgs[] = round($academy['avg_national'], 2);
    $academyRegionalAvgs[] = round($academy['avg_regional'], 2);
    $academyStudentCounts[] = $academy['student_count'];
}

$statusLabels = [];
$statusCounts = [];
$statusColors = [
    'pending' => '#ffeaa7',
    'approved' => '#d4f1e6',
    'rejected' => '#fad7d7'
];

foreach ($statusData as $status) {
    $statusLabels[] = ucfirst($status['status']);
    $statusCounts[] = $status['count'];
}

$registrationDates = [];
$registrationCounts = [];

foreach ($registrationData as $reg) {
    $registrationDates[] = $reg['date'];
    $registrationCounts[] = $reg['count'];
}

$subjectNames = [];
$subjectScores = [];
$subjectColors = ['#3498db', '#e67e22', '#2ecc71', '#9b59b6', '#e74c3c', '#f1c40f'];

foreach ($subjectData as $index => $subject) {
    $subjectNames[] = $subject['subject_name'];
    $subjectScores[] = round($subject['avg_score'], 2);
}

$scoreRanges = [];
$scoreCounts = [];
$scoreColors = ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#3498db', '#9b59b6'];

foreach ($scoreDistribution as $index => $score) {
    $scoreRanges[] = $score['score_range'];
    $scoreCounts[] = $score['count'];
}


$totalStudents = array_sum($statusCounts);


$approvalRate = 0;
foreach ($statusData as $status) {

    if ($status['status'] == 'approved') {
        $approvalRate = ($status['count'] / $totalStudents) * 100;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/stats.css">
    <title>Statistical Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <?php include 'sidebar.php'; ?>
        </div>
        <div class="content">
            <div class="stats-header">
                <h1>Statistical Dashboard</h1>
                <p>View comprehensive statistics for student inscriptions | Total: <?php echo $totalStudents; ?></p>
            </div>

            <div class="stats-summary">
                <?php
                $statusInfo = [
                    'pending' => ['icon' => '⏳', 'class' => 'warning'],
                    'approved' => ['icon' => '✓', 'class' => 'success'],
                    'rejected' => ['icon' => '✗', 'class' => 'danger']
                ];

                foreach ($statusData as $status):
                    $statusClass = $statusInfo[$status['status']]['class'] ?? '';
                    $statusIcon = $statusInfo[$status['status']]['icon'] ?? '';
                ?>
                <div class="summary-card">
                    <h2><?php echo $status['count']; ?></h2>
                    <p><?php echo ucfirst($status['status']); ?> Students</p>
                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusIcon; ?> <?php echo round(($status['count'] / $totalStudents) * 100); ?>%</span>
                </div>
                <?php endforeach; ?>



                <!-- Average National Score -->
                <div class="summary-card">
                    <h2><?php
                        $avgNational = 0;
                        foreach ($cityData as $city) {
                            $avgNational += $city['avg_national'] * $city['student_count'];
                        }
                        //show nothing if total students is 0
                        if($totalStudents == 0) {
                            echo 'The Student Table is Empty<br>';
                            echo 'No statistics to show';
                            exit();
                        }else{
                            echo round($avgNational / $totalStudents, 2);
                        }

                    ?></h2>
                    <p>Avg. National Score</p>
                </div>

            </div>

            <div class="tab-navigation">
                <button class="tab-button active" onclick="showTab('overview')">Overview</button>
                <button class="tab-button" onclick="showTab('score-analysis')">Score Analysis</button>
                <button class="tab-button" onclick="showTab('geographical')">Geographical</button>
                <button class="tab-button" onclick="showTab('time-analysis')">Time Analysis</button>
            </div>

            <!-- Overview Tab -->
            <div id="overview" class="tab-content active">
                <div class="stats-container">
                    <div class="stats-card">
                        <h3>Student Status Distribution</h3>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>

                    <div class="stats-card">
                        <h3>Score Distribution</h3>
                        <div class="chart-container">
                            <canvas id="scoreDistributionChart"></canvas>
                        </div>
                    </div>

                    <div class="stats-card">
                        <h3>Daily Registrations</h3>
                        <div class="chart-container">
                            <canvas id="registrationChart"></canvas>
                        </div>
                    </div>

                    <div class="stats-card">
                        <h3>Average Scores by Subject</h3>
                        <div class="chart-container">
                            <canvas id="subjectChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Score Analysis Tab -->
            <div id="score-analysis" class="tab-content">
                <div class="stats-container">
                    <div class="stats-card">
                        <h3>Score Distribution</h3>
                        <div class="chart-container">
                            <canvas id="scoreDistributionChart2"></canvas>
                        </div>
                        <div class="inline-stats">
                            <div>
                                <h3><?php
                                    $highScoreCount = 0;
                                    foreach ($scoreDistribution as $score) {
                                        if ($score['score_range'] == 'Above 18' || $score['score_range'] == '16-18') {
                                            $highScoreCount += $score['count'];
                                        }
                                    }
                                    echo round(($highScoreCount / $totalStudents) * 100) . '%';
                                ?></h3>
                                <p>High Performers<br/>(>16)</p>
                            </div>
                            <div>
                                <h3><?php
                                    $avgScoreCount = 0;
                                    foreach ($scoreDistribution as $score) {
                                        if ($score['score_range'] == '12-14' || $score['score_range'] == '14-16') {
                                            $avgScoreCount += $score['count'];
                                        }
                                    }
                                    echo round(($avgScoreCount / $totalStudents) * 100) . '%';
                                ?></h3>
                                <p>Average Performers<br/>(12-16)</p>
                            </div>
                            <div>
                                <h3><?php
                                    $lowScoreCount = 0;
                                    foreach ($scoreDistribution as $score) {
                                        if ($score['score_range'] == 'Below 10' || $score['score_range'] == '10-12') {
                                            $lowScoreCount += $score['count'];
                                        }
                                    }
                                    echo round(($lowScoreCount / $totalStudents) * 100) . '%';
                                ?></h3>
                                <p>Low Performers<br/>(<12)</p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card">
                        <h3>Average Scores by Subject</h3>
                        <div class="chart-container">
                            <canvas id="subjectChart2"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Geographical Tab -->
            <div id="geographical" class="tab-content">
                <div class="stats-container">
                    <div class="stats-card">
                        <h3>Average Scores by City</h3>
                        <div class="chart-container">
                            <canvas id="cityChart"></canvas>
                        </div>
                    </div>

                    <div class="stats-card">
                        <h3>Average Scores by Academy</h3>
                        <div class="chart-container">
                            <canvas id="academyChart"></canvas>
                        </div>
                    </div>

                    <div class="stats-card">
                        <h3>Student Distribution by City</h3>
                        <div class="chart-container">
                            <canvas id="cityDistributionChart"></canvas>
                        </div>
                    </div>

                    <div class="stats-card">
                        <h3>Student Distribution by Academy</h3>
                        <div class="chart-container">
                            <canvas id="academyDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Time Analysis Tab -->
            <div id="time-analysis" class="tab-content">
                <div class="stats-container">
                    <div class="stats-card">
                        <h3>Daily Registrations</h3>
                        <div class="chart-container">
                            <canvas id="registrationChart2"></canvas>
                        </div>
                    </div>

                    <div class="stats-card">
                        <h3>Registration Trend Analysis</h3>
                        <div class="chart-container">
                            <canvas id="registrationTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabId) {

            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });

            document.getElementById(tabId).classList.add('active');

            event.currentTarget.classList.add('active');
        }

        // Chart.js configuration
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.color = '#2c3e50';


        const chartColors = {
            blue: 'rgba(52, 152, 219, 0.7)',
            green: 'rgba(46, 204, 113, 0.7)',
            purple: 'rgba(155, 89, 182, 0.7)',
            orange: 'rgba(230, 126, 34, 0.7)',
            red: 'rgba(231, 76, 60, 0.7)',
            yellow: 'rgba(241, 196, 15, 0.7)',
            teal: 'rgba(22, 160, 133, 0.7)'
        };

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($statusLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($statusCounts); ?>,
                    backgroundColor: [
                        '#ffeaa7',  // pending
                        '#d4f1e6',  // approved
                        '#fad7d7'   // rejected
                    ],
                    borderColor: [
                        '#fdcb6e',
                        '#27ae60',
                        '#e74c3c'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // City Chart
        const cityCtx = document.getElementById('cityChart').getContext('2d');
        const cityChart = new Chart(cityCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($cityNames); ?>,
                datasets: [
                    {
                        label: 'National Average',
                        data: <?php echo json_encode($cityNationalAvgs); ?>,
                        backgroundColor: chartColors.blue,
                    },
                    {
                        label: 'Regional Average',
                        data: <?php echo json_encode($cityRegionalAvgs); ?>,
                        backgroundColor: chartColors.green,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            footer: function(tooltipItems) {
                                const index = tooltipItems[0].dataIndex;
                                return 'Students: ' + <?php echo json_encode($cityStudentCounts); ?>[index];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 10,
                        max: 20
                    }
                }
            }
        });

        // Academy Chart
        const academyCtx = document.getElementById('academyChart').getContext('2d');
        const academyChart = new Chart(academyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($academyNames); ?>,
                datasets: [
                    {
                        label: 'National Average',
                        data: <?php echo json_encode($academyNationalAvgs); ?>,
                        backgroundColor: chartColors.purple,
                    },
                    {
                        label: 'Regional Average',
                        data: <?php echo json_encode($academyRegionalAvgs); ?>,
                        backgroundColor: chartColors.orange,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            footer: function(tooltipItems) {
                                const index = tooltipItems[0].dataIndex;
                                return 'Students: ' + <?php echo json_encode($academyStudentCounts); ?>[index];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 10,
                        max: 20
                    }
                }
            }
        });

        // Registration Chart
        const registrationCtx = document.getElementById('registrationChart').getContext('2d');
        const registrationChart = new Chart(registrationCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($registrationDates); ?>,
                datasets: [{
                    label: 'Registrations',
                    data: <?php echo json_encode($registrationCounts); ?>,
                    fill: true,
                    backgroundColor: 'rgba(41, 128, 185, 0.1)',
                    borderColor: 'rgba(41, 128, 185, 1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Subject Chart
        const subjectCtx = document.getElementById('subjectChart').getContext('2d');
        const subjectChart = new Chart(subjectCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($subjectNames); ?>,
                datasets: [{
                    label: 'Average Score',
                    data: <?php echo json_encode($subjectScores); ?>,
                    backgroundColor: <?php echo json_encode($subjectColors); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 10,
                        max: 20
                    }
                }
            }
        });

        // Score Distribution Chart
        const scoreDistCtx = document.getElementById('scoreDistributionChart').getContext('2d');
        const scoreDistChart = new Chart(scoreDistCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($scoreRanges); ?>,
                datasets: [{
                    data: <?php echo json_encode($scoreCounts); ?>,
                    backgroundColor: <?php echo json_encode($scoreColors); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // City Distribution Chart
        const cityDistCtx = document.getElementById('cityDistributionChart').getContext('2d');
        const cityDistChart = new Chart(cityDistCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($cityNames); ?>,
                datasets: [{
                    data: <?php echo json_encode($cityStudentCounts); ?>,
                    backgroundColor: Object.values(chartColors),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Academy Distribution Chart
        const academyDistCtx = document.getElementById('academyDistributionChart').getContext('2d');
        const academyDistChart = new Chart(academyDistCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($academyNames); ?>,
                datasets: [{
                    data: <?php echo json_encode($academyStudentCounts); ?>,
                    backgroundColor: Object.values(chartColors),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Duplicate charts for other tabs
        const scoreDistCtx2 = document.getElementById('scoreDistributionChart2').getContext('2d');
        const scoreDistChart2 = new Chart(scoreDistCtx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($scoreRanges); ?>,
                datasets: [{
                    label: 'Number of Students',
                    data: <?php echo json_encode($scoreCounts); ?>,
                    backgroundColor: <?php echo json_encode($scoreColors); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Subject Chart 2
        const subjectCtx2 = document.getElementById('subjectChart2').getContext('2d');
        const subjectChart2 = new Chart(subjectCtx2, {
            type: 'radar',
            data: {
                labels: <?php echo json_encode($subjectNames); ?>,
                datasets: [{
                    label: 'Average Score',
                    data: <?php echo json_encode($subjectScores); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    r: {
                        beginAtZero: false,
                        min: 10,
                        max: 20
                    }
                }
            }
        });

        // Registration Chart 2
        const regCtx2 = document.getElementById('registrationChart2').getContext('2d');
        const regChart2 = new Chart(regCtx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($registrationDates); ?>,
                datasets: [{
                    label: 'Registrations',
                    data: <?php echo json_encode($registrationCounts); ?>,
                    backgroundColor: chartColors.teal,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Registration Trend Chart
        const trendCtx = document.getElementById('registrationTrendChart').getContext('2d');

        // Calculate 7-day moving average
        const movingAvg = [];
        for (let i = 0; i < <?php echo json_encode($registrationCounts); ?>.length; i++) {
            let sum = 0;
            let count = 0;

            // Look back up to 7 days
            for (let j = Math.max(0, i - 6); j <= i; j++) {
                sum += <?php echo json_encode($registrationCounts); ?>[j];
                count++;
            }

            movingAvg.push(sum / count);
        }

        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($registrationDates); ?>,
                datasets: [
                    {
                        label: 'Daily Registrations',
                        data: <?php echo json_encode($registrationCounts); ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1,
                        pointRadius: 2
                    },
                    {
                        label: '7-Day Moving Average',
                        data: movingAvg,
                        borderColor: 'rgba(192, 57, 43, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>