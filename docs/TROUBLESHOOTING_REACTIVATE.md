# Troubleshooting Guide: Reactivate License Issue

## Quick Fix Steps

### Step 1: Clear Browser Cache
The most common issue is **browser caching old JavaScript files**.

**Solution:**
1. Open the License Manager page in your browser
2. Press `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac) to **hard refresh**
3. Or: Press `F12` to open DevTools → Go to Network tab → Check "Disable cache"
4. Refresh the page again

### Step 2: Verify JavaScript is Loaded
1. Open Browser DevTools (F12)
2. Go to **Console tab**
3. Type: `reactivateLicense`
4. Press Enter
5. **Expected result:** You should see the function definition
6. **If "undefined":** The JavaScript file is not loaded or cached

### Step 3: Test the Backend API Directly
Send a POST request to the admin handler without relying on the retired `test-reactivate.php` page. Example using `curl`:

```bash
curl -X POST \
   -H "Content-Type: application/x-www-form-urlencoded" \
   -d "action=reactivate_license&license_id=1" \
   http://localhost/license-management-system/admin/license-manager.php
```

Replace `license_id=1` with an actual suspended license ID and confirm the JSON response reports success.

## Diagnostic Steps

### Check 1: Verify Reactivate Button Appears
1. Open License Manager page
2. Look for a license with status "Suspended"
3. Click the three-dot menu (⋮) next to that license
4. **Expected:** You should see "Reactivate" option with a check icon
5. **If not visible:** The PHP conditional is not working

### Check 2: Console Debugging
When you click "Reactivate":
1. Open Browser Console (F12)
2. You should see these logs:
   ```
   reactivateLicense called with ID: [number]
   Sending reactivate request...
   Response received: 200
   Result: {success: true, message: "..."}
   ```
3. **If you don't see these logs:** JavaScript function is not being called

### Check 3: Network Tab Inspection
1. Open Browser DevTools (F12)
2. Go to **Network tab**
3. Click "Reactivate" button
4. Look for a POST request to `license-manager.php`
5. Click on it to see:
   - **Request Payload:** Should contain `action=reactivate_license&license_id=[number]`
   - **Response:** Should be JSON with `{success: true, message: "..."}`

### Check 4: PHP Error Logs
Check XAMPP error logs:
```
C:\xampp\apache\logs\error.log
```

Look for lines containing:
- "License Manager POST Request"
- "License reactivated"
- Any PHP errors

## Common Issues and Solutions

### Issue 1: "reactivateLicense is not defined"
**Cause:** JavaScript file is not loaded or old version is cached

**Solution:**
```javascript
// In browser console, verify the file is loaded with timestamp:
// Check the <script> tag has ?v=[timestamp]
```

1. View page source
2. Look for: `<script src="../assets/js/license-manager.js?v=1728086400"></script>`
3. If no `?v=` parameter, the fix wasn't applied

### Issue 2: Button not appearing for suspended licenses
**Cause:** PHP conditional logic issue

**Solution:**
Check the license status in database:
```sql
SELECT id, license_key, status FROM lms_licenses WHERE status = 'suspended';
```

Verify the dropdown menu code:
```php
<?php elseif ($license['status'] === 'suspended'): ?>
    <a href="#" onclick="reactivateLicense(<?php echo $license['id']; ?>); return false;">
        <i class="fas fa-check-circle"></i> Reactivate
    </a>
<?php endif; ?>
```

### Issue 3: Request returns 404 or error
**Cause:** POST action handler not found

**Solution:**
Verify the action handler exists in `license-manager.php`:
```php
case 'reactivate_license':
    $license_id = (int)$_POST['license_id'];
    $data = ['status' => 'active'];
    $license_manager->updateLicenseById($license_id, $data);
    // ...
```

### Issue 4: Status changes but page doesn't reflect it
**Cause:** Status is being updated but display logic is incorrect

**Solution:**
1. Check database directly after clicking reactivate:
   ```sql
   SELECT status FROM lms_licenses WHERE id = [your_license_id];
   ```
2. If status is 'active' in DB but shows 'suspended' on page → Page cache issue
3. Solution: Hard refresh the page

## Testing Checklist

- [ ] Hard refresh browser (Ctrl+Shift+R)
- [ ] Clear browser cache completely
- [ ] Verify `reactivateLicense` function exists in console
- [ ] Check Network tab for POST requests
- [ ] Verify server-side action handler exists
- [ ] Check PHP error logs
- [ ] Verify database status after action
- [ ] Check dropdown menu shows correct option

## Manual Database Fix

If you need to manually reactivate a license:

```sql
-- Find suspended licenses
SELECT id, license_key, customer_name, status FROM lms_licenses WHERE status = 'suspended';

-- Manually reactivate (replace ID with actual ID)
UPDATE lms_licenses SET status = 'active', updated_at = CURRENT_TIMESTAMP WHERE id = 1;

-- Verify
SELECT id, license_key, customer_name, status FROM lms_licenses WHERE id = 1;
```

## Verification Script

Run this in browser console on License Manager page:

```javascript
// Test if function exists
console.log('reactivateLicense exists:', typeof reactivateLicense !== 'undefined');

// Test if it can be called (will show confirm dialog)
if (typeof reactivateLicense !== 'undefined') {
    console.log('Function is defined. You can test by calling: reactivateLicense(1)');
} else {
    console.error('Function NOT defined! Check if JavaScript file is loaded.');
}

// Check script tag
const scripts = document.querySelectorAll('script[src*="license-manager.js"]');
console.log('License Manager JS scripts found:', scripts.length);
scripts.forEach(s => console.log('  Script src:', s.src));
```

## Success Indicators

When working correctly, you should see:

1. ✅ Suspended licenses show "Reactivate" in dropdown
2. ✅ Clicking reactivate shows confirmation dialog
3. ✅ Console shows debug logs
4. ✅ Network tab shows POST request to license-manager.php
5. ✅ Response is JSON with success:true
6. ✅ Page reloads after 1 second
7. ✅ License status changes to "Active" in table
8. ✅ Database shows status='active'

## If Still Not Working

1. **Take screenshots of:**
   - Browser console showing errors
   - Network tab showing the POST request/response
   - The dropdown menu for a suspended license

2. **Check these files were updated:**
   - `admin/license-manager.php` - Backend actions added
   - `assets/js/license-manager.js` - reactivateLicense function added

3. **Verify PHP version:**
   ```bash
   php -v
   ```
   Should be PHP 7.4 or higher

4. **Check database connection:**
   Open any other admin page (dashboard.php) - if it works, DB is fine

5. **Compare with working page:**
   - Open `admin/licenses.php`
   - Test suspend/reactivate there
   - Compare network requests between the two pages

## Contact Information

If issue persists after all steps:
- Document all error messages
- Check PHP error logs
- Verify all files were saved properly
- Restart Apache server in XAMPP
