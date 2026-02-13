# BeeQ Framework Security Analysis Report

**Date:** 2026-02-13
**Scope:** All PHP and JS files in the BeeQ low-code framework
**Analyst:** Claude (Automated Security Review)
**Files Analyzed:** 12 PHP files, 1 JS file (~470KB total)

---

## Executive Summary

The BeeQ framework is a PHP-based low-code application platform that dynamically generates forms, lists, and workflows from database-driven configuration. The codebase shows evidence of ongoing security hardening (AES-256-GCM encryption upgrade, SQL command aliasing, referrer checks, session hardening). However, **critical vulnerabilities remain**, primarily around SQL injection via string concatenation, cross-site scripting (XSS) through unescaped output, and dangerous use of `eval()`.

### Risk Rating: **HIGH**

| Severity | Count |
|----------|-------|
| Critical | 6 |
| High     | 8 |
| Medium   | 7 |
| Low      | 5 |

---

## 1. SQL Injection (CRITICAL)

### 1.1 Search Functionality - Direct Interpolation into SQL WHERE Clauses

**Files:** `bq_list_table.php:107-161`
**Severity:** CRITICAL
**OWASP:** A03:2021 - Injection

The search functionality strips single and double quotes but then directly interpolates user input into SQL:

```php
// bq_list_table.php:108
$s = str_replace(["'", '"'], "", $s);
$searchWhere = "(";
foreach($_SESSION['currentpage']['meta']['searchfields'] as $k=>$v){
    $searchWhere .= " $v LIKE '%$s%' OR ";
}
```

**Attack Vector:** The quote stripping is insufficient. An attacker can use backslash escaping, boolean-based blind injection without quotes (e.g., `OR 1=1--`), or exploit fields that don't require string context. The search input is never parameterized.

### 1.2 Delete Action - `$_GET['id']` Directly in SQL

**File:** `bq_list_edit_action.php:45`
**Severity:** CRITICAL

```php
$sql="delrec from ".$table." where id='".$idx."'";
pw_execute($sql);
```

Where `$idx = $_GET['id']` (line 39). No parameterized query is used.

### 1.3 List Edit Delete - `$_GET['id']` in SQL

**File:** `bq_list_table_le.php:28`
**Severity:** CRITICAL

```php
$sql="delrec from ".$_SESSION['currentpage_le']['head']['tablename']. " where id='".$_GET['id']."'";
PW_execute($sql);
```

Direct concatenation of `$_GET['id']` into a DELETE statement.

### 1.4 Edit Table Rows - `$id` in SQL

**File:** `bq_list_table_le.php:112`
**Severity:** HIGH

```php
$sql = "selrec * from " . $_SESSION['currentpage_le']['head']['tablename'] . " where id='" . $id . "'";
```

While `$id` is derived from `pw_dec($_GET['hid'])`, if the decryption mechanism is bypassed or the encrypted value manipulated, this becomes injectable.

### 1.5 TopCombo Filter - Direct Concatenation

**File:** `bq_list_table.php:221-227`
**Severity:** HIGH

```php
$str = " and ".$tcArray[1]."='".$reqString[0]."' ";
if($like!='') $str = " and ".$tcArray[1]." like '%".$reqString[0]."%' ";
```

Values derived from POST data are concatenated directly into SQL filter strings.

### 1.6 Parent Condition - Direct GET Parameters

**File:** `bq_list_table.php:371-373`
**Severity:** HIGH

```php
$parentid   = $_GET['parentid'];
$parenttable= $_GET['parenttable'];
$parentCond = " AND (linkedid='$parentid' AND linkedto='$parenttable')";
```

Both `$_GET['parentid']` and `$_GET['parenttable']` are directly interpolated.

### 1.7 Page Setup SQL Construction

**File:** `bq_list_table_le.php:62`
**Severity:** HIGH

```php
$currentPage_le['head']=getValueforPS("selrec * from _pb_pagehead where pgid='".$pgid."' limit 0,1");
```

