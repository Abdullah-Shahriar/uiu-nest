<?php
/** UIU Nest — Manage Properties (Owner) — with photo gallery upload */
$pageName = 'Properties';
require_once __DIR__ . '/../includes/header.php';
requireRole(['owner']);

$db = getDB();
$stmt = $db->prepare('SELECT * FROM properties WHERE owner_id = ? AND is_active = 1 ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$properties = $stmt->fetchAll();

// Rooms + photos per property
foreach ($properties as &$p) {
    $rStmt = $db->prepare('SELECT * FROM rooms WHERE property_id = ? AND is_active = 1');
    $rStmt->execute([$p['id']]);
    $p['rooms'] = $rStmt->fetchAll();

    $imgStmt = $db->prepare('SELECT * FROM property_images WHERE property_id = ? ORDER BY sort_order ASC, id ASC');
    $imgStmt->execute([$p['id']]);
    $p['images'] = $imgStmt->fetchAll();
}
unset($p);
?>

<div class="section-header">
    <h2>🏢 My Properties</h2>
    <button class="btn btn-primary btn-sm" onclick="Modal.open('addPropertyModal')">+ Add Property</button>
</div>

<?php if (empty($properties)): ?>
<div class="empty-state">
    <div class="empty-state-icon">🏢</div>
    <h3>No properties yet</h3>
    <p>Add your first property to start listing rooms.</p>
</div>
<?php else: ?>

<?php foreach ($properties as $p): ?>
<div class="card" style="margin-bottom:24px;">

    <!-- Property Header -->
    <div style="padding:20px 20px 0;">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
            <div>
                <h3 style="margin-bottom:4px;"><?= htmlspecialchars($p['name']) ?></h3>
                <div style="color:var(--text-tertiary);font-size:0.875rem;">📍 <?= htmlspecialchars($p['address']) ?></div>
                <?php if ($p['description']): ?>
                <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:6px;"><?= htmlspecialchars($p['description']) ?></div>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0;">
                <button class="btn btn-sm btn-ghost" onclick="openEditProperty(<?= htmlspecialchars(json_encode($p)) ?>)">✏️ Edit</button>
                <button class="btn btn-sm btn-outline" onclick="showAddRoom(<?= $p['id'] ?>)">+ Add Room</button>
            </div>
        </div>
    </div>

    <!-- Photo Gallery Section -->
    <div style="padding:0 20px 16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div style="font-size:0.875rem;font-weight:600;color:var(--text-secondary);">📷 Property Photos</div>
            <label class="btn btn-sm btn-ghost" style="cursor:pointer;">
                + Add Photos
                <input type="file" accept="image/*" multiple style="display:none;"
                       onchange="uploadPropertyPhotos(this, <?= $p['id'] ?>)">
            </label>
        </div>

        <?php if (empty($p['images'])): ?>
        <div class="upload-box" onclick="this.querySelector('input').click()">
            <div class="upload-box-icon">🖼️</div>
            <div style="font-size:0.875rem;color:var(--text-tertiary);">Click to add photos of this property</div>
            <input type="file" accept="image/*" multiple onchange="uploadPropertyPhotos(this, <?= $p['id'] ?>)">
        </div>
        <?php else: ?>
        <div class="photo-gallery" id="gallery-<?= $p['id'] ?>">
            <?php foreach ($p['images'] as $img): ?>
            <div class="photo-thumb" data-img-id="<?= $img['id'] ?>">
                <img src="<?= APP_URL . '/' . htmlspecialchars($img['image_path']) ?>" alt="Property photo"
                     onclick="openLightbox('<?= APP_URL . '/' . htmlspecialchars($img['image_path']) ?>')">
                <button class="photo-delete-btn" onclick="deletePropertyPhoto(<?= $img['id'] ?>, <?= $p['id'] ?>)" title="Delete">✕</button>
                <?php if ($img['is_cover']): ?>
                <span class="photo-cover-badge">Cover</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <!-- Add more button always at end -->
            <label class="photo-add-more">
                <span>+</span>
                <input type="file" accept="image/*" multiple style="display:none;"
                       onchange="uploadPropertyPhotos(this, <?= $p['id'] ?>)">
            </label>
        </div>
        <?php endif; ?>
    </div>

    <!-- Rooms Table -->
    <div style="padding:0 20px 20px;">
        <div style="font-size:0.875rem;font-weight:600;color:var(--text-secondary);margin-bottom:10px;">🚪 Rooms</div>
        <?php if (empty($p['rooms'])): ?>
        <p style="color:var(--text-tertiary);padding:16px 0;text-align:center;font-size:0.875rem;">No rooms added yet</p>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Room</th><th>Capacity</th><th>Occupancy</th><th>Rent</th><th>Amenities</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($p['rooms'] as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['room_number']) ?></strong></td>
                <td><?= $r['capacity'] ?></td>
                <td>
                    <span style="color:<?= $r['current_occupancy'] >= $r['capacity'] ? 'var(--danger)' : 'var(--success)' ?>;">
                        <?= $r['current_occupancy'] ?>/<?= $r['capacity'] ?>
                    </span>
                </td>
                <td><?= formatRent((float)$r['rent_amount']) ?></td>
                <td><?php
                    $am = json_decode($r['amenities_json'] ?: '[]', true);
                    echo implode(', ', array_map(fn($a) => getAmenityLabel($a), array_slice($am, 0, 3)));
                    if (count($am) > 3) echo ' +' . (count($am) - 3);
                ?></td>
                <td><button class="btn btn-sm btn-ghost" onclick="openEditRoom(<?= htmlspecialchars(json_encode($r)) ?>)">✏️</button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ── Lightbox ── -->
<div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:9999;align-items:center;justify-content:center;" onclick="closeLightbox()">
    <img id="lightboxImg" src="" alt="" style="max-width:90vw;max-height:90vh;border-radius:var(--radius);object-fit:contain;">
    <button onclick="closeLightbox()" style="position:absolute;top:20px;right:20px;background:rgba(255,255,255,0.2);color:#fff;border:none;border-radius:50%;width:40px;height:40px;font-size:1.2rem;cursor:pointer;">✕</button>
</div>

<!-- ── Add Property Modal ── -->
<div class="modal-overlay" id="addPropertyModal">
    <div class="modal">
        <div class="modal-header"><h3>Add Property</h3><button class="modal-close" onclick="Modal.close('addPropertyModal')">✕</button></div>
        <div class="modal-body">
            <div class="form-group"><label class="form-label">Property Name</label><input class="form-control" id="propName" required></div>
            <div class="form-group"><label class="form-label">Address</label><input class="form-control" id="propAddr" required></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Latitude</label><input class="form-control" type="number" step="any" id="propLat" value="23.798" required></div>
                <div class="form-group"><label class="form-label">Longitude</label><input class="form-control" type="number" step="any" id="propLng" value="90.449" required></div>
            </div>
            <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" id="propDesc" rows="3"></textarea></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="Modal.close('addPropertyModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addProperty()">Add Property</button>
        </div>
    </div>
</div>

<!-- ── Edit Property Modal ── -->
<div class="modal-overlay" id="editPropertyModal">
    <div class="modal">
        <div class="modal-header"><h3>Edit Property</h3><button class="modal-close" onclick="Modal.close('editPropertyModal')">✕</button></div>
        <div class="modal-body">
            <input type="hidden" id="editPropId">
            <div class="form-group"><label class="form-label">Property Name</label><input class="form-control" id="editPropName" required></div>
            <div class="form-group"><label class="form-label">Address</label><input class="form-control" id="editPropAddr" required></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Latitude</label><input class="form-control" type="number" step="any" id="editPropLat" required></div>
                <div class="form-group"><label class="form-label">Longitude</label><input class="form-control" type="number" step="any" id="editPropLng" required></div>
            </div>
            <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" id="editPropDesc" rows="3"></textarea></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="Modal.close('editPropertyModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveEditProperty()">Save Changes</button>
        </div>
    </div>
</div>

<!-- ── Add Room Modal ── -->
<div class="modal-overlay" id="addRoomModal">
    <div class="modal">
        <div class="modal-header"><h3>Add Room</h3><button class="modal-close" onclick="Modal.close('addRoomModal')">✕</button></div>
        <div class="modal-body">
            <input type="hidden" id="roomPropId">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Room Number</label><input class="form-control" id="roomNum" required></div>
                <div class="form-group"><label class="form-label">Capacity</label><input class="form-control" type="number" id="roomCap" min="1" value="1" required></div>
            </div>
            <div class="form-group"><label class="form-label">Rent (৳/month)</label><input class="form-control" type="number" id="roomRent" min="0" step="500" required></div>
            <div class="form-group">
                <label class="form-label">Amenities</label>
                <div class="checkbox-group">
                    <?php foreach (getAmenitiesList() as $a): ?>
                    <label class="checkbox-item"><input type="checkbox" class="room-amenity" value="<?= $a['slug'] ?>"> <?= $a['icon'] ?> <?= htmlspecialchars($a['label']) ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" id="roomDescInput" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="Modal.close('addRoomModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addRoom()">Add Room</button>
        </div>
    </div>
</div>

<!-- ── Edit Room Modal ── -->
<div class="modal-overlay" id="editRoomModal">
    <div class="modal">
        <div class="modal-header"><h3>Edit Room</h3><button class="modal-close" onclick="Modal.close('editRoomModal')">✕</button></div>
        <div class="modal-body">
            <input type="hidden" id="editRoomId">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Room Number</label><input class="form-control" id="editRoomNum" required></div>
                <div class="form-group"><label class="form-label">Capacity</label><input class="form-control" type="number" id="editRoomCap" min="1" required></div>
            </div>
            <div class="form-group"><label class="form-label">Rent (৳/month)</label><input class="form-control" type="number" id="editRoomRent" min="0" step="500" required></div>
            <div class="form-group">
                <label class="form-label">Amenities</label>
                <div class="checkbox-group" id="editAmenities">
                    <?php foreach (getAmenitiesList() as $a): ?>
                    <label class="checkbox-item"><input type="checkbox" class="edit-room-amenity" value="<?= $a['slug'] ?>"> <?= $a['icon'] ?> <?= htmlspecialchars($a['label']) ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" id="editRoomDesc" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="Modal.close('editRoomModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveEditRoom()">Save Changes</button>
        </div>
    </div>
</div>

<style>
.photo-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.photo-thumb {
    position: relative;
    width: 120px;
    height: 90px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    border: 2px solid var(--border);
    transition: all 0.2s;
}
.photo-thumb:hover { border-color: var(--accent); }
.photo-thumb img {
    width: 100%; height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.2s;
}
.photo-thumb:hover img { transform: scale(1.05); }
.photo-delete-btn {
    position: absolute;
    top: 4px; right: 4px;
    background: rgba(0,0,0,0.6);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 22px; height: 22px;
    font-size: 0.75rem;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    line-height: 1;
}
.photo-thumb:hover .photo-delete-btn { display: flex; }
.photo-cover-badge {
    position: absolute;
    bottom: 4px; left: 4px;
    background: var(--accent);
    color: #fff;
    font-size: 0.65rem;
    padding: 2px 6px;
    border-radius: var(--radius-full);
    font-weight: 600;
}
.photo-add-more {
    width: 120px;
    height: 90px;
    border: 2px dashed var(--border-strong);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.8rem;
    color: var(--text-tertiary);
    transition: all 0.2s;
}
.photo-add-more:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }
</style>

<script>
// ── Property photos ──────────────────────────────────────────────────────────
async function uploadPropertyPhotos(input, propId) {
    const files = Array.from(input.files);
    if (!files.length) return;

    Toast.show(`Uploading ${files.length} photo(s)...`, 'info');

    const fd = new FormData();
    files.forEach(f => fd.append('photos[]', f));
    fd.append('property_id', propId);

    try {
        const resp = await fetch(`${APP_URL}/api/property-images.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        const data = await resp.json();
        if (data.success) {
            Toast.show('Photos uploaded!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            Toast.show(data.error || 'Upload failed', 'error');
        }
    } catch(e) { Toast.show('Upload error', 'error'); }
}

async function deletePropertyPhoto(imgId, propId) {
    if (!confirm('Delete this photo?')) return;
    try {
        const data = await fetchAPI(`${APP_URL}/api/property-images.php`, {
            method: 'DELETE',
            body: JSON.stringify({ image_id: imgId })
        });
        if (data.success) {
            const thumb = document.querySelector(`.photo-thumb[data-img-id="${imgId}"]`);
            if (thumb) { thumb.style.opacity = '0'; thumb.style.transform = 'scale(0.8)'; setTimeout(() => thumb.remove(), 200); }
            Toast.show('Photo deleted', 'info');
        }
    } catch(e) {}
}

function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = '';
}

// ── Property CRUD ─────────────────────────────────────────────────────────────
function showAddRoom(propId) {
    document.getElementById('roomPropId').value = propId;
    Modal.open('addRoomModal');
}

function openEditProperty(p) {
    document.getElementById('editPropId').value   = p.id;
    document.getElementById('editPropName').value = p.name;
    document.getElementById('editPropAddr').value = p.address;
    document.getElementById('editPropLat').value  = p.location_lat;
    document.getElementById('editPropLng').value  = p.location_lng;
    document.getElementById('editPropDesc').value = p.description || '';
    Modal.open('editPropertyModal');
}

function openEditRoom(r) {
    document.getElementById('editRoomId').value   = r.id;
    document.getElementById('editRoomNum').value  = r.room_number;
    document.getElementById('editRoomCap').value  = r.capacity;
    document.getElementById('editRoomRent').value = r.rent_amount;
    document.getElementById('editRoomDesc').value = r.description || '';
    const amenities = JSON.parse(r.amenities_json || '[]');
    document.querySelectorAll('.edit-room-amenity').forEach(cb => {
        cb.checked = amenities.includes(cb.value);
    });
    Modal.open('editRoomModal');
}

async function addProperty() {
    try {
        await fetchAPI(`${APP_URL}/api/properties.php`, { method:'POST', body: JSON.stringify({
            type: 'property',
            name: document.getElementById('propName').value,
            address: document.getElementById('propAddr').value,
            lat: document.getElementById('propLat').value,
            lng: document.getElementById('propLng').value,
            description: document.getElementById('propDesc').value,
        })});
        Toast.show('Property added!', 'success');
        setTimeout(() => location.reload(), 800);
    } catch(e) {}
}

async function saveEditProperty() {
    try {
        await fetchAPI(`${APP_URL}/api/properties.php`, { method:'PUT', body: JSON.stringify({
            type: 'property',
            id:   document.getElementById('editPropId').value,
            name: document.getElementById('editPropName').value,
            address: document.getElementById('editPropAddr').value,
            lat: document.getElementById('editPropLat').value,
            lng: document.getElementById('editPropLng').value,
            description: document.getElementById('editPropDesc').value,
        })});
        Toast.show('Property updated!', 'success');
        setTimeout(() => location.reload(), 800);
    } catch(e) {}
}

async function addRoom() {
    const amenities = [];
    document.querySelectorAll('.room-amenity:checked').forEach(cb => amenities.push(cb.value));
    try {
        await fetchAPI(`${APP_URL}/api/properties.php`, { method:'POST', body: JSON.stringify({
            type: 'room',
            property_id: document.getElementById('roomPropId').value,
            room_number: document.getElementById('roomNum').value,
            capacity: document.getElementById('roomCap').value,
            rent_amount: document.getElementById('roomRent').value,
            amenities,
            description: document.getElementById('roomDescInput').value,
        })});
        Toast.show('Room added!', 'success');
        setTimeout(() => location.reload(), 800);
    } catch(e) {}
}

async function saveEditRoom() {
    const amenities = [];
    document.querySelectorAll('.edit-room-amenity:checked').forEach(cb => amenities.push(cb.value));
    try {
        await fetchAPI(`${APP_URL}/api/properties.php`, { method:'PUT', body: JSON.stringify({
            type: 'room',
            id:   document.getElementById('editRoomId').value,
            room_number: document.getElementById('editRoomNum').value,
            capacity: document.getElementById('editRoomCap').value,
            rent_amount: document.getElementById('editRoomRent').value,
            amenities,
            description: document.getElementById('editRoomDesc').value,
        })});
        Toast.show('Room updated!', 'success');
        setTimeout(() => location.reload(), 800);
    } catch(e) {}
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
