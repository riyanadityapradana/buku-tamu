<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$current_admin = getCurrentAdmin();
$db = getDB();

// Handle delete action
if (isset($_POST['delete_id'])) {
    try {
        $visitor_id = $_POST['delete_id'];
        
        // Get visitor data for logging
        $stmt = $db->prepare("SELECT nama_lengkap, kode_kunjungan FROM visitors WHERE id = ?");
        $stmt->execute([$visitor_id]);
        $visitor = $stmt->fetch();
        
        if ($visitor) {
            // Delete visitor
            $stmt = $db->prepare("DELETE FROM visitors WHERE id = ?");
            $stmt->execute([$visitor_id]);
            
            // Log activity
            logActivity($visitor_id, $current_admin['id'], 'delete', 'Admin menghapus data pengunjung: ' . $visitor['nama_lengkap']);
            
            $success_message = "Data pengunjung " . $visitor['nama_lengkap'] . " berhasil dihapus.";
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle bulk checkout
if (isset($_POST['bulk_checkout'])) {
    try {
        $visitor_ids = $_POST['visitor_ids'] ?? [];
        if (empty($visitor_ids)) {
            throw new Exception("Pilih minimal satu pengunjung untuk checkout.");
        }
        
        $placeholders = str_repeat('?,', count($visitor_ids) - 1) . '?';
        $stmt = $db->prepare("UPDATE visitors SET jam_keluar = NOW(), status_kunjungan = 'keluar' WHERE id IN ($placeholders) AND status_kunjungan = 'masuk'");
        $stmt->execute($visitor_ids);
        
        $affected = $stmt->rowCount();
        $success_message = "$affected pengunjung berhasil di-checkout.";
        
        // Log activity for each
        foreach ($visitor_ids as $vid) {
            logActivity($vid, $current_admin['id'], 'checkout', 'Admin melakukan bulk checkout');
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Pagination and filtering
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$filter_status = $_GET['status'] ?? '';
$filter_date = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($filter_status) {
    $where_conditions[] = "status_kunjungan = ?";
    $params[] = $filter_status;
}

if ($filter_date) {
    $where_conditions[] = "DATE(created_at) = ?";
    $params[] = $filter_date;
}

if ($search) {
    $where_conditions[] = "(nama_lengkap LIKE ? OR asal_instansi LIKE ? OR kode_kunjungan LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM visitors $where_clause";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Get visitors data
$sql = "SELECT * FROM visitors $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$visitors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengunjung - Admin RS Pelita Insani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 20px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .filter-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white mb-4">
                        <i class="fas fa-hospital fa-2x mb-2"></i>
                        <h6>RS Pelita Insani</h6>
                        <small>Admin Panel</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="visitors.php">
                                <i class="fas fa-users"></i> Data Pengunjung
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Laporan
                            </a>
                        </li>
                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-user-cog"></i> Kelola User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i> Pengaturan
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="mt-auto pt-3">
                        <hr style="border-color: rgba(255,255,255,0.2)">
                        <div class="text-white px-3 py-2">
                            <small>
                                <i class="fas fa-user"></i> <?= htmlspecialchars($current_admin['nama']) ?><br>
                                <span class="badge bg-light text-dark"><?= ucfirst($current_admin['role']) ?></span>
                            </small>
                        </div>
                        <a href="logout.php" class="nav-link text-white-50">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Data Pengunjung</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="visitor_add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Pengunjung
                            </a>
                            <button type="button" class="btn btn-success" onclick="exportData()">
                                <i class="fas fa-download"></i> Export Excel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card filter-card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="masuk" <?= $filter_status == 'masuk' ? 'selected' : '' ?>>Masuk</option>
                                    <option value="keluar" <?= $filter_status == 'keluar' ? 'selected' : '' ?>>Keluar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="date" class="form-control" value="<?= $filter_date ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pencarian</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Nama, instansi, atau kode..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Data Table -->
                <form method="POST" id="visitorForm">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-users"></i> 
                                Total: <?= $total_records ?> pengunjung
                            </h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-warning" onclick="bulkCheckout()">
                                    <i class="fas fa-sign-out-alt"></i> Checkout Terpilih
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelected()">
                                    <i class="fas fa-trash"></i> Hapus Terpilih
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="checkAll" onchange="toggleAll()">
                                        </th>
                                        <th>Kode</th>
                                        <th>Nama Lengkap</th>
                                        <th>Instansi</th>
                                        <th>Telepon</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Keluar</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($visitors)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <i class="fas fa-users fa-2x text-muted mb-2"></i><br>
                                                <span class="text-muted">Tidak ada data pengunjung</span>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($visitors as $visitor): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="visitor_ids[]" value="<?= $visitor['id'] ?>" class="visitor-checkbox">
                                                </td>
                                                <td>
                                                    <small class="text-monospace"><?= htmlspecialchars($visitor['kode_kunjungan']) ?></small>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($visitor['nama_lengkap']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($visitor['jenis_kelamin']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($visitor['asal_instansi']) ?></td>
                                                <td><?= htmlspecialchars($visitor['no_telepon']) ?></td>
                                                <td>
                                                    <small><?= date('d/m/Y H:i', strtotime($visitor['jam_masuk'])) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($visitor['jam_keluar']): ?>
                                                        <small><?= date('d/m/Y H:i', strtotime($visitor['jam_keluar'])) ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($visitor['status_kunjungan'] == 'masuk'): ?>
                                                        <span class="badge bg-warning status-badge">Di Dalam</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success status-badge">Keluar</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="visitor_detail.php?id=<?= $visitor['id'] ?>" 
                                                           class="btn btn-outline-primary" title="Detail">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="visitor_edit.php?id=<?= $visitor['id'] ?>" 
                                                           class="btn btn-outline-success" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($visitor['status_kunjungan'] == 'masuk'): ?>
                                                            <a href="../checkout.php?code=<?= $visitor['kode_kunjungan'] ?>" 
                                                               class="btn btn-outline-warning" title="Checkout" target="_blank">
                                                                <i class="fas fa-sign-out-alt"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="deleteVisitor(<?= $visitor['id'] ?>, '<?= htmlspecialchars($visitor['nama_lengkap'], ENT_QUOTES) ?>')" 
                                                                title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Hidden inputs for actions -->
                    <input type="hidden" name="bulk_checkout" id="bulkCheckoutInput">
                    <input type="hidden" name="delete_id" id="deleteIdInput">
                </form>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page-1 ?>&status=<?= $filter_status ?>&date=<?= $filter_date ?>&search=<?= urlencode($search) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= $filter_status ?>&date=<?= $filter_date ?>&search=<?= urlencode($search) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page+1 ?>&status=<?= $filter_status ?>&date=<?= $filter_date ?>&search=<?= urlencode($search) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAll() {
            const checkAll = document.getElementById('checkAll');
            const checkboxes = document.querySelectorAll('.visitor-checkbox');
            checkboxes.forEach(cb => cb.checked = checkAll.checked);
        }

        function bulkCheckout() {
            const selected = document.querySelectorAll('.visitor-checkbox:checked');
            if (selected.length === 0) {
                alert('Pilih minimal satu pengunjung untuk checkout.');
                return;
            }
            
            if (confirm(`Checkout ${selected.length} pengunjung yang dipilih?`)) {
                document.getElementById('bulkCheckoutInput').value = '1';
                document.getElementById('visitorForm').submit();
            }
        }

        function deleteSelected() {
            const selected = document.querySelectorAll('.visitor-checkbox:checked');
            if (selected.length === 0) {
                alert('Pilih minimal satu pengunjung untuk dihapus.');
                return;
            }
            
            if (confirm(`Hapus ${selected.length} pengunjung yang dipilih? Aksi ini tidak dapat dibatalkan.`)) {
                // Handle multiple delete - would need additional backend logic
                alert('Fitur hapus massal belum diimplementasi. Gunakan hapus satu per satu.');
            }
        }

        function deleteVisitor(id, name) {
            if (confirm(`Hapus data pengunjung "${name}"?\nAksi ini tidak dapat dibatalkan.`)) {
                document.getElementById('deleteIdInput').value = id;
                document.getElementById('visitorForm').submit();
            }
        }

        function exportData() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.location.href = 'reports.php?' + params.toString();
        }
    </script>
</body>
</html>
