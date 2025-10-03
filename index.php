<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zwicky License Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .container { background: white; padding: 50px; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); text-align: center; max-width: 500px; }
        h1 { color: #333; margin-bottom: 20px; font-size: 28px; }
        p { color: #666; margin-bottom: 30px; line-height: 1.6; }
        .btn { display: inline-block; padding: 15px 30px; margin: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; transition: transform 0.2s; font-weight: 500; }
        .btn:hover { transform: translateY(-2px); }
        .btn-secondary { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .status { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ Zwicky License Management System</h1>
        <p>Welcome to your local development environment!</p>
        
        <div class="status">
            <strong>✅ Server Status:</strong> Running on PHP <?php echo phpversion(); ?><br>
            <strong>✅ Database:</strong> MySQL Connected<br>
            <strong>✅ Environment:</strong> Local Development
        </div>
        
        <div>
            <a href="install.php" class="btn">🔧 Run Installation</a>
            <a href="admin/login_simple.php" class="btn btn-secondary">🔐 Admin Login</a>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="admin/user_manager.php" class="btn" style="background: #ffc107; color: #000;">👥 User Manager</a>
            <a href="admin/diagnostic.php" class="btn" style="background: #17a2b8;">🔍 Diagnostics</a>
        </div>
        
        <p style="margin-top: 30px; font-size: 12px; color: #999;">
            Default Login: admin / admin123
        </p>
    </div>
</body>
</html>