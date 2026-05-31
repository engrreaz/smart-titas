# Smart Titas - Backend API Documentation

**Base URL:** `https://smarttitas.eimbox.com/api`

## General Guidelines
- All requests should be `POST`.
- Response format: `JSON`.
- Authentication: Use JWT (JSON Web Token) for protected routes.
- Success Response: `{"status": "success", "data": ...}`
- Error Response: `{"status": "error", "message": "Reason for failure"}`

---

## 1. Authentication

### `login.php`
- **Description:** Authenticate user and return JWT.
- **Params:** `phone`, `password`
- **Response:** `{"status": "success", "token": "JWT_TOKEN", "user": {id, name, role, ...}}`
- **SQL:** `SELECT * FROM users WHERE phone = ?`

### `register.php`
- **Description:** Create a new contributor account.
- **Params:** `name`, `phone`, `email`, `password`
- **Response:** `{"status": "success", "message": "User registered"}`
- **SQL:** `INSERT INTO users (name, phone, email, password, role) VALUES (?, ?, ?, ?, 'contributor')`

### `profile.php`
- **Description:** Get user details and contribution stats.
- **Params:** `user_id`
- **Headers:** `Authorization: Bearer <token>`
- **SQL:** 
    - `SELECT * FROM users WHERE id = ?`
    - `SELECT COUNT(*) as total_contributions FROM contributions WHERE user_id = ?`

---

## 2. Directories (Fetch Data)
*Note: All fetch APIs should return an array of objects.*

### `officials.php`
- **Params:** `action='list'`
- **SQL:** `SELECT * FROM officials ORDER BY designation ASC`

### `institutions.php`
- **Params:** `action='list'`
- **SQL:** `SELECT * FROM institutions`

### `donors.php`
- **Params:** `action='list'`
- **SQL:** `SELECT * FROM blood_donors WHERE available = 1`

### `professionals.php`
- **Params:** `action='list'`
- **SQL:** `SELECT * FROM professionals`

### `businesses.php`
- **Params:** `action='list'`
- **SQL:** `SELECT * FROM businesses`

### `emergency.php`
- **Params:** `action='list'`
- **SQL:** `SELECT * FROM emergency_contacts`

### `notices.php`
- **Params:** `action='list'`
- **SQL:** `SELECT * FROM notices ORDER BY created_at DESC`

---

## 3. Contribution System (Protected)
*All these require JWT in Header: `Authorization: Bearer <token>`*

### `add_entry.php`
- **Params:** `table_name`, `data` (JSON object)
- **Action:** Insert into specified table and log into `contributions` table.
- **Example Data:** `{"name": "...", "phone": "...", "designation": "..."}`

### `edit_request.php`
- **Params:** `item_type` (e.g., 'officials'), `item_id`, `changes` (JSON object)
- **Action:** Insert into `edit_requests` table for moderator approval.

### `verify.php`
- **Params:** `item_type`, `item_id`, `verification_level`
- **Action:** (Moderator only) Update `verification_level` in the target table and log in `verification_logs`.

### `report.php`
- **Params:** `item_type`, `item_id`, `reason`
- **Action:** Log entry in `reports` table.

---

## 4. Search & Sync

### `search.php`
- **Params:** `query`
- **Action:** Search across all directory tables.
- **SQL Example:** `SELECT 'official' as type, name, phone FROM officials WHERE name LIKE %?% UNION ...`

### `sync.php`
- **Params:** `last_sync_timestamp`
- **Action:** Return all records updated/created after the given timestamp across all tables. This is crucial for the offline-first approach.

---

## Manual Instructions for Backend Developer
1. **Password Security:** Use `password_hash()` with `PASSWORD_DEFAULT`.
2. **JWT Secret:** Use a strong secret key for token generation.
3. **CORS:** Ensure `Access-Control-Allow-Origin: *` is set for development.
4. **Database Character Set:** Ensure `utf8mb4` is used for Bengali support.
5. **JSON Handling:** Use `json_decode(file_get_contents('php://input'), true)` to read JSON POST data.
