# 🚀 Railway Deployment Guide — TLC Cookies Website

## What Was Changed

| File | Change |
|------|--------|
| `includes/connection.php` | Reads Railway MySQL env vars (`MYSQLHOST`, `MYSQLUSER`, etc.) with local dev fallbacks |
| `Dockerfile` | New — tells Railway how to build and run your PHP/Apache app |
| `docker-entrypoint.sh` | New — configures Apache to use Railway's dynamic `$PORT` |
| `.htaccess` | New — redirects root `/` to `pages/index.php` |

---

## Step 1 — Push Your Code to GitHub

Railway deploys from GitHub. If you haven't already:

1. Go to [github.com](https://github.com) → **New repository** → name it (e.g. `tlc-cookies`)
2. On your computer, open a terminal in your project folder and run:

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/tlc-cookies.git
git push -u origin main
```

---

## Step 2 — Create a Railway Account & Project

1. Go to [railway.app](https://railway.app) and sign up (free tier available)
2. Click **New Project**
3. Choose **Deploy from GitHub repo**
4. Authorize Railway to access your GitHub and select your repository
5. Railway will detect the `Dockerfile` and start building automatically

---

## Step 3 — Add a MySQL Database

Your site needs a MySQL database. Add one inside Railway:

1. In your Railway project dashboard, click **+ New Service**
2. Choose **Database → MySQL**
3. Railway creates a MySQL instance and automatically sets these environment variables in your project:
   - `MYSQLHOST`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
   - `MYSQLDATABASE`
   - `MYSQLPORT`

> ✅ Your `connection.php` already reads these variables — **no extra config needed!**

The database tables and admin account are created automatically on first page load.

---

## Step 4 — Connect the Database to Your Web Service

1. Click on your **web service** (the PHP app) in the Railway dashboard
2. Go to **Variables** tab
3. Click **+ Add Variable Reference** → select the MySQL service
4. This links the MySQL env vars into your web service

---

## Step 5 — Get Your Live URL

1. Click on your web service
2. Go to **Settings → Networking**
3. Click **Generate Domain** — you'll get a URL like `tlc-cookies.up.railway.app`
4. Visit the URL — your site is live! 🎉

The first visit initializes all database tables and creates the admin account.

---

## Step 6 — Admin Login

Once deployed, go to:

```
https://YOUR-DOMAIN.up.railway.app/admin/login.php
```

Default credentials:
- **Email:** `tlcookies@gmail.com`
- **Password:** `@tlc2026`

> ⚠️ Change the admin password immediately after first login!

---

## ⚠️ Important: Uploaded Images

Railway's filesystem is **ephemeral** — uploaded product/banner/profile images will be **deleted on every redeploy**.

For production use, you should store uploads in a cloud service. Two easy options:

### Option A — Cloudinary (recommended, free tier)
1. Sign up at [cloudinary.com](https://cloudinary.com)
2. Use their PHP SDK to upload images instead of `move_uploaded_file()`

### Option B — Backblaze B2 or AWS S3
Similar approach — store files in object storage, save the URL in the database.

For now (testing/demo), local uploads work fine between deploys.

---

## Local Development (Still Works)

Your site still works locally with XAMPP/WAMP/Laragon:

- `connection.php` falls back to `localhost`, `root`, empty password, and `tlcookies_db`
- No changes needed for local dev

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Build fails | Check the Railway build logs tab for PHP/Apache errors |
| "Database connection failed" | Make sure MySQL service is added and variables are linked to the web service |
| Images not showing after redeploy | Expected on Railway free tier — see the uploads note above |
| 500 error on pages | Check Railway logs → usually a PHP error; look for missing `$conn` or wrong file paths |
| Site shows directory listing | The `.htaccess` redirect is not working — make sure `mod_rewrite` is enabled (it is in the Dockerfile) |
