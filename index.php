<?php
require_once 'api/db.php';
// Override the JSON content type set by db.php
header('Content-Type: text/html; charset=utf-8');

// Optional: create the table if it does not exist
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS friends (
        id INT AUTO_INCREMENT PRIMARY KEY,
        friend_name VARCHAR(150) NOT NULL,
        mobile_no VARCHAR(20) NOT NULL,
        address TEXT,
        total_member INT DEFAULT 1,
        pick_point VARCHAR(150),
        updated_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Ignore if permission denied or similar, just proceed
}

// Handle Form Submission (Add/Edit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $friend_name = $_POST['friend_name'] ?? '';
    $mobile_no = $_POST['mobile_no'] ?? '';
    $address = $_POST['address'] ?? '';
    $total_member = $_POST['total_member'] ?? 1;
    $pick_point = $_POST['pick_point'] ?? 'Undefined';

    if (!empty($id)) {
        $stmt = $conn->prepare("UPDATE friends SET friend_name=?, mobile_no=?, address=?, total_member=?, pick_point=? WHERE id=?");
        $stmt->execute([$friend_name, $mobile_no, $address, $total_member, $pick_point, $id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO friends (friend_name, mobile_no, address, total_member, pick_point) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$friend_name, $mobile_no, $address, $total_member, $pick_point]);
    }
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['pick_point']) ? '?pick_point=' . urlencode($_GET['pick_point']) : ''));
    exit();
}

// Get filter
$filter_pick_point = isset($_GET['pick_point']) ? $_GET['pick_point'] : 'All';
$points = ['All', 'Batakandi', 'Gazipur', 'Karikandi', 'Dhaka', 'Undefined'];

// Fetch data
$query = "SELECT * FROM friends";
$params = [];
if ($filter_pick_point !== 'All') {
    $query .= " WHERE pick_point = ?";
    $params[] = $filter_pick_point;
}
$query .= " ORDER BY friend_name ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$friends = $stmt->fetchAll();

$total_friends_count = count($friends);
$total_members_count = array_sum(array_column($friends, 'total_member'));
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ফ্রেন্ডস ম্যানেজমেন্ট</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --md-sys-color-primary: #006874;
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-primary-container: #97f0ff;
            --md-sys-color-on-primary-container: #001f24;
            --md-sys-color-secondary-container: #cce8e2;
            --md-sys-color-on-secondary-container: #05201b;
            --md-sys-color-surface: #fbfdfd;
            --md-sys-color-on-surface: #191c1d;
            --md-sys-color-surface-variant: #dbe4e6;
        }

        body {
            background-color: var(--md-sys-color-surface);
            color: var(--md-sys-color-on-surface);
            font-family: 'Noto Sans Bengali', sans-serif;
            padding-bottom: 5rem;
        }
        
        .top-app-bar {
            background-color: var(--md-sys-color-primary-container);
            color: var(--md-sys-color-on-primary-container);
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .filter-nav .nav-link {
            color: var(--md-sys-color-on-surface);
            border-radius: 24px;
            margin: 0 0.3rem;
            padding: 0.5rem 1.25rem;
            background-color: var(--md-sys-color-surface-variant);
            margin-bottom: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        
        .filter-nav .nav-link:hover {
            background-color: #c4d0d3;
        }

        .filter-nav .nav-link.active {
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
            box-shadow: 0 4px 8px rgba(0, 104, 116, 0.25);
        }

        .card-tonal {
            background-color: var(--md-sys-color-secondary-container);
            color: var(--md-sys-color-on-secondary-container);
            border: none;
            border-radius: 16px;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-tonal:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }
        
        .card-title {
            color: var(--md-sys-color-on-secondary-container);
        }

        .btn-fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background-color: var(--md-sys-color-primary-container);
            color: var(--md-sys-color-on-primary-container);
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            border: none;
            transition: background-color 0.2s, transform 0.2s;
            z-index: 1000;
        }

        .btn-fab:hover {
            background-color: #79e3f4;
            transform: scale(1.05);
        }

        .btn-tonal {
            background-color: rgba(0, 104, 116, 0.12);
            color: #006874;
            border-radius: 20px;
            border: none;
            padding: 0.4rem 1.2rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .btn-tonal:hover {
            background-color: rgba(0, 104, 116, 0.2);
        }

        .btn-primary-custom {
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
            border-radius: 20px;
            border: none;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }

        .modal-content {
            border-radius: 24px;
            border: none;
            box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12), 0 11px 15px -7px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            border-bottom: none;
            padding: 1.5rem 1.5rem 0.5rem;
        }
        
        .modal-body {
            padding: 1rem 1.5rem;
        }
        
        .modal-footer {
            border-top: none;
            padding: 0.5rem 1.5rem 1.5rem;
        }

        .form-control, .form-select {
            border-radius: 12px;
            background-color: var(--md-sys-color-surface-variant);
            border: none;
            padding: 0.75rem 1rem;
            color: var(--md-sys-color-on-surface);
        }
        .form-control:focus, .form-select:focus {
            background-color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(0, 104, 116, 0.25);
        }
        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            color: #49454f;
        }
    </style>
