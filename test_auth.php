<?php
/**
 * UIU Nest — Full Reset & Setup Script
 * Clears old demo users, inserts correct ones, sets passwords.
 * Access: http://localhost/GitHub/uiu-nest/test_auth.php
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
body  { font-family: monospace; background: #0c0c18; color: #e8e8f0; padding: 30px; font-size: 14px; line-height: 1.8; }
h2   { color: #6c83f7; margin: 20px 0 8px; }
.ok  { color: #34d399; }
.err { color: #f87171; }
.warn{ color: #fbbf24; }
table { border-collapse: collapse; margin: 12px 0; width: 100%; }
th, td { padding: 10px 16px; border: 1px solid #2a2a44; text-align: left; }
th { background: #1e1e36; color: #6c83f7; }
tr:hover td { background: #1a1a30; }
.btn { display:inline-block; margin-top:24px; padding:14px 32px; background:linear-gradient(135deg,#4361ee,#7c3aed); color:#fff; border-radius:8px; text-decoration:none; font-size:15px; font-weight:600; }
pre  { background:#1e1e36; padding:16px; border-radius:8px; border-left:4px solid #34d399; margin-top:20px; }
hr   { border:none; border-top:1px solid #2a2a44; margin:20px 0; }
</style>
</head>
<body>

<h2>🛠️ UIU Nest — Reset & Setup</h2>
<hr>

<?php
$db = getDB();

// ══════════════════════════════════════════════════════════════
// STEP 1 — Create property_images table if missing
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 1: Database Tables</h2>';
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

    try {
        $db->exec("ALTER TABLE properties ADD COLUMN house_manager_id INT UNSIGNED DEFAULT NULL");
        echo '<span class="ok">✅ Added house_manager_id to properties</span><br>';
    } catch (Exception $ex) {
        echo '<span class="warn">ℹ️ house_manager_id already exists</span><br>';
    }

    try {
        $db->exec("ALTER TABLE applications ADD COLUMN applied_at DATETIME DEFAULT NULL");
        $db->exec("UPDATE applications SET applied_at = created_at WHERE applied_at IS NULL");
        echo '<span class="ok">✅ Added applied_at to applications</span><br>';
    } catch (Exception $ex) {
        echo '<span class="warn">ℹ️ applied_at already exists</span><br>';
    }

    echo '<span class="ok">✅ Database tables: ready</span><br>';
} catch (Exception $e) {
    echo '<span class="warn">⚠️ ' . htmlspecialchars($e->getMessage()) . '</span><br>';
}

echo '<h2>Step 1b: Refresh Amenities</h2>';
try {
    $db->exec("DELETE FROM amenities");
    $db->exec("INSERT INTO amenities (slug, label, icon, sort_order, is_active) VALUES
      ('wifi',          'High-Speed WiFi',   '📶', 1, 1),
      ('ac',            'Air Conditioning',  '❄️', 2, 1),
      ('attached_bath', 'Attached Bathroom', '🚿', 3, 1),
      ('shared_bath',   'Shared Bathroom',   '🛁', 4, 1),
      ('furnished',     'Fully Furnished',   '🪑', 5, 1),
      ('balcony',       'Private Balcony',   '🌇', 6, 1),
      ('parking',       'Parking Space',     '🅿️', 7, 1),
      ('laundry',       'Laundry Access',    '👕', 8, 1),
      ('security',      '24/7 Security',     '🔒', 9, 1),
      ('cctv',          'CCTV Surveillance', '📹', 10, 1),
      ('study_room',    'Study Room',        '📚', 11, 1),
      ('rooftop',       'Rooftop Access',    '🏙️', 12, 1)
    ");
    echo '<span class="ok">✅ Amenities refreshed — Fan, Kitchen, Generator removed</span><br>';
} catch (Exception $e) {
    echo '<span class="warn">⚠️ Amenities: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
}

echo '<h2>Step 2: Upload Directories</h2>';
foreach ([
    UPLOAD_DIR . '/avatars'            => 'uploads/avatars',
    UPLOAD_DIR . '/properties'         => 'uploads/properties',
    UPLOAD_DIR . '/owner_applications' => 'uploads/owner_applications',
    DOC_UPLOAD_DIR                     => 'uploads/documents',
] as $path => $label) {
    if (!is_dir($path)) mkdir($path, 0755, true);
    echo '<span class="ok">✅ ' . $label . '</span><br>';
}

// ══════════════════════════════════════════════════════════════
// STEP 3 — Wipe old demo users & re-insert correct ones
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 3: Resetting Demo Users</h2>';

$db->exec('SET FOREIGN_KEY_CHECKS = 0');

// Delete ALL non-admin users (clears Karim, Fahim, old tenants, etc.)
// Keep only the admin account (id=1 / role=admin)
$db->exec("DELETE FROM users WHERE role != 'admin'");

// Also clean up orphaned data
$db->exec("DELETE FROM properties WHERE owner_id NOT IN (SELECT id FROM users)");
$db->exec("DELETE FROM rooms WHERE property_id NOT IN (SELECT id FROM properties)");
$db->exec("DELETE FROM listings WHERE room_id NOT IN (SELECT id FROM rooms)");
$db->exec("DELETE FROM applications WHERE listing_id NOT IN (SELECT id FROM listings)");
$db->exec("DELETE FROM room_tenants WHERE user_id NOT IN (SELECT id FROM users)");
$db->exec("DELETE FROM saved_listings WHERE user_id NOT IN (SELECT id FROM users)");

$db->exec('SET FOREIGN_KEY_CHECKS = 1');
echo '<span class="ok">✅ Old demo users cleared</span><br>';

// Generate password hash
$hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);

// ── Insert Shahed Khan (Owner) ─────────────────────────────────
$db->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified_at, phone)
              VALUES ('Shahed Khan', 'shahed@gmail.com', ?, 'owner', 1, NOW(), '+8801711111111')")
   ->execute([$hash]);
$shahedId = (int)$db->lastInsertId();
echo '<span class="ok">✅ Shahed Khan (Owner) inserted — ID: ' . $shahedId . '</span><br>';

// ── Insert Ritu Datta (House Manager / tenant) ─────────────────
$db->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified_at, student_id, department, year_of_study, gender)
              VALUES ('Ritu Datta', 'ritu@bscse.uiu.ac.bd', ?, 'tenant', 1, NOW(), '011221050', 'CSE', 4, 'female')")
   ->execute([$hash]);
$rituId = (int)$db->lastInsertId();
echo '<span class="ok">✅ Ritu Datta (House Manager) inserted — ID: ' . $rituId . '</span><br>';

// ── Insert Tahsin Faiyaz (Applicant / student) ─────────────────
$db->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified_at, student_id, department, year_of_study, gender)
              VALUES ('Tahsin Faiyaz', 'tahsin@bscse.uiu.ac.bd', ?, 'student', 1, NOW(), '011221001', 'CSE', 3, 'male')")
   ->execute([$hash]);
$tahsinId = (int)$db->lastInsertId();
echo '<span class="ok">✅ Tahsin Faiyaz (Applicant) inserted — ID: ' . $tahsinId . '</span><br>';

// ── Re-insert demo properties under Shahed Khan ───────────────
$db->prepare("INSERT INTO properties (owner_id, name, address, location_lat, location_lng, description) VALUES
  (?, 'Greenview Residence', '15/A Madani Avenue, Badda, Dhaka', 23.7995, 90.4510, 'Modern student housing just 5 minutes walk from UIU campus. Fully furnished rooms with 24/7 security.'),
  (?, 'Scholar Heights', '22 Bir Uttam Rafiqul Islam Ave, Dhaka', 23.7960, 90.4480, 'Premium accommodation for university students. Rooftop study lounge, high-speed internet, and backup generator.'),
  (?, 'Campus Edge Hostel', 'United City, Madani Avenue, Dhaka', 23.8005, 90.4520, 'Closest hostel to UIU campus. Walking distance, affordable rooms with modern amenities.')")
->execute([$shahedId, $shahedId, $shahedId]);
echo '<span class="ok">✅ 3 demo properties added under Shahed Khan</span><br>';

// Get property IDs
$props = $db->query("SELECT id FROM properties WHERE owner_id = $shahedId ORDER BY id ASC")->fetchAll();
[$p1, $p2, $p3] = [$props[0]['id'], $props[1]['id'], $props[2]['id']];

// ── Re-insert demo rooms ───────────────────────────────────────
$db->prepare("INSERT INTO rooms (property_id, room_number, capacity, current_occupancy, rent_amount, amenities_json, description) VALUES
  (?, '101', 2, 1, 8500.00, '[\"wifi\",\"ac\",\"attached_bath\",\"furnished\"]', 'Spacious double room with balcony.'),
  (?, '102', 1, 0, 6000.00, '[\"wifi\",\"fan\",\"shared_bath\"]', 'Cozy single room.'),
  (?, '201', 3, 2, 5500.00, '[\"wifi\",\"fan\",\"shared_bath\",\"furnished\"]', 'Triple sharing room.'),
  (?, 'A1',  2, 0, 9500.00, '[\"wifi\",\"ac\",\"attached_bath\",\"furnished\",\"balcony\"]', 'Premium double room with city view.'),
  (?, 'A2',  2, 1, 7500.00, '[\"wifi\",\"ac\",\"shared_bath\",\"furnished\"]', 'Comfortable shared room.'),
  (?, 'G1',  1, 0, 5000.00, '[\"wifi\",\"fan\",\"shared_bath\"]', 'Budget-friendly single near campus gate.'),
  (?, 'G2',  2, 0, 7000.00, '[\"wifi\",\"ac\",\"attached_bath\"]', 'Well-ventilated double room.')")
->execute([$p1, $p1, $p1, $p2, $p2, $p3, $p3]);
echo '<span class="ok">✅ 7 demo rooms added</span><br>';

// Get room IDs
$rooms = $db->query("SELECT id FROM rooms ORDER BY id ASC")->fetchAll();
[$r1,$r2,$r3,$r4,$r5,$r6,$r7] = array_column($rooms, 'id');

// ── Ritu Datta lives in room 101 (house manager) ───────────────
$db->prepare("INSERT INTO room_tenants (room_id, user_id) VALUES (?, ?)")->execute([$r1, $rituId]);
echo '<span class="ok">✅ Ritu Datta assigned to Room 101 (Greenview Residence)</span><br>';

// ── Demo listings ──────────────────────────────────────────────
$db->prepare("INSERT INTO listings (room_id, created_by, listing_type, title, description, status, published_at) VALUES
  (?, ?, 'roommate_needed', 'Looking for a Roommate — Greenview 101', 'Need a chill, studious roommate. Room is fully furnished with AC.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Premium Double Room at Scholar Heights', 'Brand new fully furnished double room with AC, attached bath, and balcony.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Budget Single Room — Campus Edge', 'Affordable single room just 2 minutes from UIU gate.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Double Room Near Campus — AC & Attached Bath', 'Well-maintained double room with AC and attached bathroom.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Single Room at Greenview', 'Quiet single room with WiFi. Shared bathroom.', 'published', NOW())")
->execute([$r1,$rituId, $r4,$shahedId, $r6,$shahedId, $r7,$shahedId, $r2,$shahedId]);
echo '<span class="ok">✅ 5 demo listings added</span><br>';

// ── Update admin password too ──────────────────────────────────
$db->prepare("UPDATE users SET password_hash = ? WHERE role = 'admin'")->execute([$hash]);
echo '<span class="ok">✅ Admin password reset to admin123</span><br>';

echo '<h2>Step 3b: Extra Owners & Properties</h2>';

$db->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified_at, phone) VALUES ('Abdullah Shahriar', 'shahriar@gmail.com', ?, 'owner', 1, NOW(), '+8801822334455')")->execute([$hash]);
$shahriarId = (int)$db->lastInsertId();
echo '<span class="ok">✅ Abdullah Shahriar (Owner) inserted — ID: ' . $shahriarId . '</span><br>';

$db->exec("UPDATE users SET is_active = 1");
echo '<span class="ok">✅ All users set is_active = 1</span><br>';

$db->prepare("INSERT INTO properties (owner_id, name, address, location_lat, location_lng, description) VALUES
  (?, 'Shatarkul View Apartments', 'House 12, Road 4, Shatarkul, Badda, Dhaka', 23.7820, 90.4430, 'Spacious rooms near Shatarkul lake. Great view and ventilation. Note: area has limited 24-hour security coverage.'),
  (?, 'Sayeed Nagar Heights', 'Block B, Sayeed Nagar, Badda, Dhaka', 23.7890, 90.4460, 'Comfortable accommodation in a quiet residential area. Internal rooms with limited balcony and outdoor access.'),
  (?, 'Kuril Flyover Residency', 'Kuril Chowrasta, Vatara, Dhaka', 23.8150, 90.4280, 'Modern building near Kuril flyover. About 3.5 km from UIU campus. Good connectivity via bus routes.'),
  (?, 'Bashundhara Gate House', 'Block J, Bashundhara RA, Dhaka', 23.8200, 90.4160, 'Premium gated community housing inside Bashundhara Residential Area. Approx 5 km from UIU campus but excellent facilities.')")
->execute([$shahedId, $shahedId, $shahedId, $shahedId]);

$shahedNewProps = $db->query("SELECT id FROM properties WHERE owner_id = $shahedId ORDER BY id ASC")->fetchAll();
$pShatarkul1 = $shahedNewProps[3]['id'];
$pSayeedNagar = $shahedNewProps[4]['id'] ?? null;
$pKuril1 = $shahedNewProps[5]['id'] ?? null;
$pBashundhara1 = $shahedNewProps[6]['id'] ?? null;

$allShahedProps = $db->query("SELECT id FROM properties WHERE owner_id = $shahedId ORDER BY id ASC")->fetchAll();
$pShatarkul1   = $allShahedProps[3]['id'];
$pSayeedNagar  = $allShahedProps[4]['id'];
$pKuril1       = $allShahedProps[5]['id'];
$pBashundhara1 = $allShahedProps[6]['id'];

$db->prepare("INSERT INTO properties (owner_id, name, address, location_lat, location_lng, description) VALUES
  (?, 'Shatarkul Lake Breeze', 'Road 7, Shatarkul, Badda, Dhaka', 23.7815, 90.4425, 'Affordable housing by Shatarkul. Comfortable rooms but the area has limited CCTV coverage at night.'),
  (?, 'Kuril Point Hostel', 'Vatara, Near Kuril Biswa Road, Dhaka', 23.8165, 90.4270, 'Affordable double and single rooms near Kuril. Approximately 4 km from UIU — suitable with transport.'),
  (?, 'Bashundhara Comfort Inn', 'Block G, Bashundhara RA, Dhaka', 23.8195, 90.4170, 'Well-maintained rooms in the heart of Bashundhara. Peaceful environment. About 5 km from UIU campus.')")
->execute([$shahriarId, $shahriarId, $shahriarId]);

$shahriarProps = $db->query("SELECT id FROM properties WHERE owner_id = $shahriarId ORDER BY id ASC")->fetchAll();
$pShatarkul2   = $shahriarProps[0]['id'];
$pKuril2       = $shahriarProps[1]['id'];
$pBashundhara2 = $shahriarProps[2]['id'];

echo '<span class="ok">✅ 7 additional properties added (4 Shahed + 3 Shahriar)</span><br>';

$shatarkulAmenities     = '["wifi","ac","attached_bath","furnished","balcony","study_room","rooftop"]';
$sayeedNagarAmenities   = '["wifi","ac","attached_bath","furnished","security","cctv","parking","laundry"]';
$kurilAmenities         = '["wifi","ac","furnished","security","cctv","laundry"]';
$bashundharaAmenities   = '["wifi","ac","attached_bath","furnished","balcony","security","cctv","rooftop","study_room","laundry","parking"]';

$db->prepare("INSERT INTO rooms (property_id, room_number, capacity, current_occupancy, rent_amount, amenities_json, description) VALUES
  (?, 'S101', 2, 0, 8000.00, ?, 'Double room with lake view. No security guard after midnight.'),
  (?, 'S102', 1, 0, 6500.00, ?, 'Single room, good natural light. Shared bath.'),
  (?, 'SN201', 2, 0, 9000.00, ?, 'Internal double room. No balcony access.'),
  (?, 'SN202', 1, 0, 7000.00, ?, 'Compact single room. Good security. No rooftop.'),
  (?, 'K301', 2, 0, 10000.00, ?, 'Spacious double near Kuril flyover. 3.5 km from UIU.'),
  (?, 'K302', 1, 0, 7500.00, ?, 'Single room. Kuril area. Bus stop nearby.'),
  (?, 'B401', 2, 0, 14000.00, ?, 'Premium double inside Bashundhara. 5 km from campus.'),
  (?, 'B402', 2, 0, 12000.00, ?, 'Well-furnished double. All amenities. Far from UIU.'),
  (?, 'SL101', 2, 0, 7500.00, ?, 'Lake view room. Limited CCTV coverage at night.'),
  (?, 'KP201', 2, 0, 9500.00, ?, 'Near Kuril. Good facilities but 4 km from UIU.'),
  (?, 'BC301', 2, 0, 13000.00, ?, 'Premium room in Bashundhara. 5 km from campus.')")
->execute([
    $pShatarkul1,   $shatarkulAmenities,
    $pShatarkul1,   $shatarkulAmenities,
    $pSayeedNagar,  $sayeedNagarAmenities,
    $pSayeedNagar,  $sayeedNagarAmenities,
    $pKuril1,       $kurilAmenities,
    $pKuril1,       $kurilAmenities,
    $pBashundhara1, $bashundharaAmenities,
    $pBashundhara1, $bashundharaAmenities,
    $pShatarkul2,   $shatarkulAmenities,
    $pKuril2,       $kurilAmenities,
    $pBashundhara2, $bashundharaAmenities
]);

$newRooms = $db->query("SELECT id FROM rooms WHERE property_id IN ($pShatarkul1,$pSayeedNagar,$pKuril1,$pBashundhara1,$pShatarkul2,$pKuril2,$pBashundhara2) ORDER BY id ASC")->fetchAll();
$nrIds = array_column($newRooms, 'id');

$db->prepare("INSERT INTO listings (room_id, created_by, listing_type, title, description, status, published_at) VALUES
  (?, ?, 'owner_direct', 'Double Room — Shatarkul Lake View', 'Peaceful room overlooking Shatarkul lake. WiFi, AC, furnished. Limited security at night.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Single Room — Shatarkul Badda', 'Budget single room in Shatarkul area. Good amenities. Note: no 24/7 security.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Modern Double Room — Sayeed Nagar', 'Fully equipped double room with security. Indoor location, no balcony/rooftop.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Quiet Single Room — Sayeed Nagar', 'Good security setup. Compact room. No rooftop or study room access.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Double Room — Kuril Flyover Area', 'Near Kuril. Bus service available. About 3.5 km from UIU campus.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Single Room — Kuril Vatara', 'Affordable single near Kuril. 4 km from UIU. CCTV covered.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Premium Double — Bashundhara RA', 'All amenities including balcony, rooftop and study room. 5 km from UIU campus.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Luxury Double — Bashundhara Gate', 'Top floor fully furnished room. Excellent building but 5 km from UIU.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Lake View Room — Shatarkul (Shahriar)', 'Budget-friendly room near Shatarkul lake. No 24/7 CCTV coverage.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Double Room — Kuril Point (Shahriar)', 'Near Kuril Biswa road. 4 km from UIU. Good transport links.', 'published', NOW()),
  (?, ?, 'owner_direct', 'Comfort Double — Bashundhara (Shahriar)', 'Well-maintained room in Bashundhara. All amenities. Far from UIU.', 'published', NOW())")
->execute([
    $nrIds[0], $shahedId,
    $nrIds[1], $shahedId,
    $nrIds[2], $shahedId,
    $nrIds[3], $shahedId,
    $nrIds[4], $shahedId,
    $nrIds[5], $shahedId,
    $nrIds[6], $shahedId,
    $nrIds[7], $shahedId,
    $nrIds[8], $shahriarId,
    $nrIds[9], $shahriarId,
    $nrIds[10], $shahriarId
]);
echo '<span class="ok">✅ 11 new listings added across Shatarkul, Sayeed Nagar, Kuril & Bashundhara</span><br>';


// ══════════════════════════════════════════════════════════════
// STEP 4 — Show final user table
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 4: Final User Accounts</h2>';

$roleLabel = fn($r) => match($r) {
    'tenant'  => '🏠 House Manager',
    'owner'   => '🏢 Owner',
    'student' => '🎓 Applicant',
    'admin'   => '⚙️ Admin',
    default   => ucfirst($r),
};

$users = $db->query('SELECT id, full_name, email, role FROM users ORDER BY id')->fetchAll();
echo '<table><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Password</th></tr>';
foreach ($users as $u) {
    echo '<tr>';
    echo '<td>' . $u['id'] . '</td>';
    echo '<td><strong>' . htmlspecialchars($u['full_name']) . '</strong></td>';
    echo '<td style="color:#6c83f7;">' . htmlspecialchars($u['email']) . '</td>';
    echo '<td>' . $roleLabel($u['role']) . '</td>';
    echo '<td class="ok">admin123</td>';
    echo '</tr>';
}
echo '</table>';

// ══════════════════════════════════════════════════════════════
// STEP 5 — Verify password works
// ══════════════════════════════════════════════════════════════
echo '<h2>Step 5: Login Verification</h2>';
$test = $db->query("SELECT password_hash FROM users WHERE email='shahed@gmail.com'")->fetch();
if ($test && password_verify('admin123', $test['password_hash'])) {
    echo '<span class="ok">✅ shahed@gmail.com + admin123 → LOGIN WILL WORK ✓</span><br>';
} else {
    echo '<span class="err">❌ Verification failed — something went wrong.</span><br>';
}
?>

<a href="<?= APP_URL ?>/pages/login.php" class="btn">🔑 Go to Login Now →</a>

<pre>
✅ Reset complete!

Login credentials:
  shahed@gmail.com          → admin123  (Owner)
  ritu@bscse.uiu.ac.bd      → admin123  (House Manager)
  tahsin@bscse.uiu.ac.bd    → admin123  (Applicant)
  admin@uiu.ac.bd           → admin123  (Admin)
</pre>

</body>
</html>
