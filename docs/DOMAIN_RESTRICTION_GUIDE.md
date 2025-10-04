# Domain Restriction Feature - Complete Guide

## Overview
The License Management System now enforces strict domain restrictions. When an "Allowed Domain" is specified for a license, the license key will ONLY work on that domain or matching domains.

---

## 🔒 How Domain Restriction Works

### 1. **No Allowed Domains Set**
- License key works on **ANY domain**
- No restrictions applied
- Maximum flexibility

### 2. **Allowed Domains Specified**
- License key works **ONLY on specified domains**
- Domain validation is **REQUIRED**
- Activation will **FAIL** if domain doesn't match
- Validation without domain will **FAIL**

---

## 🎯 Domain Matching Rules

### Exact Match
```
Allowed: example.com
✅ Works: example.com
❌ Fails: subdomain.example.com
❌ Fails: another-site.com
```

### Wildcard Match (*.domain.com)
```
Allowed: *.example.com
✅ Works: subdomain.example.com
✅ Works: app.example.com
✅ Works: any.subdomain.example.com
❌ Fails: example.com (no wildcard for root)
❌ Fails: another-site.com
```

### Multiple Domains
```
Allowed: example.com, app.example.com, *.test.com
✅ Works: example.com
✅ Works: app.example.com
✅ Works: sub.test.com
✅ Works: any.sub.test.com
❌ Fails: another-site.com
```

### Subdomain Automatic Match
```
Allowed: example.com
✅ Works: example.com
✅ Works: sub.example.com (subdomain of allowed domain)
✅ Works: deep.sub.example.com
❌ Fails: example-fake.com
```

---

## 📝 Setting Up Domain Restrictions

### In Admin Panel

1. **Create/Edit License**
2. Click "Advanced Options"
3. Find "Allowed Domains" field
4. Enter domains (comma-separated)

#### Examples:
```
Single domain:
example.com

Multiple domains:
example.com, mysite.com, app.example.com

With wildcards:
*.example.com, mysite.com

Localhost for development:
localhost, 127.0.0.1, *.local
```

---

## 🔧 API Behavior

### Validation Endpoint

**Without Domain Restrictions:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX"
}
```
✅ Response: Valid (works on any domain)

**With Domain Restrictions:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX"
}
```
❌ Response: "Domain is required for this license"

```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "example.com"
}
```
✅ Response: Valid (if example.com is allowed)
❌ Response: "Domain not authorized" (if not allowed)

### Activation Endpoint

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "example.com"
}
```

**Success Response:**
```json
{
  "success": true,
  "activation_token": "...",
  "message": "License activated successfully"
}
```

**Failure Response (Wrong Domain):**
```json
{
  "success": false,
  "valid": false,
  "message": "Domain not authorized for this license. Allowed domains: example.com, mysite.com"
}
```

**Failure Response (Domain Required):**
```json
{
  "success": false,
  "valid": false,
  "message": "Domain is required for this license. This license has domain restrictions."
}
```

---

## 🛡️ Security Features

### 1. Domain Sanitization
- All domains are sanitized before storage
- Removes protocols (http://, https://)
- Removes paths (/admin, /page)
- Converts to lowercase
- Trims whitespace

### 2. Strict Validation
- Exact pattern matching
- Wildcard support with regex
- Case-insensitive comparison
- No partial matches (prevents bypass)

### 3. Logging
- All validation attempts logged
- Failed attempts recorded with reason
- Domain mismatch details logged
- Audit trail for security

---

## 💡 Use Cases

### 1. Single Site License
```
Product: Premium WordPress Theme
Allowed Domains: example.com
Usage: Customer can only use on example.com
```

### 2. Multi-Site License
```
Product: Agency Bundle
Allowed Domains: site1.com, site2.com, site3.com
Usage: Customer can use on 3 specific sites
```

### 3. Development + Production
```
Product: SaaS Application
Allowed Domains: *.myapp.com, localhost
Usage: Works on all myapp.com subdomains + localhost for dev
```

### 4. Unlimited (No Restrictions)
```
Product: Open License
Allowed Domains: (empty)
Usage: Works on any domain
```

---

## 🧪 Testing Domain Restrictions

### Test Scenarios

#### Scenario 1: Exact Domain Match
```
Allowed: example.com
Test Domain: example.com
Expected: ✅ PASS
```

#### Scenario 2: Wildcard Match
```
Allowed: *.example.com
Test Domain: app.example.com
Expected: ✅ PASS
```

#### Scenario 3: Wrong Domain
```
Allowed: example.com
Test Domain: hacker.com
Expected: ❌ FAIL - "Domain not authorized"
```

#### Scenario 4: Missing Domain
```
Allowed: example.com
Test Domain: (not provided)
Expected: ❌ FAIL - "Domain is required"
```

#### Scenario 5: Multiple Domains
```
Allowed: site1.com, site2.com
Test Domain: site1.com
Expected: ✅ PASS

Test Domain: site2.com
Expected: ✅ PASS

