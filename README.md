<div align="center">

# ShopLab (online_shop)

**A simple PHP clothing store, intentionally vulnerable, built for cybersecurity teaching**

![PHP](https://img.shields.io/badge/PHP-8.4.4--fpm-777BB4?logo=php&logoColor=white)
![Nginx](https://img.shields.io/badge/Nginx-1.27--alpine-009639?logo=nginx&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-PDO-003B57?logo=sqlite&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![Lab only](https://img.shields.io/badge/Lab%20only-intentionally%20vulnerable-B3473F)

</div>

> **Warning.** This application contains security vulnerabilities on purpose. It is not a production system. Never deploy it on the internet, on a public server or on a shared network.

## What this is

ShopLab is a simple online clothing store with no framework, built so students can see how unprotected code really behaves. Every problematic place in the code is marked with a `VULNERABLE:` comment. This README explains each weakness, how to observe it safely inside the lab, and what the correct fix looks like.

Store features:

- Home page with 12 sample products and search
- Product detail page with size selection
- Shopping cart (add, change quantity, remove)
- User registration and login with PHP sessions
- User profile and order pages
- Product image upload (multipart) with a preview page that reads and shows the uploaded file before publishing

## Versions and technology

| Part | Version | Note |
|---|---|---|
| PHP | 8.4.4 (FPM) | official image `php:8.4.4-fpm` |
| Nginx | 1.27 (alpine) | serves `src/public` and passes `.php` files to PHP-FPM |
| Database | SQLite through PDO | the file is created automatically in `src/data/` |
| Docker Compose | v2 | the `docker compose` command (no hyphen) |
| Frontend | HTML, CSS and a little JavaScript | no framework, one `style.css` file with CSS variables |

Simple structure: no framework, no Composer, no external dependencies.

## Lab rules

1. The lab is published on port 8080, so other devices on the same private network (for example a bridged virtual machine or your own computer) can reach it. Use it only on a private, trusted network. To restrict it to the local machine, set `BIND_IP=127.0.0.1` (see below).
2. All tests are done only in this lab environment, with sample data.
3. After each session, remove the environment with `docker compose down -v` and clear the contents of `src/data/` and `src/public/uploads/`.

## Quick start on Ubuntu

Step 1. Install Docker and Compose (Ubuntu 22.04 or 24.04) if you do not have them:

```bash
sudo apt update
sudo apt install -y docker.io docker-compose-v2 git
sudo usermod -aG docker $USER      # then log out and log in again
```

Step 2. Get the code and start it:

```bash
git clone https://github.com/exploitcartel/online_shop.git
cd online_shop
docker compose up -d --build
```

Step 3. Open it in the browser: http://127.0.0.1:8080

From another machine on the same network (another virtual machine or your main computer), open `http://VM_ADDRESS:8080`. To find the address of the machine that runs Docker:

```bash
hostname -I
```

## Access from another machine (bridged network)

The lab listens on all network interfaces of the machine that runs Docker, on port 8080. With a VirtualBox bridged adapter the virtual machine has its own address on your network, so other virtual machines and your main computer can open `http://VM_ADDRESS:8080` with no extra steps.

If other machines cannot connect, check these points in order:

1. The VirtualBox network adapter is set to "Bridged Adapter", and `hostname -I` shows an address in the same range as your main computer.
2. The published port shows `0.0.0.0:8080->80/tcp`:

```bash
docker compose ps
```

3. The lab answers on the network address from inside the virtual machine itself:

```bash
curl -I http://$(hostname -I | awk '{print $1}'):8080
```

4. If the `ufw` firewall is active, allow your own network (adjust the range):

```bash
sudo ufw status
sudo ufw allow from 192.168.1.0/24 to any port 8080 proto tcp
```

Restrict the lab to the local machine only (use an SSH tunnel if you still need access from your main computer):

```bash
echo "BIND_IP=127.0.0.1" > .env
docker compose up -d
ssh -L 8080:127.0.0.1:8080 username@UBUNTU_IP
```

Open it to the network again:

```bash
rm .env
docker compose up -d
```

Use the network mode only on a private, trusted network. Never run the lab on a public server or an untrusted Wi-Fi.

## Verify the setup

Check the PHP version (it must show 8.4.4):

```bash
docker compose exec php php -v
```

Check the services (both must be `running`):

```bash
docker compose ps
```

Check that the app answers on localhost:

```bash
curl -I http://127.0.0.1:8080/
```

Sample accounts (created automatically on first start):

| Username | Password | Role |
|---|---|---|
| admin | admin123 | admin |
| student | student123 | customer |

## Update from GitHub

```bash
cd online_shop
git pull
docker compose up -d --build
```

Reset the lab to its initial state (clean database, no uploaded images, sample product images are kept):

```bash
docker compose down -v
rm -f src/data/shop.sqlite
find src/public/uploads -type f ! -name .gitkeep ! -name 'sample-*' -delete
docker compose up -d --build
```

Stop and clean up:

```bash
docker compose down -v
```

## Directory structure

```
online_shop/
  docker-compose.yml
  README.md
  nginx/default.conf         Nginx configuration
  php/Dockerfile             php:8.4.4-fpm image
  php/lab.ini                intentionally weak PHP configuration
  src/
    includes/                config.php, header.php, footer.php
    public/                  application pages (Nginx document root)
      uploads/               uploaded images and the 12 sample product images (sample-*.svg)
    data/                    SQLite database (created automatically)
```

## How to read the code

Every problematic place in the code is marked with the comment `// VULNERABLE: <description>` (or `<!-- VULNERABLE: -->` in HTML). Students should use:

```bash
grep -rn "VULNERABLE" src/
```

About test inputs: use only harmless inputs that show the behavior without causing damage, as shown below.

## List of intentional vulnerabilities

### 1. SQL Injection (search, product details, login, registration, order note, profile, order, publish)

Where: `index.php` (parameter `q`), `product.php` (parameter `id`), `login.php`, `register.php`, `cart.php` (field `note`), `profile.php`, `order.php`, `preview.php`.

Cause: user input is concatenated directly into the SQL query text.

How to observe it in the lab:
- In the search box, type a single apostrophe `'` and look at the error message PHP shows.
- On the login page, use `admin'--` as the username and anything as the password. Observe how the SQL comment removes the password condition.
- On `product.php?id=1 OR 1=1`, observe how the query condition changes.

Correct fix: prepared statements with bound parameters.

```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user && password_verify($password, $user['password'])) { /* login succeeded */ }
```

For `id` and other numeric values also use `filter_var($id, FILTER_VALIDATE_INT)`.

### 2. Reflected Cross-Site Scripting (XSS)

Where: `index.php`, the "Results for" line.

How to observe it: search for `<b>test</b>` and see that the text appears in bold, which means the browser interprets it as HTML. To show script execution, use only a harmless test: `<script>alert(document.domain)</script>`

Correct fix:

```php
echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8');
```

Also add a `Content-Security-Policy` header that allows only trusted scripts.

### 3. Stored Cross-Site Scripting (XSS)

Where: the bio in `profile.php`, the order note in `order.php`, the username in the account menu, the product name in the cart, the file content in `preview.php`.

How to observe it: save `<i>test</i>` as the bio, then open the profile with another account. To demonstrate execution use only `alert(document.domain)`.

Correct fix: escape all output with `htmlspecialchars()`, and add a `Content-Security-Policy`.

### 4. Missing CSRF protection

Where: add, change and remove in the cart, the profile bio update, order checkout.

How to observe it: create a small local HTML page with a form that sends a `POST` to `http://127.0.0.1:8080/cart.php` with the action `remove`. Open it while signed in and see that the action runs without the user's consent.

Correct fix: a random token per session, placed in the form and verified on the server.

```php
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
// in the form: <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) { http_response_code(403); exit; }
```

Also set the session cookie with `SameSite=Lax` or `Strict`.

### 5. Price and quantity tampering in the cart

Where: `product.php` sends the price as a hidden field, and `cart.php` accepts it without comparing it to the database. A negative quantity is accepted when updating.

How to observe it: with the browser developer tools, change the value of the `price` field before submitting, then look at the total in the cart and in the order.

Correct fix: on the server always read the price from the database using `product_id`, and verify the quantity is a positive integer.

### 6. Broken access control (IDOR) on profile and orders

Where: `profile.php?id=2` and `order.php?id=1` (the `id` parameter).

How to observe it: sign in as `student`, open your own order, then change the `id` number in the URL and see that data of other users appears.

Correct fix: check ownership on the server.

```php
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $me['id']]);
```

### 7. Mass assignment in registration

Where: `register.php` accepts the `role` field from the form.

How to observe it: with the developer tools, change the hidden `role` field to `admin` before submitting, then open the profile and look at the role.

Correct fix: the role is set only on the server (`customer`), never from the request.

### 8. Weak password storage

Where: `config.php`, `register.php` and `login.php` use unsalted MD5.

How to observe it: compute the hash of `admin123` inside the container and compare it with a public MD5 table.

```bash
docker compose exec php php -r "echo md5('admin123'), PHP_EOL;"
```

Correct fix:

```php
$hash = password_hash($password, PASSWORD_ARGON2ID);
password_verify($password, $hash);
```

### 9. Weak session management

Where: `login.php`, `logout.php`, `php/lab.ini`.

Weaknesses: no `session_regenerate_id()` after login (session fixation), the cookie has no `HttpOnly` and `SameSite`, logout does not fully destroy the session, and there is no login attempt limit.

How to observe it: look at the `PHPSESSID` cookie in the developer tools before and after login and see that the value does not change. In the console, `document.cookie` returns the cookie because it has no `HttpOnly`.

Correct fix:

```php
session_regenerate_id(true);               // after login
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => true]);
// on logout: $_SESSION = []; session_destroy();
```

### 10. Unrestricted file upload

Where: `upload.php` and `nginx/default.conf`.

Weaknesses: the extension is not checked, the real content is not checked, the file is stored under the client's name, and Nginx executes PHP even inside `uploads/`.

How to observe it: upload a text file named `test.txt` and see that it is stored without any check. To show the risk of the extension, upload a file named `version.php` with this harmless content and open it at `/uploads/version.php`:

```php
<?php echo 'Lab: ', PHP_VERSION;
```

See that the server runs the uploaded file as code, and discuss what this would mean on a real server.

Correct fix:

```php
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
if (!isset($allowed[$mime]) || $_FILES['image']['size'] > 1_000_000) { exit('File not allowed'); }
$name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];   // new random name
move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $name);
```

Also store files outside the application root, or disable PHP execution in Nginx for `uploads/`:

```
location ^~ /uploads/ { location ~ \.php$ { return 403; } }
```

### 11. Path traversal and local file read in the preview

Where: `preview.php` (parameter `file`).

How to observe it: open the preview of an uploaded file, then change the `file` parameter to a relative path that leaves `uploads/`, for example `../../includes/footer.php`. See that the server reads and shows a file outside the allowed directory. Use only files of the lab application itself. Also look at the error message, which reveals the full path on the server.

Correct fix:

```php
$base = realpath(__DIR__ . '/uploads');
$path = realpath($base . '/' . basename($file));
if ($path === false || strncmp($path, $base . DIRECTORY_SEPARATOR, strlen($base) + 1) !== 0) {
    http_response_code(404); exit('File not found');
}
```

Also show the content with `htmlspecialchars()` and only for allowed file types.

### 12. Information disclosure through errors

Where: `config.php`, `php/lab.ini`, `login.php`, `register.php`, `preview.php`.

How to observe it: trigger an SQL error (an apostrophe in the search box) and look at the full query text, file paths and PHP version in the response.

Correct fix: in production set `display_errors = Off` and `log_errors = On`, write errors to a log file, and show the user only a generic message. Also set `expose_php = Off`.

## File inclusion lab (isolated, PHP 5.4)

A second, separate exercise, isolated from the rest of the shop:

```
legacy-php/
  Dockerfile              php:5.4-fpm image
  lab.ini                 session settings for this exercise
  public/
    include_sink.php      the vulnerable sink
```

It runs as its own `legacy-php` service and its own Nginx port, `8081`, next to the shop's `8080`. It does not touch the shop's code, database or sessions in any way. This separation exists because PHP 5.4 does not understand the `??` operator the shop's own code uses everywhere (added in PHP 7.0), so running the whole shop on 5.4 would break every page; keeping it as a second, independent service lets this exercise use an old PHP build without breaking anything else.

### include_sink.php versus preview.php

Both read a file named in a URL parameter, but they are not the same class of bug. `preview.php` in the main shop (vulnerability 11 above) calls `file_get_contents()`, so whatever it reads comes back as text or triggers stored XSS if a browser renders it, but it is never run as code. `include_sink.php` calls `include()` instead, so if the target file contains a `<?php` block, that block executes on the server. That is Local File Inclusion with code execution, not just information disclosure.

```php
$file = isset($_GET['file']) ? $_GET['file'] : '';
if ($file && file_exists($file)) {
    include($file);
}
```

### The session configuration

`legacy-php/lab.ini` sets:

```ini
session.upload_progress.enabled = On
session.upload_progress.cleanup = Off
session.use_strict_mode = Off
session.use_only_cookies = Off
```

Historically, while a multipart file upload was still being received, PHP would write progress data, including a field name supplied by the client, into the session's own storage file. With `cleanup = Off` that data is not erased once the request completes. `use_strict_mode = Off` and `use_only_cookies = Off` mean the server will accept a session ID the client picks itself, rather than only one it generated. Together, this is what an LFI endpoint like `include_sink.php` needs to turn a file read into code execution: the attacker's own session file becomes a file on disk whose name and content they both control.

This specific technique does not work against the shop's own PHP 8.4.4 service. The race condition it depends on was closed in the PHP engine itself in versions 5.4.11 and 5.3.21 (released in 2012-2013), which is why this lab deliberately runs an old, unsupported PHP build to reproduce it rather than relying on configuration alone. This repository does not include a script that performs the actual race-condition exploit; the lab provides the vulnerable conditions for study, not a working attack tool.

### Verifying the environment

Confirm the PHP version in the isolated container (should show `5.4.x`, not the shop's `8.4.4`):

```bash
docker compose exec legacy-php php -v
```

Confirm the session settings took effect:

```bash
docker compose exec legacy-php php -i | grep -i upload_progress
docker compose exec legacy-php php -i | grep -i "session.use_strict_mode\|session.use_only_cookies"
```

Confirm the sink is reachable and the shop is unaffected:

```bash
curl -I http://127.0.0.1:8081/include_sink.php
curl -I http://127.0.0.1:8080/index.php
```

Note on `php:5.4-fpm`: this tag is old enough that it may have been removed from Docker Hub by the time you build this. If `docker compose build legacy-php` fails to pull the image, try `php:5.6-fpm` in `legacy-php/Dockerfile` instead (edit the `FROM` line), which is more likely to still be available; the rest of this lab works the same way under 5.6, since the same race condition was present in all PHP versions before 5.4.11 / 5.3.21.

## Discussion questions for the class

1. Which vulnerabilities can be chained to increase impact? For example, stored XSS together with the missing `HttpOnly` flag on the cookie.
2. Why do prepared statements solve SQL Injection, while filtering the apostrophe does not?
3. What traces would appear in the Nginx and PHP logs during each test? How would a SOC team detect them?
4. Which WAF rules would reduce each weakness, and why do they not replace fixing the code?

## Final exercise

Students create a branch with fixes for all vulnerabilities above and verify with the same harmless tests that the unprotected behavior no longer reproduces.
