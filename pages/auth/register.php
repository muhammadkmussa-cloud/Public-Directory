<?php
/**
 * Register Page - Umma Directory
 */

$db = Database::getInstance();
$auth = new Auth($db);

$error = '';
$success = '';
$errors = [];

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        try {
            $userId = $auth->register([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'phone' => $phone,
                'city' => $city
            ]);
            
            if ($userId) {
                // Auto-login after registration
                $auth->login($email, $password);
                header('Location: /dashboard?welcome=1');
                exit;
            } else {
                $error = 'Registration failed. Email may already be in use.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$pageTitle = 'Create Account';
include BASE_PATH . '/pages/layouts/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Join Umma Directory</h1>
            <p>Create your free account today</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <?= $error ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="/register" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
            
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" required 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       placeholder="Your full name" autocomplete="name">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="your@email.com" autocomplete="email">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                           placeholder="+254 7XX XXX XXX" autocomplete="tel">
                </div>
                
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" 
                           value="<?= htmlspecialchars($_POST['city'] ?? '') ?>"
                           placeholder="Nairobi, Mombasa, etc." autocomplete="address-level2">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required 
                       minlength="6"
                       placeholder="At least 6 characters" autocomplete="new-password">
                <small>Must be at least 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirm Password *</label>
                <input type="password" id="password_confirm" name="password_confirm" required 
                       placeholder="Re-enter password" autocomplete="new-password">
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" required>
                    <span>I agree to the <a href="/terms" target="_blank">Terms of Service</a> and <a href="/privacy" target="_blank">Privacy Policy</a></span>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="/login">Login</a></p>
        </div>
        
        <div class="benefits-list">
            <h4>Why join?</h4>
            <ul>
                <li>✓ Write reviews and help the community</li>
                <li>✓ Save favorite businesses and mosques</li>
                <li>✓ Claim your business listing</li>
                <li>✓ Get personalized recommendations</li>
                <li>✓ Track your contributions and badges</li>
            </ul>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/pages/layouts/footer.php'; ?>
