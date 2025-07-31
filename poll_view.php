<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$poll_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$poll_stmt = $conn->prepare("SELECT question FROM polls WHERE id = ?");
$poll_stmt->bind_param("i", $poll_id);
$poll_stmt->execute();
$poll_result = $poll_stmt->get_result();
if ($poll_result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}
$poll = $poll_result->fetch_assoc();
$poll_question = $poll['question'];
$poll_stmt->close();

$poll_options = [];
switch ($poll_id) {
    case 1: $poll_options = ['Python', 'JavaScript', 'PHP', 'Java', 'C#', 'Rust']; break;
    case 2: $poll_options = ['Windows', 'macOS', 'Linux', 'ChromeOS', 'Other']; break;
    case 3: $poll_options = ['Instagram', 'Facebook', 'X (Twitter)', 'TikTok', 'LinkedIn']; break;
    default: $poll_options = [];
}

$vote_check_stmt = $conn->prepare("SELECT id FROM poll_votes WHERE user_id = ? AND poll_id = ?");
$vote_check_stmt->bind_param("ii", $user_id, $poll_id);
$vote_check_stmt->execute();
$has_voted = $vote_check_stmt->get_result()->num_rows > 0;
$vote_check_stmt->close();

$results_stmt = $conn->prepare("SELECT choice, COUNT(id) as vote_count FROM poll_votes WHERE poll_id = ? GROUP BY choice");
$results_stmt->bind_param("i", $poll_id);
$results_stmt->execute();
$results_result = $results_stmt->get_result();
$poll_results = [];
$total_votes = 0;
while($row = $results_result->fetch_assoc()) {
    $poll_results[$row['choice']] = $row['vote_count'];
    $total_votes += $row['vote_count'];
}
$results_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Poll</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" style="display: block; margin-bottom: 20px;">&larr; Back to Dashboard</a>
        <div id="poll-container">
            <h2><?php echo htmlspecialchars($poll_question); ?></h2>
            <div id="feedback-message"></div>
            <?php if ($has_voted): ?>
                <div id="results-section">
                    <h3>You have already voted. Here are the current results:</h3>
                    <p>Total Votes: <?php echo $total_votes; ?></p>
                    <?php foreach ($poll_options as $option):
                        $votes = $poll_results[$option] ?? 0;
                        $percentage = ($total_votes > 0) ? round(($votes / $total_votes) * 100, 2) : 0;
                    ?>
                    <div class="poll-option"><span><?php echo htmlspecialchars($option); ?> (<?php echo $votes; ?> votes)</span></div>
                    <div class="result-bar-container"><div class="result-bar" style="width: <?php echo $percentage; ?>%;"><?php echo $percentage; ?>%</div></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form id="poll-form">
                    <input type="hidden" name="poll_id" value="<?php echo $poll_id; ?>">
                    <div id="poll-section">
                        <?php foreach ($poll_options as $option): ?>
                        <div class="poll-option"><label><input type="radio" name="vote" value="<?php echo $option; ?>" required> <?php echo htmlspecialchars($option); ?></label></div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn">Submit Vote</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<script>
$(document).ready(function() {
    $('#poll-form').on('submit', function(e) {
        e.preventDefault();
        let choice = $('input[name="vote"]:checked').val();
        let pollId = $('input[name="poll_id"]').val();
        if (!choice) {
            $('#feedback-message').html('<div class="message error">Please select an option.</div>');
            return;
        }
        $.ajax({
            type: 'POST',
            url: 'vote.php',
            data: { vote: choice, poll_id: pollId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#poll-container').html(response.html);
                } else {
                    $('#feedback-message').html('<div class="message error">' + response.message + '</div>');
                }
            }
        });
    });
});
</script>
</body>
</html>