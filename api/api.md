# Smart Titas - Backend API Specification (Final Comprehensive Guide)

**Base URL:** `https://smarttitas.eimbox.com/api/`

---

## 1. Authentication APIs (`auth/`)

### A. Login (`auth/login.php`)
- **Method:** `POST`
- **Parameters:** `phone`, `password`, `device_id`
- **Query:** `SELECT * FROM users WHERE phone = ?` (Verify hashed password in PHP)
- **Response:**
```json
{
  "status": "success",
  "token": "JWT_TOKEN",
  "user": { "id": 1, "name": "User Name", "phone": "01700000000", "role": "contributor", "trustScore": 10, "levelName": "Bronze" }
}
```

### B. Register (`auth/register.php`)
- **Method:** `POST`
- **Parameters:** `name`, `phone`, `password`, `email`, `device_id`
- **Query:** `INSERT INTO users (name, phone, password, email, role, device_id) VALUES (?, ?, ?, ?, 'contributor', ?)`
- **Response:** `{"status": "success", "message": "User registered successfully"}`

---

## 2. Data Synchronization APIs (`data/`)
*All categories follow Delta Sync using `last_sync` (UNIX Timestamp).*
*Only records with `status = 'approved'` should be returned.*

- **Endpoints:** `officials.php`, `institutions.php`, `donors.php`, `professionals.php`, `businesses.php`, `emergency.php`, `notices.php`, `tourism.php`.
- **Method:** `POST`
- **Parameters:** `last_sync` (Integer)
- **Common Logic:** `SELECT * FROM table_name WHERE UNIX_TIMESTAMP(updated_at) > ? AND status = 'approved'`
- **Sample Response (officials.php):**
```json
[
  {
    "id": 1,
    "name": "Rahim Uddin",
    "designation": "UNO",
    "institution": "Upazila Parishad",
    "phone": "017XXXXXXXX",
    "verificationLevel": "official",
    "updated_at": "2023-10-01 12:00:00"
  }
]
```

---

## 3. Action & Moderation APIs (`action/`)

### A. Add Entry (`action/add_entry.php`)
- **Method:** `POST`
- **Header:** `Authorization: Bearer <token>`
- **Parameters:** `type` (official|institution|donor|etc), `data` (JSON String), `device_id`
- **Query:** `INSERT INTO [table_name] (field1, field2, ..., status) VALUES (?, ?, ..., 'pending')`
- **Response:** `{"status": "success", "message": "Entry submitted for moderation"}`

### B. Moderate Item (`action/moderate.php`)
- **Method:** `POST`
- **Header:** `Authorization: Bearer <token>`
- **Restriction:** Role must be `moderator` or `super_admin`.
- **Parameters:** `item_type`, `item_id`, `action` (approve|reject)
- **Query:** `UPDATE [table_name] SET status = ? WHERE id = ?`
- **Point System Logic (PHP):** 
  - If `action == 'approve'`, Update user's `trust_score`: `UPDATE users SET trust_score = trust_score + 10 WHERE id = (SELECT created_by FROM [table] WHERE id = ?)`

### C. Verify Item (`action/verify.php`)
- **Method:** `POST`
- **Header:** `Authorization: Bearer <token>`
- **Parameters:** `item_type`, `item_id`, `level` (community|moderator|official|trusted)
- **Query:** `UPDATE [table_name] SET verification_level = ? WHERE id = ?`

---

## 4. Manual Instructions for Backend Developer

### Database Table Changes:
1. **`status` Column:** প্রতিটি ডাটা টেবিলে (officials, institutions, etc.) একটি `status` কলাম যোগ করুন: `ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'`.
2. **`updated_at` Column:** ডেল্টা সিঙ্কের জন্য `updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` কলামটি নিশ্চিত করুন।
3. **`uploads/` Folder:** রুট ডিরেক্টরিতে `uploads/` ফোল্ডার তৈরি করুন এবং `775` পারমিশন দিন। ইমেজ গুলো `https://smarttitas.eimbox.com/uploads/` পাথ থেকে এক্সেসযোগ্য হবে।

### Security:
1. **JWT Secret:** একটি শক্তিশালী সিক্রেট কি ব্যবহার করুন।
2. **Rate Limiting:** প্রতি ডিভাইসে দিনে সর্বোচ্চ ১০টি নতুন এন্ট্রি বা ২০টি এডিট রিকোয়েস্ট সীমাবদ্ধ করুন।
3. **FCM:** নতুন কোনো নোটিশ (`notice`) পাবলিশ হলে অটোমেটিক পুশ নোটিফিকেশন ট্রিগার করুন।

---
*এই ডকুমেন্টেশন অনুযায়ী ব্যাকএন্ড ডেভেলপমেন্ট সম্পন্ন করলে অ্যাপটি পুরোপুরি লাইভ ব্যবহারের উপযোগী হবে।*
