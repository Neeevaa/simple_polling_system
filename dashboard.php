<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Get all polls from the database
$polls_query = $conn->query("SELECT id, question FROM polls ORDER BY created_at DESC");

// Get a list of polls the user has already voted in
$voted_polls_query = $conn->prepare("SELECT poll_id FROM poll_votes WHERE user_id = ?");
$voted_polls_query->bind_param("i", $user_id);
$voted_polls_query->execute();
$result = $voted_polls_query->get_result();
$voted_polls = [];
while ($row = $result->fetch_assoc()) {
    $voted_polls[] = $row['poll_id'];
}
$voted_polls_query->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Polling System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 700px;">
        <div class="dashboard-header">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</span>
            <div>
                <a href="all_results.php" style="margin-right: 10px;">All Polls</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
        
        <h2>Available Polls</h2>
        <div class="poll-list">
            <?php if ($polls_query->num_rows > 0): ?>
                <?php while ($poll = $polls_query->fetch_assoc()): ?>
                    <div class="poll-item">
                        <div class="poll-question">
                            <?php echo htmlspecialchars($poll['question']); ?>
                        </div>
                        <div class="poll-actions">
                            <?php if (in_array($poll['id'], $voted_polls)): ?>
                                <span class="voted-status">✅ Voted</span>
                            <?php endif; ?>
                            <a href="poll_view.php?id=<?php echo $poll['id']; ?>" class="btn-view">View & Vote</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No polls available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>