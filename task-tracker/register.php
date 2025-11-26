<?php
include 'config.php';

if ($_POST) {
    $username = cleanInput($_POST['username']);
    $firstname = cleanInput($_POST['firstname']);
    $lastname = cleanInput($_POST['lastname']);
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, firstname, lastname, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $firstname, $lastname, $password_hash]);
        
        $_SESSION['success'] = "Account created! Please login.";
        header("Location: login.php");
        exit();
        
    } catch(PDOException $e) {
        $error = "Username already exists";
    }
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Task Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h2>Create account</h2>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="text" name="username" class="form-input" placeholder="Username" required>
                <input type="text" name="firstname" class="form-input" placeholder="First Name" required>
                <input type="text" name="lastname" class="form-input" placeholder="Last Name" required>
                <input type="password" name="password" class="form-input" placeholder="Password" required>
                <button type="submit" class="btn btn-success">Register</button>
            </form>
            
            <p style="margin-top: 15px; text-align: center;">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>