Test Domain: site3.com
Expected: ❌ FAIL
```

---

## 🔍 Troubleshooting

### Issue: "Domain not authorized"
**Cause**: The domain doesn't match any allowed domains
**Solution**: 
1. Check spelling of allowed domains
2. Ensure domain is in the list
3. Check for wildcards if using subdomains
4. Verify domain sanitization (no http://, no paths)

### Issue: "Domain is required"
**Cause**: License has domain restrictions but no domain was provided
**Solution**: Always pass the domain parameter in API calls

### Issue: Wildcard not working
**Cause**: Incorrect wildcard syntax
**Solution**: Use `*.example.com` not `*.example.*` or `*example.com`

### Issue: Subdomain not working
**Cause**: Exact match expected but subdomain provided
**Solution**: Either add subdomain explicitly or use wildcard `*.example.com`

---

## 📊 Database Structure

### License Table
```sql
allowed_domains VARCHAR(500) NULL
```
- Stores comma-separated list of allowed domains
- NULL = no restrictions (works everywhere)
- Empty string = treated as NULL
- Max 500 characters

### Activations Table
```sql
domain VARCHAR(255) NOT NULL
```
- Stores the activated domain
- Required field
- Used for validation on each check

---

## 🚀 Best Practices

### For Administrators

1. **Be Specific**: Use exact domains when possible
2. **Use Wildcards Wisely**: Only for legitimate subdomain needs
3. **Document**: Add notes about domain restrictions in license notes
4. **Test First**: Test domain patterns before giving to customer
5. **Allow Development**: Include localhost for development licenses

### For Developers

1. **Always Pass Domain**: Include domain in all API calls
2. **Handle Errors**: Properly handle domain validation failures
3. **Sanitize Input**: Clean domain before sending to API
4. **Cache Carefully**: Don't cache validation across domains
5. **Log Failures**: Log domain validation failures for debugging

### For Customers

1. **Know Your Domain**: Provide correct domain during setup
2. **Include Subdomains**: Request wildcards if using subdomains
3. **Plan Ahead**: Request all needed domains initially
4. **Test Locally**: Use localhost entries for development
5. **Contact Support**: Request domain updates if needed

---

## 🔐 Security Considerations

### Prevents

✅ License key sharing across unauthorized domains
✅ Reselling of licenses to other domains
✅ Unauthorized redistribution
✅ Key theft and reuse on different sites

### Does NOT Prevent

❌ Multiple activations on same domain (use max_activations)
❌ IP-based restrictions (not implemented)
❌ User-based restrictions (not implemented)
❌ Geographical restrictions (not implemented)

---

## 📈 Implementation Details

### Files Modified

1. **classes/LMSLicenseManager.php**
   - Enhanced `validateLicense()` method
   - Added `matchesDomain()` helper method
   - Improved domain validation logic
   - Added wildcard support

### Code Changes

#### Before:
```php
if (!in_array($domain, $allowed_domains)) {
    return ['valid' => false, 'message' => 'Domain not authorized'];
}
```

#### After:
```php
$domain_allowed = false;
foreach ($allowed_domains as $allowed_domain) {
    if ($this->matchesDomain($domain, $allowed_domain)) {
        $domain_allowed = true;
        break;
    }
}

if (!$domain_allowed) {
    return [
        'valid' => false, 
        'message' => 'Domain not authorized. Allowed: ' . implode(', ', $allowed_domains)
    ];
}
```

---

## ✅ Testing Checklist

- [ ] Create license without domain restrictions (works anywhere)
- [ ] Create license with single domain (only works there)
- [ ] Create license with multiple domains (works on all)
- [ ] Create license with wildcard (works on subdomains)
- [ ] Test activation on allowed domain (success)
- [ ] Test activation on wrong domain (failure)
- [ ] Test activation without domain (failure if restricted)
- [ ] Test wildcard matching
- [ ] Test subdomain matching
- [ ] Verify error messages are clear
- [ ] Check logging functionality
- [ ] Test API endpoints

---

## 🎓 Examples

### Example 1: WordPress Theme License
```
Product: Premium WP Theme
Customer: John Doe
Allowed Domains: johndoe.com, www.johndoe.com
Max Activations: 1

Result: Works only on johndoe.com and www.johndoe.com
```

### Example 2: SaaS Application
```
Product: Cloud App Pro
Customer: Acme Corp
Allowed Domains: *.acmecorp.com, acme.com
Max Activations: 10

Result: Works on:
  ✅ acme.com
  ✅ app.acmecorp.com
  ✅ api.acmecorp.com
  ✅ any.sub.acmecorp.com
```

### Example 3: Development License
```
Product: Dev Tools Bundle
Customer: Developer
Allowed Domains: localhost, 127.0.0.1, *.local, *.test
Max Activations: Unlimited

Result: Works on all local development environments
```

---

## 📞 Support

For issues or questions about domain restrictions:
1. Check license configuration in admin panel
2. Verify domain format (no http://, no paths)
3. Test with exact domain match first
4. Check server logs for validation errors
5. Contact administrator for domain updates

---

**Status**: ✅ Implemented and Active
**Version**: 2.0
**Last Updated**: October 4, 2025
**Documentation**: Complete
