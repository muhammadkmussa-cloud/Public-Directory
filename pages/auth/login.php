<?php
/**
 * Login Page - Umma Directory
 */

$db = Database::getInstance();
$auth = new Auth($db);

$error = '';
$success = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            if ($auth->login($email, $password, $remember)) {
                // Redirect to intended page or dashboard
                $redirect = $_GET['redirect'] ?? '/dashboard';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Login';
include BASE_PATH . '/pages/layouts/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p>Login to access your account</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="/login" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="your@email.com" autocomplete="email">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password" autocomplete="current-password">
            </div>
            
            <div class="form-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1">
                    <span>Remember me</span>
                </label>
                <a href="/forgot-password" class="forgot-link">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="/register">Sign up</a></p>
        </div>
        
        <div class="divider">
            <span>OR</span>
        </div>
        
        <div class="social-login">
            <button class="btn btn-outline btn-social">
                <span class="social-icon">📧</span> Continue with Email
            </button>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/pages/layouts/footer.php'; ?>