Despite `getValueForPS` being a prepared statement wrapper, the SQL string is pre-built with `$pgid` concatenated. This defeats the purpose of prepared statements.

### 1.8 Attachment Operations with String Interpolation

**File:** `bq_list_edit_action.php:655`
**Severity:** MEDIUM

```php
$attcnt1=getValueForPS("selrec count(*) from _pb_attachments where linkedto='".$tablename."' and linkedid='".$id."'");
```

Values concatenated into queries that are then passed to the prepared statement function, negating its protection.

---

## 2. Cross-Site Scripting (XSS)

### 2.1 SQL Statement Echoed to Client

**File:** `bq_list_edit_action.php:54, 74`
**Severity:** CRITICAL
**Type:** Reflected XSS

```php
el.innerHTML="'.$sql.'";
```

The constructed SQL statement (which may contain user-controlled data) is injected directly into JavaScript/HTML via `innerHTML`. An attacker who controls parts of the SQL (e.g., through `$_GET['id']`) can inject arbitrary HTML/JavaScript.

### 2.2 Threat Detection Alert with User Data

**File:** `bq_list_edit_action.php:109-113`
**Severity:** CRITICAL
**Type:** Reflected XSS

```php
echo "
<script>
    alert('Erx 43344: ".$threat."');
</script>
";
```

The `$threat` variable contains POST key names and values directly concatenated. An attacker can inject JavaScript by crafting POST parameter names containing script-breaking characters.

### 2.3 Thread Control - Stored User Data Output Without Encoding

**File:** `bq_list_edit_action.php:17-23`
**Severity:** HIGH
**Type:** Stored XSS

```php
if(isset($v['userid']))$str.="<i class='bi bi-person-fill'></i> ".$v['userid'];
if(isset($v['username']))$str.=$v['username']."<br>";
if(isset($v['message']))$str.="<i class='bi bi-chat-left-text'></i> ".nl2br($v['message'])."<br>";
```

Thread messages from the database (userid, username, message, userip) are output without `htmlspecialchars()`. Any stored malicious content in these fields will execute in victims' browsers.

### 2.4 Filename Display Without Encoding

**File:** `bq_list_table_le.php:15`
**Severity:** HIGH
**Type:** Stored XSS

```php
echo "<div class='bq-popup'>
        <span class='file-name' style='max-width:160px;' title='$filename'><b>$filename
```

The `$filename` variable from the database is output directly into HTML attributes and content without encoding.

### 2.5 Post Error Display

**File:** `bq_list_edit_action.php:194`
**Severity:** MEDIUM
**Type:** Reflected XSS

```php
'.$postErrors.'
```

While `$postErrors` is built from validation logic, if field captions or values are user-controlled, XSS payloads could be injected.

### 2.6 Positive Note: Some Output Encoding Present

**File:** `bq_list_table.php:191`

```php
$esc = array_map('htmlspecialchars', $r);
```

The list table rendering does apply `htmlspecialchars` to row data. This is good practice but inconsistently applied across the codebase.

---

## 3. Remote Code Execution via `eval()`

### 3.1 User-Controlled Data in eval()

**File:** `bq_list_edit_action.php:536`
**Severity:** CRITICAL

```php
$result = eval("return ($eval);");
```

The `fieldjsvalidationsold` function takes validation expressions from the database (field configuration) and evaluates them with `eval()`. While this is database-driven rather than directly user-input, any SQL injection or admin-panel compromise that modifies validation rules leads to **Remote Code Execution**.

Additionally, POST values are substituted into the eval string:
```php
foreach($_POST as $kk=>$vv){
    $eval=str_replace("[".$kk."]",$vv,$eval);
}
```

If a validation rule contains `[$fieldname]` placeholders, user POST data is substituted directly before `eval()`, creating an RCE vector.

---

## 4. Authentication and Session Security

### 4.1 Hardcoded Database Credentials

**File:** `bq_indi_engine.php:570-577`
**Severity:** HIGH

