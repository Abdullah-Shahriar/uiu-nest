<?php
/**
 * UIU Nest v2 — Full Reset & Seed Script
 * Run once: http://localhost/GitHub/uiu-nest/test_auth.php
 * Wipes old demo data and inserts the correct users, properties, rooms, listings.
 */
require_once __DIR__ . '/config/database.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>UIU Nest — Setup</title>
<style>
body  { font-family: monospace; background: #060c1a; color: #e2e8f0; padding: 30px; font-size: 14px; line-height: 1.9; }
h2   { color: #38bdf8; margin: 24px 0 6px; border-bottom: 1px solid #1e3a5f; padding-bottom: 4px; }
.ok  { color: #34d399; }
.err { color: #f87171; }
.warn{ color: #fbbf24; }
table { border-collapse: collapse; margin: 12px 0; width: 100%; }
th, td { padding: 10px 16px; border: 1px solid #1e3a5f; text-align: left; }
th { background: #0e1830; color: #38bdf8; }
tr:hover td { background: #0a1626; }
.btn { display:inline-block; margin-top:24px; padding:14px 32px; background:linear-gradient(135deg,#2563eb,#0ea5e9); color:#fff; border-radius:8px; text-decoration:none; font-size:15px; font-weight:600; }
pre  { background:#0e1830; padding:16px; border-radius:8px; border-left:4px solid #34d399; margin-top:20px; }
hr   { border:none; border-top:1px solid #1e3a5f; margin:20px 0; }
</style>
</head>
<body>

<h2>UIU Nest v2 — Reset & Setup</h2>
<hr>

<?php
$db = getDB();

// ══════════════════════════════════════════════════════════════
// STEP 1 — Schema checks & table creation
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 1: Schema</h2>';
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `property_images` (
      `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `property_id` INT UNSIGNED NOT NULL,
      `image_path`  VARCHAR(500) NOT NULL,
      `is_cover`    TINYINT(1)   NOT NULL DEFAULT 0,
      `sort_order`  INT          NOT NULL DEFAULT 0,
      `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      INDEX `idx_propimg_property` (`property_id`),
      CONSTRAINT `fk_propimg_property` FOREIGN KEY (`property_id`)
        REFERENCES `properties` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `owner_applications` (
      `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
      `full_name`             VARCHAR(255)    NOT NULL,
      `email`                 VARCHAR(255)    NOT NULL,
      `phone`                 VARCHAR(20)     NOT NULL,
      `address`               TEXT            NOT NULL,
      `nid_path`              VARCHAR(500)    NOT NULL,
      `electricity_bill_path` VARCHAR(500)    NOT NULL,
      `photo_path`            VARCHAR(500)    NOT NULL,
      `extra_info`            TEXT,
      `status`                ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
      `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `complaints` (
      `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `category`     ENUM('maintenance','noise','safety','management','other') NOT NULL DEFAULT 'other',
      `property_id`  INT UNSIGNED DEFAULT NULL,
      `subject`      VARCHAR(255) NOT NULL,
      `description`  TEXT NOT NULL,
      `submitter_id` INT UNSIGNED DEFAULT NULL,
      `is_anonymous` TINYINT(1) NOT NULL DEFAULT 1,
      `status`       ENUM('open','under_review','resolved','dismissed') NOT NULL DEFAULT 'open',
      `admin_note`   TEXT DEFAULT NULL,
      `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `resolved_at`  DATETIME DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Add columns if missing
    foreach ([
        "ALTER TABLE properties ADD COLUMN house_manager_id INT UNSIGNED DEFAULT NULL",
        "ALTER TABLE applications ADD COLUMN applied_at DATETIME DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN avatar_path VARCHAR(500) DEFAULT NULL",
        "ALTER TABLE properties ADD COLUMN image_path VARCHAR(500) DEFAULT NULL",
    ] as $sql) {
        try { $db->exec($sql); echo '<span class="ok">Added: ' . substr($sql, 20, 40) . '...</span><br>'; }
        catch (Exception $ex) { echo '<span class="warn">Already exists: ' . substr($sql, 20, 35) . '</span><br>'; }
    }
    echo '<span class="ok">Schema OK</span><br>';
} catch (Exception $e) {
    echo '<span class="err">Schema error: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
}

// ══════════════════════════════════════════════════════════════
// STEP 2 — Refresh Amenities (SVG slug icons, no emojis)
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 2: Amenities</h2>';
try {
    $db->exec("DELETE FROM amenities");
    $db->exec("INSERT INTO amenities (slug, label, icon, sort_order, is_active) VALUES
      ('wifi',          'High-Speed WiFi',   'wifi',           1, 1),
      ('ac',            'Air Conditioning',  'wind',           2, 1),
      ('attached_bath', 'Attached Bathroom', 'shower-head',    3, 1),
      ('shared_bath',   'Shared Bathroom',   'bath',           4, 1),
      ('furnished',     'Fully Furnished',   'sofa',           5, 1),
      ('balcony',       'Private Balcony',   'sun',            6, 1),
      ('parking',       'Parking Space',     'car',            7, 1),
      ('laundry',       'Laundry Access',    'washing-machine',8, 1),
      ('security',      '24/7 Security',     'shield',         9, 1),
      ('cctv',          'CCTV Surveillance', 'camera',         10, 1),
      ('study_room',    'Study Room',        'book-open',      11, 1),
      ('rooftop',       'Rooftop Access',    'building',       12, 1),
      ('generator',     'Backup Generator',  'zap',            13, 1),
      ('lift',          'Elevator/Lift',     'arrow-up',       14, 1)
    ");
    echo '<span class="ok">14 amenities inserted</span><br>';
} catch (Exception $e) {
    echo '<span class="err">Amenities: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
}

// ══════════════════════════════════════════════════════════════
// STEP 3 — Upload Directories
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 3: Upload Directories</h2>';
foreach ([
    UPLOAD_DIR . '/avatars'            => 'uploads/avatars',
    UPLOAD_DIR . '/properties'         => 'uploads/properties',
    UPLOAD_DIR . '/owner_applications' => 'uploads/owner_applications',
    DOC_UPLOAD_DIR                     => 'uploads/documents',
] as $path => $label) {
    if (!is_dir($path)) mkdir($path, 0755, true);
    echo '<span class="ok">' . $label . '</span><br>';
}

// ══════════════════════════════════════════════════════════════
// STEP 4 — Wipe & Reseed Users, Properties, Rooms, Listings
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 4: Data Reset</h2>';
$db->exec('SET FOREIGN_KEY_CHECKS = 0');
$db->exec("DELETE FROM room_tenants");
$db->exec("DELETE FROM listing_requirements");
$db->exec("DELETE FROM listings");
$db->exec("DELETE FROM rooms");
$db->exec("DELETE FROM property_images");
$db->exec("DELETE FROM properties");
$db->exec("DELETE FROM applications");
$db->exec("DELETE FROM saved_listings");
$db->exec("DELETE FROM complaints");
$db->exec("DELETE FROM users WHERE role != 'admin'");
// Clean orphan admin data too
$db->exec("DELETE FROM properties WHERE owner_id NOT IN (SELECT id FROM users)");
$db->exec('SET FOREIGN_KEY_CHECKS = 1');
echo '<span class="ok">Old data cleared</span><br>';

$hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);

// Reset admin password
$db->prepare("UPDATE users SET password_hash = ?, is_active = 1 WHERE role = 'admin'")->execute([$hash]);
echo '<span class="ok">Admin password reset to admin123</span><br>';

// Get admin id (Abdullah Shahriar is admin)
$adminUser = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
$adminId = $adminUser ? (int)$adminUser['id'] : null;

// ── Insert Shahed Khan (Owner) ─────────────────────────────────────────────
$db->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified_at, phone)
              VALUES ('Shahed Khan', 'shahed@gmail.com', ?, 'owner', 1, NOW(), '+8801711111111')")
   ->execute([$hash]);
$shahedId = (int)$db->lastInsertId();
echo '<span class="ok">Shahed Khan (Owner) → ID: ' . $shahedId . '</span><br>';

// ── Insert Ritu Datta (House Manager) ─────────────────────────────────────
$db->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified_at, student_id, department, year_of_study, gender)
              VALUES ('Ritu Datta', 'ritu@bscse.uiu.ac.bd', ?, 'tenant', 1, NOW(), '011221050', 'CSE', 4, 'female')")
   ->execute([$hash]);
$rituId = (int)$db->lastInsertId();
echo '<span class="ok">Ritu Datta (House Manager) → ID: ' . $rituId . '</span><br>';

// ── Insert Tahsin Faiyaz (Student) ────────────────────────────────────────
$db->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified_at, student_id, department, year_of_study, gender)
              VALUES ('Tahsin Faiyaz', 'tahsin@bscse.uiu.ac.bd', ?, 'student', 1, NOW(), '011221001', 'CSE', 3, 'male')")
   ->execute([$hash]);
$tahsinId = (int)$db->lastInsertId();
echo '<span class="ok">Tahsin Faiyaz (Student) → ID: ' . $tahsinId . '</span><br>';

// ══════════════════════════════════════════════════════════════
// STEP 5 — Seed Properties
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 5: Properties</h2>';

// Allowed domains (add gmail for owners)
$db->exec("DELETE FROM allowed_domains");
$db->exec("INSERT INTO allowed_domains (domain) VALUES ('uiu.ac.bd'),('bscse.uiu.ac.bd'),('student.uiu.ac.bd'),('gmail.com')");

// ── Shahed Khan — Sayeed Nagar, Badda ────────────────────────
$db->prepare("INSERT INTO properties (owner_id, name, address, location_lat, location_lng, description, is_active) VALUES
  (?, 'Nikunja Villa',    'Sayeed Nagar, Badda, Dhaka-1212', 23.7991, 90.4505, 'A premium gated residence in Sayeed Nagar. Features modern amenities and 24/7 security for a safe student life.', 1),
  (?, 'Mukul Villa',      'Sayeed Nagar, Badda, Dhaka-1212', 23.7988, 90.4508, 'Well-maintained family hostel with large rooms and a peaceful environment. Close to public transport.', 1),
  (?, 'Rupali Kuthir',    'Sayeed Nagar, Badda, Dhaka-1212', 23.7985, 90.4512, 'Budget-friendly compact rooms. Ideal for students who prefer a quiet, focused study environment.', 1),
  (?, 'Nilachol Nibash',  'Satarkul Road, Badda, Dhaka-1212', 23.7960, 90.4550, 'Spacious 6-storey building with rooftop access and CCTV coverage throughout the premises.', 1)
")->execute([$shahedId, $shahedId, $shahedId, $shahedId]);
echo '<span class="ok">4 properties added under Shahed Khan</span><br>';

// ── Abdullah Shahriar (Admin as Owner) — Basundhara R/A ──────
if ($adminId) {
    $db->prepare("INSERT INTO properties (owner_id, name, address, location_lat, location_lng, description, is_active) VALUES
      (?, 'Tasin Villa',        'Block-D, Basundhara R/A, Dhaka-1229', 23.8120, 90.4260, 'Premium villa-style residence in Basundhara. Fully gated with generator backup, CCTV, and covered parking.', 1),
      (?, 'Campus Edge Hostel', 'Block-C, Basundhara R/A, Dhaka-1229', 23.8115, 90.4255, 'Go-to hostel for UIU students. Located minutes from campus with shared study lounges and fast WiFi.', 1),
      (?, 'Simanto Kuthir',     'Block-E, Basundhara R/A, Dhaka-1229', 23.8125, 90.4270, 'Affordable and clean. Ideal for first-year students seeking a secure, friendly hostel environment.', 1)
    ")->execute([$adminId, $adminId, $adminId]);
    echo '<span class="ok">3 properties added under Abdullah Shahriar (Admin)</span><br>';
}

// ══════════════════════════════════════════════════════════════
// STEP 6 — Seed Rooms
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 6: Rooms</h2>';

$shahedProps = $db->query("SELECT id FROM properties WHERE owner_id = $shahedId ORDER BY id ASC")->fetchAll();
[$pNikunja, $pMukul, $pRupali, $pNilachol] = array_column($shahedProps, 'id');

$db->prepare("INSERT INTO rooms (property_id, room_number, capacity, current_occupancy, rent_amount, amenities_json, description) VALUES
  (?, '101', 2, 0, 8000.00, '[\"wifi\",\"ac\",\"attached_bath\",\"furnished\"]',          'Spacious double room, fully AC with attached bath.'),
  (?, '102', 1, 0, 6500.00, '[\"wifi\",\"ac\",\"shared_bath\"]',                          'Cozy single room with fan & shared bath.'),
  (?, 'A1',  2, 1, 7500.00, '[\"wifi\",\"ac\",\"attached_bath\",\"furnished\"]',          'Double room — Ritu Datta currently resides here.'),
  (?, 'A2',  1, 0, 5500.00, '[\"wifi\",\"shared_bath\"]',                                  'Single room, quiet corner of the building.'),
  (?, 'G1',  1, 0, 5000.00, '[\"wifi\",\"shared_bath\"]',                                  'Budget single near entrance.'),
  (?, 'G2',  2, 0, 7000.00, '[\"wifi\",\"ac\",\"shared_bath\"]',                           'Double room with AC.'),
  (?, 'B1',  2, 0, 9000.00, '[\"wifi\",\"ac\",\"attached_bath\",\"furnished\",\"cctv\",\"security\"]', 'Premium double room with full security amenities.'),
  (?, 'B2',  3, 0, 7000.00, '[\"wifi\",\"shared_bath\",\"cctv\"]',                         'Triple-sharing room with CCTV.')
")->execute([$pNikunja, $pNikunja, $pMukul, $pMukul, $pRupali, $pRupali, $pNilachol, $pNilachol]);
echo '<span class="ok">8 rooms added for Shahed Khan properties</span><br>';

if ($adminId) {
    $adminProps = $db->query("SELECT id FROM properties WHERE owner_id = $adminId ORDER BY id ASC")->fetchAll();
    [$pTasin, $pCampusEdge, $pSimanto] = array_column($adminProps, 'id');

    $db->prepare("INSERT INTO rooms (property_id, room_number, capacity, current_occupancy, rent_amount, amenities_json, description) VALUES
      (?, 'T1', 2, 0, 10000.00, '[\"wifi\",\"ac\",\"attached_bath\",\"furnished\",\"parking\",\"generator\"]', 'Premium double room with all facilities.'),
      (?, 'T2', 1, 0,  7500.00, '[\"wifi\",\"ac\",\"shared_bath\",\"furnished\"]',                             'Single room with AC and shared bath.'),
      (?, 'C1', 1, 0,  5500.00, '[\"wifi\",\"shared_bath\",\"study_room\"]',                                   'Single room, study lounge access.'),
      (?, 'C2', 2, 0,  8500.00, '[\"wifi\",\"ac\",\"attached_bath\",\"study_room\"]',                          'Double room with AC and attached bath.'),
      (?, 'S1', 1, 0,  4500.00, '[\"wifi\",\"shared_bath\"]',                                                  'Affordable single room.'),
      (?, 'S2', 2, 0,  6000.00, '[\"wifi\",\"ac\",\"shared_bath\"]',                                           'Double room with AC.')
    ")->execute([$pTasin, $pTasin, $pCampusEdge, $pCampusEdge, $pSimanto, $pSimanto]);
    echo '<span class="ok">6 rooms added for Abdullah Shahriar properties</span><br>';
}

// ══════════════════════════════════════════════════════════════
// STEP 7 — Ritu Datta as room_tenant in Mukul Villa A1
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 7: Room Tenants</h2>';
$mukulA1 = $db->query("SELECT id FROM rooms WHERE property_id = $pMukul AND room_number = 'A1' LIMIT 1")->fetchColumn();
if ($mukulA1) {
    $db->prepare("INSERT INTO room_tenants (room_id, user_id, moved_in_at) VALUES (?, ?, NOW())")->execute([$mukulA1, $rituId]);
    echo '<span class="ok">Ritu Datta assigned to Mukul Villa Room A1</span><br>';
}

// ══════════════════════════════════════════════════════════════
// STEP 8 — Seed Listings
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 8: Listings</h2>';

$allRooms = $db->query("SELECT r.id, r.room_number, p.owner_id, p.name AS pname FROM rooms r JOIN properties p ON p.id = r.property_id ORDER BY r.id ASC")->fetchAll();

foreach ($allRooms as $room) {
    $listingTitles = [
        'Nikunja Villa'    => ['101' => 'Double Room at Nikunja Villa — AC & Attached Bath', '102' => 'Single Room at Nikunja Villa'],
        'Mukul Villa'      => ['A2'  => 'Single Room Available at Mukul Villa'],
        'Rupali Kuthir'    => ['G1'  => 'Budget Single Room — Rupali Kuthir', 'G2' => 'Double Room at Rupali Kuthir — AC'],
        'Nilachol Nibash'  => ['B1'  => 'Premium Double Room at Nilachol Nibash', 'B2' => 'Triple Sharing — Nilachol Nibash'],
        'Tasin Villa'      => ['T1'  => 'Premium Double Room — Tasin Villa, Basundhara', 'T2' => 'Single Room at Tasin Villa'],
        'Campus Edge Hostel' => ['C1' => 'Single Room at Campus Edge Hostel', 'C2' => 'Double Room — Campus Edge, AC & Attached Bath'],
        'Simanto Kuthir'   => ['S1'  => 'Budget Single — Simanto Kuthir, Basundhara', 'S2' => 'Double Room at Simanto Kuthir'],
    ];
    $title = $listingTitles[$room['pname']][$room['room_number']] ?? null;
    if ($title) {
        // Skip Mukul Villa A1 (occupied by Ritu Datta, no listing)
        if ($room['pname'] === 'Mukul Villa' && $room['room_number'] === 'A1') continue;
        $db->prepare("INSERT INTO listings (room_id, created_by, listing_type, title, description, status, published_at)
                      VALUES (?, ?, 'owner_direct', ?, '', 'published', NOW())")
           ->execute([$room['id'], $room['owner_id'], $title]);
    }
}
$lCount = $db->query("SELECT COUNT(*) FROM listings WHERE status = 'published'")->fetchColumn();
echo '<span class="ok">' . $lCount . ' listings published</span><br>';

// ══════════════════════════════════════════════════════════════
// STEP 9 — Show users table
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 9: Final Users</h2>';
$users = $db->query('SELECT id, full_name, email, role FROM users ORDER BY id')->fetchAll();
echo '<table><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Password</th></tr>';
foreach ($users as $u) {
    $roleColor = ['admin'=>'#38bdf8','owner'=>'#fbbf24','tenant'=>'#34d399','student'=>'#a78bfa'][$u['role']] ?? '#94a3b8';
    echo '<tr>';
    echo '<td>' . $u['id'] . '</td>';
    echo '<td><strong>' . htmlspecialchars($u['full_name']) . '</strong></td>';
    echo '<td style="color:#6c83f7;">' . htmlspecialchars($u['email']) . '</td>';
    echo '<td><span style="color:' . $roleColor . ';font-weight:600;">' . ucfirst($u['role']) . '</span></td>';
    echo '<td class="ok">admin123</td>';
    echo '</tr>';
}
echo '</table>';

// Verify password
$test = $db->query("SELECT password_hash FROM users WHERE email='shahed@gmail.com'")->fetch();
if ($test && password_verify('admin123', $test['password_hash'])) {
    echo '<span class="ok">Password verified — shahed@gmail.com + admin123 will work</span><br>';
}
?>

<a href="<?= APP_URL ?>/pages/login.php" class="btn">Go to Login &rarr;</a>

<pre>
Users (password: admin123):
  shahed@gmail.com          — Shahed Khan     (Owner — Sayeed Nagar)
  admin (check DB)          — Abdullah Shahriar (Admin/Owner — Basundhara)
  ritu@bscse.uiu.ac.bd     — Ritu Datta      (House Manager)
  tahsin@bscse.uiu.ac.bd   — Tahsin Faiyaz   (Student)

Properties:
  Shahed Khan:         Nikunja Villa, Mukul Villa, Rupali Kuthir, Nilachol Nibash
  Abdullah Shahriar:   Tasin Villa, Campus Edge Hostel, Simanto Kuthir
</pre>

</body>
</html>
