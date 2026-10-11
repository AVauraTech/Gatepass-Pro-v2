<?php
// manage-passes.php - PassVault Central Registry with Real-Time Client-Side Search & Relational Filtering
session_start();
include('db.php');

if (!isset($_SESSION['admin_id']) || strlen($_SESSION['admin_id']) == 0) {
    header("location:logout.php");
    exit;
}

// Auto-check overstay anomalies
checkOverstayAnomalies($conn);

$active_page = 'manage-passes.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<style>
        .vault-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(18, 28, 38, 0.75);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(0, 255, 195, 0.25);
            border-radius: 20px;
            padding: 22px 28px;
            margin-bottom: 24px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.5);
            flex-wrap: wrap;
            gap: 16px;
        }
        .vault-title-box h1 {
            margin: 0;
            color: #24ffe3;
            font-size: 1.8rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .vault-title-box p {
            margin: 4px 0 0 0;
            font-size: 0.85rem;
            color: #8da4af;
        }
        .vault-actions-top {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .btn-top-action {
            background: linear-gradient(135deg, #00ffc3, #00bfa1);
            color: #053027;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(0, 255, 195, 0.35);
        }
        .btn-top-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 255, 195, 0.6);
            color: #000;
        }

        /* Filter & Search Toolbar */
        .toolbar-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .search-wrap {
            position: relative;
            flex: 1;
            max-width: 380px;
        }
        .search-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #00ffc3;
            font-size: 0.9rem;
        }
        .search-input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            background: rgba(18, 28, 38, 0.85);
            border: 1px solid rgba(0, 255, 195, 0.3);
            border-radius: 12px;
            color: #fff;
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }
        .search-input:focus {
            border-color: #00ffc3;
            box-shadow: 0 0 15px rgba(0, 255, 195, 0.4);
            background: rgba(22, 36, 48, 0.95);
        }
        .filter-tabs {
            display: flex;
            gap: 8px;
            background: rgba(14, 24, 34, 0.7);
            padding: 4px;
            border-radius: 12px;
            border: 1px solid rgba(0, 255, 195, 0.2);
        }
        .tab-btn {
            background: transparent;
            border: none;
            color: #8da4af;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab-btn:hover { color: #fff; }
        .tab-btn.active {
            background: rgba(0, 255, 195, 0.2);
            color: #00ffc3;
            border: 1px solid rgba(0, 255, 195, 0.4);
        }

        /* Glass Table Card */
        .card-table-wrap {
            background: rgba(18, 28, 38, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 255, 195, 0.25);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            overflow-x: auto;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            min-width: 850px;
        }
        .data-table th {
            text-align: left;
            padding: 12px 14px;
            color: #24ffe3;
            border-bottom: 2px solid rgba(0, 255, 195, 0.25);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 800;
        }
        .data-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            color: #c4f9f4;
            vertical-align: middle;
        }
        .data-table tbody tr {
            transition: background 0.2s;
        }
        .data-table tbody tr:hover {
            background: rgba(0, 255, 195, 0.08);
        }
        .pass-num-link {
            color: #00ffc3;
            font-weight: 800;
            text-decoration: none;
            letter-spacing: 0.04em;
        }
        .pass-num-link:hover { text-decoration: underline; color: #62ffd9; }
        .plate-pill {
            background: rgba(0, 255, 195, 0.08);
            border: 1px solid rgba(0, 255, 195, 0.2);
            padding: 3px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.78rem;
            color: #e0f8f7;
        }
        .status-pill {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 800;
            display: inline-block;
        }
        .st-Approved { background: rgba(0, 255, 195, 0.15); color: #00ffc3; border: 1px solid rgba(0, 255, 195, 0.4); }
        .st-Checked-In { background: rgba(0, 153, 255, 0.15); color: #0099ff; border: 1px solid rgba(0, 153, 255, 0.4); }
        .st-Checked-Out { background: rgba(144, 164, 174, 0.15); color: #90a4ae; border: 1px solid rgba(144, 164, 174, 0.3); }
        .st-Flagged { background: rgba(255, 82, 82, 0.15); color: #ff5252; border: 1px solid rgba(255, 82, 82, 0.4); animation: pulseAlert 1.4s infinite; }
        @keyframes pulseAlert { 50% { opacity: 0.6; } }

        /* Row Actions */
        .action-cell {
            display: flex;
            gap: 6px;
        }
        .btn-table-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-action-view {
            background: rgba(0, 255, 195, 0.15);
            border: 1px solid rgba(0, 255, 195, 0.4);
            color: #00ffc3;
        }
        .btn-action-view:hover {
            background: #00ffc3;
            color: #053027;
        }
        .btn-action-checkpoint {
            background: rgba(0, 153, 255, 0.15);
            border: 1px solid rgba(0, 153, 255, 0.4);
            color: #0099ff;
        }
        .btn-action-checkpoint:hover {
            background: #0099ff;
            color: #fff;
        }
        .btn-action-edit {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.2);
            color: #b0bec5;
        }
        .btn-action-edit:hover {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }

        /* Pagination */
        .pagination-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
            font-size: 0.85rem;
            color: #799ea8;
        }
        .pagination-controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn-page {
            background: rgba(0, 255, 195, 0.12);
            border: 1px solid rgba(0, 255, 195, 0.25);
            color: #00ffc3;
            padding: 6px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .btn-page:hover:not(:disabled) {
            background: #00ffc3;
            color: #053027;
        }
        .btn-page:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }
    </style>

<div class="vault-header">
        <div class="vault-title-box">
            <h1><i class="fas fa-tasks text-cyan"></i> PASSVAULT REGISTRY</h1>
            <p>Real-Time Credential Indexing • Fast Relational Search • Live Checkpoint Ingress</p>
        </div>
        <div class="vault-actions-top">
            <a href="add-pass.php" class="btn-top-action"><i class="fas fa-plus"></i> Issue New Pass</a>
            <a href="checkpoint-terminal.php" class="btn-top-action" style="background:linear-gradient(135deg, #0099ff, #0066cc); color:#fff;"><i class="fas fa-shield-alt"></i> Checkpoint Terminal</a>
        </div>
    </div>

    <div class="toolbar-bar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="passSearch" class="search-input" placeholder="Search pass #, name, plate, or host..." onkeyup="filterRows()">
        </div>

        <div class="filter-tabs">
            <button class="tab-btn active" onclick="setFilter('ALL')">ALL PASSES</button>
            <button class="tab-btn" onclick="setFilter('Checked-In')"><i class="fas fa-door-open"></i> INSIDE (CHECKED-IN)</button>
            <button class="tab-btn" onclick="setFilter('Flagged')"><i class="fas fa-exclamation-triangle text-red"></i> FLAGGED / OVERSTAY</button>
            <button class="tab-btn" onclick="setFilter('Approved')">APPROVED</button>
        </div>
    </div>

    <div class="card-table-wrap">
        <table class="data-table" id="vaultTable">
            <thead>
                <tr>
                    <th>PASS #</th>
                    <th>VISITOR NAME</th>
                    <th>CATEGORY</th>
                    <th>CLEARANCE</th>
                    <th>ALPR PLATE</th>
                    <th>HOST CONTACT</th>
                    <th>VALID UNTIL</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php
                $passesQuery = mysqli_query($conn, "SELECT * FROM passes ORDER BY id DESC");
                while ($p = mysqli_fetch_assoc($passesQuery)):
                    $st = $p['status'] ?: 'Approved';
                    $isOverstay = ($p['overstay_alert'] == 1);
                ?>
                <tr data-status="<?php echo htmlspecialchars($st); ?>" data-search="<?php echo htmlspecialchars(strtolower($p['pass_number'] . ' ' . $p['full_name'] . ' ' . ($p['vehicle_no'] ?? '') . ' ' . ($p['host_name'] ?? '') . ' ' . $p['category'])); ?>">
                    <td>
                        <a href="view-pass-detail.php?id=<?php echo $p['id']; ?>" class="pass-num-link">
                            #<?php echo htmlspecialchars($p['pass_number']); ?>
                        </a>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($p['full_name']); ?></strong>
                        <?php if ($isOverstay): ?>
                            <span class="status-pill st-Flagged" style="font-size:0.65rem; padding:2px 6px; margin-left:4px;"><i class="fas fa-clock"></i> OVERSTAY</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($p['category']); ?></td>
                    <td><span style="color:#ffd600; font-size:0.8rem; font-weight:700;"><?php echo htmlspecialchars($p['security_clearance'] ?? 'Standard'); ?></span></td>
                    <td><span class="plate-pill"><?php echo htmlspecialchars($p['vehicle_no'] ?: 'N/A'); ?></span></td>
                    <td><small style="color:#799ea8;"><?php echo htmlspecialchars($p['host_name'] ?? 'Security Operations'); ?></small></td>
                    <td><small><?php echo htmlspecialchars($p['to_date']); ?></small></td>
                    <td>
                        <span class="status-pill st-<?php echo htmlspecialchars($st); ?>">
                            <?php echo strtoupper(htmlspecialchars($st)); ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-cell">
                            <a href="view-pass-detail.php?id=<?php echo $p['id']; ?>" class="btn-table-action btn-action-view" title="View Dossier & QR Badge">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="checkpoint-terminal.php?scan=<?php echo urlencode($p['pass_number']); ?>" class="btn-table-action btn-action-checkpoint" title="Verify at Checkpoint">
                                <i class="fas fa-barcode"></i> Verify
                            </a>
                            <a href="edit-pass-detail.php?id=<?php echo $p['id']; ?>" class="btn-table-action btn-action-edit" title="Edit Pass">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="pagination-bar">
            <span id="pageInfo">Showing records...</span>
            <div class="pagination-controls">
                <button class="btn-page" id="btnPrev" onclick="changePage(-1)">&laquo; Previous</button>
                <span id="pageIndicator" style="font-weight:700; color:#fff;">Page 1</span>
                <button class="btn-page" id="btnNext" onclick="changePage(1)">Next &raquo;</button>
            </div>
        </div>
    </div>
</div>

<script>
// Real-Time Search & Pagination Engine
let currentFilter = 'ALL';
let currentPage = 1;
const rowsPerPage = 10;
let visibleRows = [];

function getRows() {
    return Array.from(document.querySelectorAll('#tableBody tr'));
}

function setFilter(filter) {
    currentFilter = filter;
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');
    filterRows();
}

function filterRows() {
    const query = document.getElementById('passSearch').value.trim().toLowerCase();
    const rows = getRows();

    visibleRows = rows.filter(row => {
        const matchesStatus = (currentFilter === 'ALL') || (row.getAttribute('data-status') === currentFilter);
        const matchesQuery = !query || row.getAttribute('data-search').includes(query);
        return matchesStatus && matchesQuery;
    });

    currentPage = 1;
    renderPagination();
}

function renderPagination() {
    const rows = getRows();
    rows.forEach(r => r.style.display = 'none'); // hide all

    const total = visibleRows.length;
    const totalPages = Math.ceil(total / rowsPerPage) || 1;
    currentPage = Math.min(Math.max(currentPage, 1), totalPages);

    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    visibleRows.slice(start, end).forEach(r => r.style.display = '');

    document.getElementById('btnPrev').disabled = (currentPage === 1);
    document.getElementById('btnNext').disabled = (currentPage === totalPages || total === 0);
    document.getElementById('pageIndicator').textContent = `Page ${currentPage} of ${totalPages}`;
    document.getElementById('pageInfo').textContent = `Showing ${total > 0 ? start + 1 : 0} - ${Math.min(end, total)} of ${total} records`;
}

function changePage(direction) {
    currentPage += direction;
    renderPagination();
}

// Initial Run
document.addEventListener('DOMContentLoaded', () => {
    filterRows();
});
</script>

<?php include 'includes/footer.php'; ?>
