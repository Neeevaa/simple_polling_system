<?php
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to vote.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vote']) && isset($_POST['poll_id'])) {
    $user_id = $_SESSION['user_id'];
    $poll_id = (int)$_POST['poll_id'];
    $choice = $_POST['vote'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $stmt_check = $conn->prepare("SELECT id FROM poll_votes WHERE user_id = ? AND poll_id = ?");
    $stmt_check->bind_param("ii", $user_id, $poll_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already voted in this poll.']);
        exit();
    }

    $stmt_insert = $conn->prepare("INSERT INTO poll_votes (user_id, poll_id, choice, ip_address) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("iiss", $user_id, $poll_id, $choice, $ip_address);
    
    if ($stmt_insert->execute()) {
        $poll_options = [];
        switch ($poll_id) {
            case 1: $poll_options = ['Python', 'JavaScript', 'PHP', 'Java', 'C#', 'Rust']; break;
            case 2: $poll_options = ['Windows', 'macOS', 'Linux', 'ChromeOS', 'Other']; break;
            case 3: $poll_options = ['Instagram', 'Facebook', 'X (Twitter)', 'TikTok', 'LinkedIn']; break;
        }

        $results_stmt = $conn->prepare("SELECT choice, COUNT(id) as vote_count FROM poll_votes WHERE poll_id = ? GROUP BY choice");
        $results_stmt->bind_param("i", $poll_id);
        $results_stmt->execute();
        $results = $results_stmt->get_result();
        $poll_results = [];
        $total_votes = 0;
        while($row = $results->fetch_assoc()) {
            $poll_results[$row['choice']] = $row['vote_count'];
            $total_votes += $row['vote_count'];
        }
        
        ob_start();
        ?>
        <h3>Thank you for voting! Here are the current results:</h3>
        <p>Total Votes: <?php echo $total_votes; ?></p>
        <?php foreach ($poll_options as $option):
            $votes = $poll_results[$option] ?? 0;
            $percentage = ($total_votes > 0) ? round(($votes / $total_votes) * 100, 2) : 0;
        ?>
        <div class="poll-option"><span><?php echo htmlspecialchars($option); ?> (<?php echo $votes; ?> votes)</span></div>
        <div class="result-bar-container"><div class="result-bar" style="width: <?php echo $percentage; ?>%;"><?php echo $percentage; ?>%</div></div>
        <?php endforeach; ?>
        <?php
        $html = ob_get_clean();
        echo json_encode(['success' => true, 'html' => $html]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record your vote.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
$conn->close();
?>