```php
function PW_connect(){
    $engine="mysql";
    $server="10.1.2.6";
    $uid="UID";
    $pwd="XXXXX";
    $db="DB";
```

Database credentials are hardcoded in the source file. While the displayed values appear to be placeholders, this pattern means credentials live in version control. They should be in environment variables or a secrets manager.

### 4.2 Session Configuration (POSITIVE)

**File:** `bq_indi_engine.php:2-8`

The session configuration is well-hardened:
```php
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Lax');
```

This is **good practice** and prevents session fixation, cookie theft via XSS, and session ID leakage.

### 4.3 Fixed Encryption IV

**File:** `bq_indi_engine.php:89, 105, 234, 244`
**Severity:** MEDIUM

Legacy encryption uses a hardcoded IV:
```php
openssl_encrypt($str, OPENSSLMETHOD, OPENSSLKEY, 0, "12345678abcdefgh");
```

A fixed IV weakens AES encryption significantly - identical plaintexts produce identical ciphertexts, enabling pattern analysis. The newer v1 GCM implementation (lines 294-359) correctly uses random IVs and is the recommended path forward.

### 4.4 Encryption Key Fallback

**File:** `bq_indi_engine.php:207-211`
**Severity:** MEDIUM

```php
// Fallback: derive from OPENSSLKEY (not ideal but works)
return hash('sha256', $fallback, true);
```

If the `APP_AES256GCM_KEY_B64` env var is not set, the system falls back to deriving a key from `OPENSSLKEY`. This may weaken security if `OPENSSLKEY` is a low-entropy password.

---

## 5. CSRF Protection

### 5.1 Cross-Reference Token System

**File:** `bq_indi_engine.php:42-44, 554-561`
**Severity:** MEDIUM

```php
if(isset($_POST['form_xref']) && !empty($_POST['form_xref'])){
    checkXref($_POST['form_xref'],$_POST['xref']);
}
```

The system uses an encrypted cross-reference (`xref`) token system for CSRF protection. However:
- Not all state-changing endpoints verify XREF tokens (e.g., delete operations via GET)
- The delete action in `bq_list_edit_action.php:36` uses `$_GET['action']=='delete'` with no CSRF token
- The list edit delete in `bq_list_table_le.php:23-36` processes deletes without CSRF verification

### 5.2 State-Changing Operations via GET

**Files:** `bq_list_edit_action.php:36`, `bq_list_table_le.php:23`
**Severity:** HIGH

Delete operations triggered via GET parameters violate REST conventions and are vulnerable to CSRF via image tags, link prefetching, etc.

---

## 6. File Upload Security

### 6.1 Incomplete MIME Validation

**File:** `bq_list_edit_action.php:786, 862-878`
**Severity:** MEDIUM

MIME type validation code exists but is partially commented out:
```php
/*if (!in_array(strtolower($realMime), $allowedTypes[$extLower])) {
    ...
}*/
```

The active validation checks extension against allowed lists and does MIME validation in the `$id != ''` path (updates), but the new upload path (`$id == ''`) has its MIME check commented out.

### 6.2 Dangerous Extension Allowlist

**File:** `bq_list_edit_action.php:932`
**Severity:** LOW

The allowed extension list includes CAD file types (`.dwg`, `.sldprt`, `.prt`, etc.) that may have known parsing vulnerabilities in client applications. More importantly, the check only blocks `php, js, exe, bat` explicitly. Other executable types (`.phtml`, `.phar`, `.sh`, `.cmd`, `.ps1`) are not blocked.

### 6.3 Predictable Upload Filenames

**File:** `bq_list_edit_action.php:955`
**Severity:** LOW

```php
$_SESSION['enc_user_uploadedfile'] = $fileDir.getRandomStr(10).".".$ext;
```

Upload filenames use a random string. The randomness quality depends on `getRandomStr()` implementation. If it uses `rand()` instead of `random_bytes()`, filenames may be predictable.

