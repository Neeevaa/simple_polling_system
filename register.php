<?php
require 'db.php';
$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($username) || strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = "Username must be between 3 and 20 characters.";
    }
    elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores (no spaces).";
    }

    if (empty($password) || strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "This username is already taken.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $insert_stmt->bind_param("ss", $username, $hashed_password);

            if ($insert_stmt->execute()) {
                $success_message = "Registration successful! You can now login.";
            } else {
                $errors[] = "An error occurred during registration. Please try again.";
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Create an Account</h1>
        <?php if(!empty($errors)): ?>
            <div class="message error"><ul><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <?php if(!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php else: ?>
        <form action="register.php" method="post" id="register-form" novalidate>
            <div class="form-group"><label for="username">Username</label><input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"><span class="validation-error" id="username-error"></span></div>
            <div class="form-group"><label for="password">Password (min. 8 characters)</label><input type="password" id="password" name="password" required><span class="validation-error" id="password-error"></span></div>
            <div class="form-group"><label for="confirm_password">Confirm Password</label><input type="password" id="confirm_password" name="confirm_password" required><span class="validation-error" id="confirm-password-error"></span></div>
            <button type="submit" class="btn">Register</button>
        </form>
        <?php endif; ?>
        <p style="margin-top: 20px;">Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
<script>
document.getElementById('register-form').addEventListener('submit', function(e) {
    let isValid = true;
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    document.getElementById('username-error').textContent = '';
    document.getElementById('password-error').textContent = '';
    document.getElementById('confirm-password-error').textContent = '';
    if (username.value.trim() !== username.value) {
        document.getElementById('username-error').textContent = 'Username cannot have leading/trailing spaces.';
        isValid = false;
    } else if (username.value.length < 3 || username.value.length > 20) {
        document.getElementById('username-error').textContent = 'Username must be 3-20 characters.';
        isValid = false;
    } else if (/\s/.test(username.value)) {
        document.getElementById('username-error').textContent = 'Username cannot contain spaces.';
        isValid = false;
    }
    if (password.value.length < 8) {
        document.getElementById('password-error').textContent = 'Password must be at least 8 characters.';
        isValid = false;
    }
    if (password.value !== confirmPassword.value) {
        document.getElementById('confirm-password-error').textContent = 'Passwords do not match.';
        isValid = false;
    }
    if (!isValid) {
        e.preventDefault();
    }
});
</script>
</body>
</html>