</head>
<body>

    <header class="top-app-bar text-center">
        <h2 class="mb-0 fw-bold"><i class="bi bi-people-fill"></i> ফ্রেন্ডস ডিরেক্টরি</h2>
    </header>

    <div class="container">
        <!-- Filter Options -->
        <div class="d-flex flex-wrap justify-content-center filter-nav mb-3">
            <?php foreach($points as $point): ?>
                <a href="?pick_point=<?= urlencode($point) ?>" class="nav-link fw-tiny text-decoration-none <?= $filter_pick_point == $point ? 'active' : '' ?>" >
                    <?= $point ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Summary Section -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-tonal" style="background-color: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container);">
                    <div class="card-body py-2 px-3 d-flex justify-content-around align-items-center">
                        <span class="fw-bold fs-6">
                            <i class="bi bi-people-fill me-1" style="color: var(--md-sys-color-primary);"></i> মোট বন্ধু: <?= $total_friends_count ?>
                        </span>
                        <span class="fw-bold fs-6">
                            <i class="bi bi-person-lines-fill me-1" style="color: var(--md-sys-color-primary);"></i> মোট সদস্য: <?= $total_members_count ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards Grid -->
        <div class="row g-3">
            <?php if(count($friends) > 0): ?>
                <?php foreach($friends as $friend): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-tonal">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title fw-bold mb-0">
                                        <i class="bi bi-person-circle" style="color: #006874;"></i> <?= htmlspecialchars($friend['friend_name']) ?>
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-tonal py-0 px-2" style="font-size: 0.75rem;" onclick='editFriend(<?= json_encode($friend, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                                <p class="card-text mb-1" style="font-size: 0.85rem;">
                                    <i class="bi bi-telephone-fill text-muted me-1"></i> <a href="tel:<?= htmlspecialchars($friend['mobile_no']) ?>" class="text-decoration-none fw-medium" style="color: inherit;"><?= htmlspecialchars($friend['mobile_no']) ?></a>
                                </p>
                                <p class="card-text mb-1" style="font-size: 0.85rem;">
                                    <i class="bi bi-geo-alt-fill text-muted me-1"></i> <?= htmlspecialchars($friend['address']) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top" style="border-color: rgba(0,0,0,0.05) !important;">
                                    <p class="card-text mb-0" style="font-size: 0.85rem;">
                                        <i class="bi bi-people text-muted me-1"></i> সদস্য: <strong><?= htmlspecialchars($friend['total_member']) ?></strong>
                                    </p>
                                    <span class="badge rounded-pill" style="background-color: #006874; font-weight: 500; font-size: 0.65rem;"><i class="bi bi-pin-map-fill"></i> <?= htmlspecialchars($friend['pick_point']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted mt-5">
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mt-2 fs-5">কোনো ডেটা পাওয়া যায়নি</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="btn-fab" data-bs-toggle="modal" data-bs-target="#friendModal" onclick="resetForm()" title="নতুন ফ্রেন্ড যোগ করুন">
        <i class="bi bi-plus-lg"></i>
    </button>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="friendModal" tabindex="-1" aria-labelledby="friendModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold" id="friendModalLabel"><i class="bi bi-person-plus-fill text-primary"></i> নতুন ফ্রেন্ড যোগ করুন</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="?pick_point=<?= urlencode($filter_pick_point) ?>">
              <div class="modal-body">
                <input type="hidden" name="id" id="friend_id">
                
                <div class="mb-3">
                    <label class="form-label">নাম *</label>
                    <input type="text" name="friend_name" id="friend_name" class="form-control" required placeholder="সম্পূর্ণ নাম লিখুন">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">মোবাইল নম্বর *</label>
                    <input type="text" name="mobile_no" id="mobile_no" class="form-control" required placeholder="যেমন: 01700000000">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">ঠিকানা</label>
                    <textarea name="address" id="address" class="form-control" rows="2" placeholder="বিস্তারিত ঠিকানা"></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label">সদস্য সংখ্যা</label>
                        <input type="number" name="total_member" id="total_member" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label">পিক পয়েন্ট</label>
                        <select name="pick_point" id="pick_point" class="form-select">
                            <option value="Undefined">Undefined</option>
                            <option value="Batakandi">Batakandi</option>
                            <option value="Gazipur">Gazipur</option>
                            <option value="Karikandi">Karikandi</option>
                            <option value="Dhaka">Dhaka</option>
                        </select>
                    </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">বাতিল</button>
                <button type="submit" class="btn btn-primary-custom" id="saveBtn">সেইভ করুন</button>
              </div>
          </form>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('friendModalLabel').innerHTML = '<i class="bi bi-person-plus-fill" style="color: #006874;"></i> নতুন ফ্রেন্ড যোগ করুন';
            document.getElementById('saveBtn').innerText = 'সেইভ করুন';
            document.getElementById('friend_id').value = '';
            document.getElementById('friend_name').value = '';
            document.getElementById('mobile_no').value = '';
            document.getElementById('address').value = '';
            document.getElementById('total_member').value = '1';
            document.getElementById('pick_point').value = 'Undefined';
        }

        function editFriend(data) {
            document.getElementById('friendModalLabel').innerHTML = '<i class="bi bi-pencil-square" style="color: #006874;"></i> ফ্রেন্ড এডিট করুন';
            document.getElementById('saveBtn').innerText = 'আপডেট করুন';
            document.getElementById('friend_id').value = data.id;
            document.getElementById('friend_name').value = data.friend_name;
            document.getElementById('mobile_no').value = data.mobile_no;
            document.getElementById('address').value = data.address;
            document.getElementById('total_member').value = data.total_member;
            
            // Handle pick point dropdown
            let pickPointStr = data.pick_point;
            let options = document.getElementById('pick_point').options;
            let found = false;
            for(let i=0; i<options.length; i++) {
                if(options[i].value === pickPointStr) {
                    document.getElementById('pick_point').value = pickPointStr;
                    found = true;
                    break;
                }
            }
            if(!found) {
                document.getElementById('pick_point').value = 'Undefined';
            }
            
            var modal = new bootstrap.Modal(document.getElementById('friendModal'));
            modal.show();
        }
    </script>
</body>
</html>