---

## 7. File Inclusion Vulnerabilities

### 7.1 Dynamic Include Based on User-Influenced Data

**Files:** `bq_list_table.php:16-18`, `bq_list_table_le.php:123-124, 196-197, 443-444`, `bq_list_edit_action.php:406-407, 462-463`
**Severity:** MEDIUM

```php
if(file_exists("segment/".$pgid.".php")){
    include_once("segment/".$pgid.".php");
}
```

While `$pgid` typically comes from session state (database-configured page IDs), the `file_exists()` check and `.php` suffix provide some protection. However, if an attacker gains control over `$pgid` (via SQL injection or session manipulation), they could include arbitrary PHP files from the `segment/` directory.

### 7.2 File Inclusion via `$_GET['pw']`

**File:** `bq_indi_engine.php:545-549`
**Severity:** LOW (mitigated)

```php
if (!preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $_GET['pw'])) {
    bq_security_fail("Invalid pw");
    exit;
}
checkInclude($_GET['pw']);
```

The `pw` parameter (decrypted from `bqkey`) determines which PHP file to include. The regex whitelist effectively prevents directory traversal and non-PHP file inclusion. **This is well-protected.**

---

## 8. Information Disclosure

### 8.1 SQL Queries Exposed to Client

**File:** `bq_list_edit_action.php:54, 74, 688`
**Severity:** HIGH

```php
el.innerHTML="'.$sql.'";
```

Full SQL statements are output to the browser in JavaScript, revealing table names, column names, and query structure.

```php
echo "selrec * from _pb_attachments_version where linkedto='".$table."' and linkedid='".$id."' order by versionno";
```

Line 688 directly echoes a SQL query for debugging purposes.

### 8.2 Debug Output Left in Production

**Files:** Multiple locations
**Severity:** MEDIUM

Several `printr()` calls remain in the code:
- `bq_list_edit_action.php:723, 771-772` - `printr($userUploadedFile)`, `printr($fileInputName)`
- Various commented `printr()` calls suggest this is a development/staging environment pattern

### 8.3 Database Connection Details in Source

**File:** `bq_indi_engine.php:570-577`
**Severity:** MEDIUM

Internal IP address `10.1.2.6` is exposed in source code, revealing network topology.

---

## 9. Encryption and Cryptography

### 9.1 Custom SQL Command Aliasing (Security Through Obscurity)

**File:** `bq_indi_engine.php:579-745`
**Severity:** LOW (Design concern)

The framework uses custom SQL command prefixes (`SELREC`, `DELREC`, etc.) that are translated to real SQL commands. While this provides a layer of obscurity and enables some filtering, it is not a security control. The `mysqlOverwrite()` function does implement useful pattern-based detection (UNION injection, SLEEP attacks, etc.), which is positive.

### 9.2 AES-256-GCM Implementation (POSITIVE)

**File:** `bq_indi_engine.php:294-359`

The v1 encryption implementation using AES-256-GCM with:
- Random 12-byte IVs
- Authentication tags
- AAD binding to form/control context
- Base64url encoding for URL safety

This is **well-implemented** and a significant security improvement over the legacy system.

---

## 10. HTMX-Specific Security Concerns

### 10.1 Encrypted URL Parameters

Throughout the codebase, HTMX requests use encrypted `bqkey` parameters:
```php
hx-get="do_bq.php?bqkey=".pw_enc($url)."'
```

This is a reasonable approach to prevent parameter tampering in the HTMX request model. However, the security depends entirely on the encryption key remaining secret.

### 10.2 innerHTML Swap Mode

**Files:** Multiple HTMX targets use `hx-swap="innerHTML"`
**Severity:** MEDIUM

HTMX's `innerHTML` swap will execute `<script>` tags in the response. Combined with any XSS vulnerability on the server side, this creates a reliable JavaScript execution path. Consider using Content-Security-Policy headers and HTMX's `hx-swap` with OOB swaps where possible.

---

## 11. Missing Security Headers

