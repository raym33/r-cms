# LinuxCMS Quickstart

## Fast local start

### Option A: built-in PHP server

```bash
bash start-local.sh
```

Then open:

- `http://127.0.0.1:8088/install.php`

### Option B: Docker

```bash
docker compose up --build
```

Then open:

- `http://127.0.0.1:8088/install.php`

## First install

1. Open `/install.php`.
2. Choose storage:
   - `SQLite` if available
   - `JSON` if you need maximum hosting compatibility
3. Create the first admin account.
4. Log in at `/r-admin`.

## First useful setup

After login:

1. Go to `Site`
   - set title
   - set tagline
   - set contact email
   - choose theme preset
2. Go to `Pages`
   - create homepage
3. Go to `Media`
   - upload logo and main images
4. Go to `Studio`
   - if you want LM Studio to generate a first draft

## First commercial checklist

Before delivering a site:

- homepage works
- contact form works
- inbox stores submissions
- analytics configured
- sitemap and robots are reachable
- admin user password changed
- 2FA enabled for owner
- backup exported

## Public routes to test

- `/`
- `/r-admin`
- `/api/health`
- `/api/forms/submit`
- `/sitemap.xml`
- `/robots.txt`
