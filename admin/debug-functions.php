<?php
/**
 * Debug Function Test Page
 */
require_once '../config/config.php';

$auth = new LMSAdminAuth();
if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Functions - License Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-suspend { background: #f59e0b; color: white; }
        .btn-reactivate { background: #10b981; color: white; }
        .btn-revoke { background: #ef4444; color: white; }
        #console-output {
            background: #1e293b;
            color: #10b981;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 10px;
        }
        .console-line {
            margin: 5px 0;
            padding: 3px 0;
            border-bottom: 1px solid #334155;
        }
        .console-error { color: #ef4444; }
        .console-success { color: #10b981; }
        .console-info { color: #3b82f6; }
    </style>
</head>
<body>
    <h1>🔍 License Manager Function Debugger</h1>
    
    <div class="test-section">
        <h2>Function Availability Test</h2>
        <button onclick="testFunctions()">Test All Functions</button>
        <div id="function-test-result"></div>
    </div>

    <div class="test-section">
        <h2>Direct Function Calls (License ID: 2)</h2>
        <button class="btn-suspend" onclick="testSuspend()">Test Suspend (ID: 2)</button>
        <button class="btn-reactivate" onclick="testReactivate()">Test Reactivate (ID: 2)</button>
        <button class="btn-revoke" onclick="testRevoke()">Test Revoke (ID: 2)</button>
    </div>

    <div class="test-section">
        <h2>Console Output</h2>
        <button onclick="clearConsole()">Clear Console</button>
        <div id="console-output"></div>
    </div>

    <script src="../assets/js/license-manager.js?v=<?php echo time(); ?>"></script>
    <script>
        // Override console methods to capture output
        const consoleOutput = document.getElementById('console-output');
        const originalConsoleLog = console.log;
        const originalConsoleError = console.error;
        const originalConsoleWarn = console.warn;

        function addToConsole(message, type = 'info') {
            const line = document.createElement('div');
            line.className = 'console-line console-' + type;
            line.textContent = new Date().toLocaleTimeString() + ' - ' + message;
            consoleOutput.appendChild(line);
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }

        console.log = function(...args) {
            originalConsoleLog.apply(console, args);
            addToConsole(args.join(' '), 'info');
        };

        console.error = function(...args) {
            originalConsoleError.apply(console, args);
            addToConsole(args.join(' '), 'error');
        };

        console.warn = function(...args) {
            originalConsoleWarn.apply(console, args);
            addToConsole(args.join(' '), 'info');
        };

        function clearConsole() {
            consoleOutput.innerHTML = '';
        }

        function testFunctions() {
            const result = document.getElementById('function-test-result');
            const functions = [
                'suspendLicense',
                'reactivateLicense',
                'revokeLicense',
                'editLicense',
                'viewLicenseDetails',
                'extendLicense',
                'regenerateKey',
                'toggleDropdown'
            ];

            let html = '<h3>Function Check Results:</h3><ul>';
            
            functions.forEach(funcName => {
                const exists = typeof window[funcName] === 'function';
                const color = exists ? 'green' : 'red';
                const status = exists ? '✅ Available' : '❌ Missing';
                html += `<li style="color: ${color}"><strong>${funcName}:</strong> ${status}</li>`;
                console.log(`Function ${funcName}: ${exists ? 'Available' : 'Missing'}`);
            });

            html += '</ul>';
            result.innerHTML = html;
        }

        function testSuspend() {
            console.log('=== Testing suspendLicense(2) ===');
            if (typeof window.suspendLicense === 'function') {
                window.suspendLicense(2);
            } else {
                console.error('suspendLicense function not found!');
                alert('ERROR: suspendLicense function not found!');
            }
        }

        function testReactivate() {
            console.log('=== Testing reactivateLicense(2) ===');
            if (typeof window.reactivateLicense === 'function') {
                window.reactivateLicense(2);
            } else {
                console.error('reactivateLicense function not found!');
                alert('ERROR: reactivateLicense function not found!');
            }
        }

        function testRevoke() {
            console.log('=== Testing revokeLicense(2) ===');
            if (typeof window.revokeLicense === 'function') {
                window.revokeLicense(2);
            } else {
                console.error('revokeLicense function not found!');
                alert('ERROR: revokeLicense function not found!');
            }
        }

        // Auto-test on load
        setTimeout(() => {
            console.log('Auto-testing functions on page load...');
            testFunctions();
        }, 500);

        // Override alert to also show in console
        const originalAlert = window.alert;
        window.alert = function(message) {
            console.log('ALERT: ' + message);
            originalAlert(message);
        };

        // Override confirm to auto-accept for testing
        const originalConfirm = window.confirm;
        window.confirm = function(message) {
            console.log('CONFIRM DIALOG: ' + message + ' (auto-accepting for test)');
            return true; // Auto-accept for testing
        };

        console.log('Debug page loaded and ready!');
    </script>
</body>
</html>