**Severity:** MEDIUM

The following HTTP security headers were not observed in the codebase:
- `Content-Security-Policy` - Would mitigate XSS impact
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY` or `SAMEORIGIN`
- `Strict-Transport-Security` (HSTS)
- `Permissions-Policy`

While these may be configured at the web server level, they should be set in the application as a defense-in-depth measure.

---

## 12. Summary of Findings by OWASP Top 10 (2021)

| OWASP Category | Findings | Max Severity |
|---|---|---|
| A01 - Broken Access Control | State-changing GET requests, missing CSRF on deletes | High |
| A02 - Cryptographic Failures | Fixed IV in legacy encryption, hardcoded credentials | High |
| A03 - Injection | SQL injection (6+ locations), eval() with user data, XSS (5+ locations) | Critical |
| A04 - Insecure Design | SQL queries in client responses, debug output | High |
| A05 - Security Misconfiguration | Missing security headers, commented-out MIME validation | Medium |
| A06 - Vulnerable Components | Not assessed (no dependency manifest) | N/A |
| A07 - Auth Failures | Hardcoded credentials pattern | High |
| A08 - Data Integrity Failures | Inconsistent CSRF protection | Medium |
| A09 - Logging Failures | SQL warning logging present (positive), but no security event logging | Medium |
| A10 - SSRF | No SSRF vectors identified | N/A |

---

## 13. Positive Security Observations

1. **Session hardening** - Strict mode, HTTP-only cookies, secure flag, SameSite
2. **AES-256-GCM encryption** - Modern authenticated encryption for v1 tokens
3. **SQL command aliasing** - Custom prefixes with dangerous pattern detection
4. **Referrer validation** - Host-based referrer checking for HTMX requests
5. **File inclusion whitelist** - Regex validation on included PHP filenames
6. **bqkey encryption** - All URL parameters encrypted, preventing casual tampering
7. **Input key validation** - Regex for parameter key names in `getRequest()`
8. **File upload extension blocking** - Blocks PHP, JS, EXE, BAT uploads
9. **XREF token system** - CSRF-like protection on form submissions
10. **List table output encoding** - `htmlspecialchars()` used on list row data

---

## 14. Priority Remediation Recommendations

### Immediate (Critical - Fix Now)

1. **Parameterize ALL SQL queries** - Replace every instance of string concatenation in SQL with prepared statement placeholders (`?`). The framework already has `getValueForPS()` and `PW_sql2rsPS()` - use them consistently.

2. **Remove `eval()`** - Replace `eval()` in validation logic with a safe expression parser or predefined validation rules.

3. **Escape all HTML output** - Apply `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` to every dynamic value output to HTML. Never inject SQL or unsanitized data into `innerHTML`.

### Short-Term (High - Fix This Sprint)

4. **Use POST for all state changes** - Move delete and other state-changing operations from GET to POST with CSRF token verification.

5. **Remove SQL from client responses** - Never send SQL queries, table names, or column names to the browser.

6. **Move credentials to environment** - Extract database credentials and encryption keys from source code to environment variables.

7. **Enable MIME validation** - Uncomment and enforce the MIME type validation in file uploads. Block additional dangerous extensions (`.phtml`, `.phar`, `.sh`, `.cmd`).

### Medium-Term (Medium - Plan for Next Release)

8. **Add security headers** - Implement CSP, X-Content-Type-Options, X-Frame-Options, HSTS.

9. **Deprecate legacy encryption** - Migrate all tokens to v1 GCM format and remove legacy AES-CBC code with fixed IV.

10. **Remove debug output** - Strip all `printr()` calls and echoed SQL from production code.

11. **Implement rate limiting** - Add rate limiting on authentication and search endpoints.

12. **Security logging** - Log all authentication events, failed access attempts, and parameter tampering detections.

---

*Report generated by automated security analysis. Manual penetration testing is recommended to validate these findings and discover additional vulnerabilities that may require runtime analysis.*
