<?php
/** UIU Nest — Common Footer */
?>
        </main>
    </div><!-- /.main-wrapper -->

    <!-- Toast container -->
    <div class="toast-container" id="toastContainer"></div>

<?php if (isLoggedIn() && hasAnyRole(['owner','tenant','student'])): ?>

<!-- Right Sidebar for Calendar -->
<button id="rightSidebarToggle" class="right-sidebar-toggle" onclick="document.getElementById('rightSidebarPanel').classList.toggle('open')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
</button>
<div id="rightSidebarPanel" class="right-sidebar-panel">
    <div class="right-sidebar-content">
        <div id="calendarSection">
    <div class="section-header">
        <h2>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;display:inline;vertical-align:middle;margin-right:6px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Announcement Calendar
        </h2>
        <?php if (hasRole('owner')): ?>
        <button class="btn btn-primary btn-sm" onclick="Modal.open('newEventModal')">+ Add Event</button>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <button class="btn btn-ghost btn-sm" onclick="CalWidget.prev()">&#8249; Prev</button>
                <strong id="calMonthLabel" style="font-size:1rem;"></strong>
                <button class="btn btn-ghost btn-sm" onclick="CalWidget.next()">Next &#8250;</button>
            </div>
            <div id="calGrid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;"></div>
        </div>
    </div>
        </div>
    </div>
</div>

<?php if (hasRole('owner')): ?>
<div class="modal-overlay" id="newEventModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header">
            <h3>Add Calendar Event</h3>
            <button class="modal-close" onclick="Modal.close('newEventModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Property</label>
                <select class="form-control" id="evProperty">
                    <?php
                    $pdo = getDB();
                    $ownerProps = $pdo->prepare('SELECT id, name FROM properties WHERE owner_id = ? AND is_active = 1');
                    $ownerProps->execute([$_SESSION['user_id']]);
                    foreach ($ownerProps->fetchAll() as $op):
                    ?>
                    <option value="<?= $op['id'] ?>"><?= htmlspecialchars($op['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Date</label>
                <input class="form-control" type="date" id="evDate">
            </div>

            <div class="form-group">
                <label class="form-label">Title</label>
                <input class="form-control" type="text" id="evTitle" placeholder="Event title...">
            </div>
            <div class="form-group">
                <label class="form-label">Description (optional)</label>
                <textarea class="form-control" id="evDesc" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="Modal.close('newEventModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveEvent()">Save Event</button>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.cal-header-cell { text-align: center; font-size: 0.68rem; font-weight: 700; color: var(--text-tertiary); padding: 6px 0; text-transform: uppercase; letter-spacing: 0.06em; }
.cal-day { min-height: 72px; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 7px; font-size: 0.76rem; background: var(--bg-secondary); transition: background var(--transition); }
.cal-day.today { border-color: var(--accent); background: var(--accent-light); }
.cal-day.other-month { opacity: 0.35; }
.cal-day .cal-num { font-weight: 700; margin-bottom: 3px; color: var(--text-primary); }
.cal-event-dot { display: block; font-size: 0.66rem; color: var(--accent); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; padding: 1px 4px; background: var(--accent-light); border-radius: 3px; }
.cal-event-dot.rent_due { color: var(--danger); background: var(--danger-light); }
.cal-event-dot.maintenance { color: var(--warning); background: var(--warning-light); }
</style>
<script>
const CalWidget = {
    current: new Date(),
    events: [],
    async init() { await this.loadEvents(); this.render(); },
    async loadEvents() {
        try {
            var r = await fetch(window.APP_URL + '/api/announcements.php', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            var d = await r.json();
            this.events = d.announcements || [];
        } catch(e) { this.events = []; }
    },
    prev() { this.current.setMonth(this.current.getMonth() - 1); this.render(); },
    next() { this.current.setMonth(this.current.getMonth() + 1); this.render(); },
    render() {
        const today = new Date();
        const y = this.current.getFullYear();
        const m = this.current.getMonth();
        document.getElementById('calMonthLabel').textContent =
            this.current.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        const grid = document.getElementById('calGrid');
        grid.innerHTML = '';
        ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => {
            const c = document.createElement('div'); c.className = 'cal-header-cell'; c.textContent = d; grid.appendChild(c);
        });
        const firstDay = new Date(y, m, 1).getDay();
        const daysInMonth = new Date(y, m + 1, 0).getDate();
        for (let i = 0; i < firstDay; i++) {
            const d = document.createElement('div'); d.className = 'cal-day other-month'; grid.appendChild(d);
        }
        for (let day = 1; day <= daysInMonth; day++) {
            const cell = document.createElement('div'); cell.className = 'cal-day';
            const dateStr = y + '-' + String(m+1).padStart(2,'0') + '-' + String(day).padStart(2,'0');
            const isToday = today.getDate()===day && today.getMonth()===m && today.getFullYear()===y;
            if (isToday) cell.classList.add('today');
            cell.innerHTML = '<div class="cal-num">' + day + '</div>';
            this.events.filter(e => e.event_date === dateStr).forEach(ev => {
                const dot = document.createElement('div');
                dot.className = 'cal-event-dot announcement';
                dot.title = (ev.property_name ? ev.property_name + ': ' : '') + ev.title;
                dot.style.cursor = 'pointer';
                dot.style.whiteSpace = 'normal';
                dot.style.padding = '4px 6px';
                dot.style.lineHeight = '1.3';
                dot.style.marginBottom = '4px';
                dot.innerHTML = `
                    <div style="font-weight:600;margin-bottom:2px;">${ev.title}</div>
                    <div style="font-size:0.6rem;opacity:0.85;">${ev.owner_name || 'Owner'} &bull; ${ev.property_name || 'General'}</div>
                `;
                dot.onclick = () => {
                    alert(`Announcement: ${ev.title}\nProperty: ${ev.property_name || 'General'}\nBy: ${ev.owner_name || 'Owner'}\n\n${ev.description || 'No description'}`);
                };
                cell.appendChild(dot);
            });
            grid.appendChild(cell);
        }
    }
};

async function saveEvent() {
    var pid   = document.getElementById('evProperty')?.value;
    var date  = document.getElementById('evDate').value;
    var title = document.getElementById('evTitle').value.trim();
    var desc  = document.getElementById('evDesc').value.trim();
    if (!date || !title) { Toast.show('Date and title required.', 'error'); return; }
    try {
        await fetchAPI(window.APP_URL + '/api/announcements.php', {
            method: 'POST',
            body: JSON.stringify({ property_id: parseInt(pid), event_date: date, title, description: desc })
        });
        Toast.show('Event saved!', 'success');
        Modal.close('newEventModal');
        await CalWidget.loadEvents();
        CalWidget.render();
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', () => CalWidget.init());
</script>
<?php endif; ?>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
<?php if (isset($includeMapJS) && $includeMapJS): ?>
    <script src="<?= APP_URL ?>/assets/js/map.js"></script>
<?php endif; ?>
<?php if (isset($includeListingsJS) && $includeListingsJS): ?>
    <script src="<?= APP_URL ?>/assets/js/listings.js"></script>
<?php endif; ?>
</body>
</html>
