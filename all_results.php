<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$polls_query = $conn->query("SELECT id, question FROM polls ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Poll Results</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 700px;">
        <div class="dashboard-header">
             <h2>All Poll Results</h2>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
        <?php while($poll = $polls_query->fetch_assoc()): 
            $poll_id = $poll['id'];
            
            $poll_options = [];
            switch ($poll_id) {
                case 1: $poll_options = ['Python', 'JavaScript', 'PHP', 'Java', 'C#', 'Rust']; break;
                case 2: $poll_options = ['Windows', 'macOS', 'Linux', 'ChromeOS', 'Other']; break;
                case 3: $poll_options = ['Instagram', 'Facebook', 'X (Twitter)', 'TikTok', 'LinkedIn']; break;
            }

            $results_stmt = $conn->prepare("SELECT choice, COUNT(id) as vote_count FROM poll_votes WHERE poll_id = ? GROUP BY choice ORDER BY vote_count DESC");
            $results_stmt->bind_param("i", $poll_id);
            $results_stmt->execute();
            $result = $results_stmt->get_result();
            
            $poll_results = [];
            $total_votes = 0;
            while($row = $result->fetch_assoc()){
                $poll_results[$row['choice']] = $row['vote_count'];
                $total_votes += $row['vote_count'];
            }
        ?>
            <div class="results-container">
                <h3><?php echo htmlspecialchars($poll['question']); ?></h3>
                <p><strong>Total Votes Cast: <?php echo $total_votes; ?></strong></p>
                <br>
                <?php
                foreach ($poll_options as $option) {
                    if (!isset($poll_results[$option])) {
                        $poll_results[$option] = 0;
                    }
                }
                arsort($poll_results);
                foreach ($poll_results as $option => $votes):
                    $percentage = ($total_votes > 0) ? round(($votes / $total_votes) * 100, 2) : 0;
                ?>
                <div class="poll-option"><span><?php echo htmlspecialchars($option); ?></span><strong><?php echo $votes; ?> votes</strong></div>
                <div class="result-bar-container"><div class="result-bar" style="width: <?php echo $percentage; ?>%;"><?php echo $percentage; ?>%</div></div>
                <?php endforeach; ?>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>