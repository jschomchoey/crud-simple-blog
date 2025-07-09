<?php
include './query/q_news.php';

// ดึงข่าวทั้งหมดสำหรับแดชบอร์ด
$news = getAllNewsForDashboard();

// ดึงหมวดหมู่ทั้งหมดสำหรับตัวกรอง
$categories = getAllCategories();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - จัดการข่าว</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/scss/main.css">
    <style>
        .dashboard-container {
            margin: 20px;
        }

        .dashboard-header {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 25px;
            box-shadow: 0px 0px 15px #04a8e328;
            margin-bottom: 20px;
        }

        .news-table {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 25px;
            box-shadow: 0px 0px 15px #04a8e328;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            color: #04a7e3;
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }

        .filter-group select {
            border: 1px solid #04a7e3;
            border-radius: 10px;
            padding: 8px 12px;
            width: 100%;
        }

        .filter-group select:focus {
            box-shadow: 0 0 0 0.2rem rgba(4, 167, 227, 0.25);
            border-color: #04a7e3;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: end;
        }

        .btn-filter {
            background-color: #04a7e3;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-filter:hover {
            background-color: #0396d1;
        }

        .btn-clear {
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-clear:hover {
            background-color: #5a6268;
        }



        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .news-image {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 5px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: #04a7e3;
        }

        input:checked+.toggle-slider:before {
            transform: translateX(26px);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .results-count {
            color: #04a7e3;
            font-weight: 600;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                min-width: auto;
            }

            .filter-buttons {
                justify-content: center;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <nav class="nav-bar d-flex justify-content-between align-items-center">
        <a class="heading-text" href="index.php">บอ ลอ อ็อก บล็อก</a>
        <div>
            <a href="add_news.php" class="theme-button me-2">เพิ่มข่าว</a>
            <a href="index.php" class="theme-button">กลับหน้าหลัก</a>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="news-table">
            <h3 style="color: #04a7e3; margin-bottom: 20px;">รายการข่าว</h3>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="category-filter">หมวดหมู่</label>
                        <select id="category-filter">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="status-filter">สถานะ</label>
                        <select id="status-filter">
                            <option value="">ทั้งหมด</option>
                            <option value="active">เปิดใช้งาน</option>
                            <option value="inactive">ปิดใช้งาน</option>
                        </select>
                    </div>

                    <div class="filter-buttons">
                        <button type="button" class="btn-filter" onclick="applyFilters()">กรอง</button>
                        <button type="button" class="btn-clear" onclick="clearFilters()">ล้าง</button>
                    </div>
                </div>
            </div>

            <!-- Results Count -->
            <div class="results-count" id="results-count">
                แสดง <?php echo count($news); ?> รายการ
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>รูปภาพ</th>
                            <th>หัวข้อข่าว</th>
                            <th>หมวดหมู่</th>
                            <th>สถานะ</th>
                            <th>วันที่สร้าง</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="news-table-body">
                        <?php if (empty($news)): ?>
                            <tr>
                                <td colspan="6" class="no-results">ไม่มีข่าวในระบบ</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($news as $row): ?>
                                <tr id="news-row-<?php echo $row['id']; ?>">
                                    <td>
                                        <?php if ($row['image']): ?>
                                            <img src="uploads/images/<?php echo htmlspecialchars($row['image']); ?>"
                                                class="news-image" alt="News Image">
                                        <?php else: ?>
                                            <div class="news-image bg-light d-flex align-items-center justify-content-center">
                                                <small>ไม่มีรูป</small>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['title']); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(substr($row['content'], 0, 100)); ?>...
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox"
                                                <?php echo $row['status'] == 'active' ? 'checked' : ''; ?>
                                                onchange="toggleStatus(<?php echo $row['id']; ?>, this.checked)">
                                            <span class="toggle-slider"></span>
                                        </label>

                                    </td>
                                    <td><?php echo date("d/m/Y H:i", strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">

                                            <a href="edit_news.php?id=<?php echo $row['id']; ?>"
                                                class="btn btn-primary btn-sm">แก้ไข</a>
                                            <button class="btn btn-danger btn-sm"
                                                onclick="deleteNews(<?php echo $row['id']; ?>)">ลบ</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="loading" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">ยืนยันการลบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    คุณแน่ใจหรือไม่ที่จะลบข่าวนี้? การลบจะไม่สามารถกู้คืนได้
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">ลบ</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let deleteNewsId = null;
        let allNews = <?php echo json_encode($news); ?>;

        function deleteNews(newsId) {
            deleteNewsId = newsId;
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteNewsId) {
                performDelete(deleteNewsId);
            }
        });

        function performDelete(newsId) {
            const loading = document.getElementById('loading');
            loading.style.display = 'block';

            let formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', newsId);

            $.ajax({
                type: 'POST',
                url: 'ajax_news_actions.php',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                dataType: 'json'
            }).done(function(result) {
                if (result.success) {
                    // ลบแถวออกจากตาราง
                    document.getElementById('news-row-' + newsId).remove();

                    // ลบออกจาก allNews array
                    allNews = allNews.filter(news => news.id != newsId);

                    // ตรวจสอบว่าไม่มีข่าวเหลือ
                    updateTableDisplay();

                    alert('ลบข่าวเรียบร้อยแล้ว');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            }).fail(function() {
                alert('เกิดข้อผิดพลาดในการลบข่าว');
            }).always(function() {
                loading.style.display = 'none';
                const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                deleteModal.hide();
                deleteNewsId = null;
            });
        }

        function toggleStatus(newsId, isActive) {
            const loading = document.getElementById('loading');
            loading.style.display = 'block';

            let formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', newsId);
            formData.append('status', isActive ? 'active' : 'inactive');

            $.ajax({
                type: 'POST',
                url: 'ajax_news_actions.php',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                dataType: 'json'
            }).done(function(result) {
                if (result.success) {


                    // อัปเดต allNews array
                    const newsIndex = allNews.findIndex(news => news.id == newsId);
                    if (newsIndex !== -1) {
                        allNews[newsIndex].status = isActive ? 'active' : 'inactive';
                    }

                    // แสดงข้อความแจ้งเตือน
                    showNotification('เปลี่ยนสถานะเรียบร้อยแล้ว', 'success');
                } else {
                    // กลับสถานะเดิมถ้าเกิดข้อผิดพลาด
                    const toggle = document.querySelector(`#news-row-${newsId} input[type="checkbox"]`);
                    toggle.checked = !isActive;
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            }).fail(function() {
                // กลับสถานะเดิมถ้าเกิดข้อผิดพลาด
                const toggle = document.querySelector(`#news-row-${newsId} input[type="checkbox"]`);
                toggle.checked = !isActive;
                alert('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
            }).always(function() {
                loading.style.display = 'none';
            });
        }

        function applyFilters() {
            const categoryFilter = document.getElementById('category-filter').value;
            const statusFilter = document.getElementById('status-filter').value;

            let filteredNews = allNews;

            // กรองตามหมวดหมู่
            if (categoryFilter) {
                filteredNews = filteredNews.filter(news => news.category_id == categoryFilter);
            }

            // กรองตามสถานะ
            if (statusFilter) {
                filteredNews = filteredNews.filter(news => news.status === statusFilter);
            }

            updateTableDisplay(filteredNews);
        }

        function clearFilters() {
            document.getElementById('category-filter').value = '';
            document.getElementById('status-filter').value = '';
            updateTableDisplay(allNews);
        }

        function updateTableDisplay(newsData = null) {
            const tbody = document.getElementById('news-table-body');
            const resultsCount = document.getElementById('results-count');

            if (newsData === null) {
                newsData = allNews;
            }

            // อัปเดตจำนวนผลลัพธ์
            resultsCount.textContent = `แสดง ${newsData.length} รายการ`;

            if (newsData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="no-results">ไม่พบข่าวที่ตรงกับเงื่อนไข</td></tr>';
                return;
            }

            let html = '';
            newsData.forEach(row => {
                html += `
                    <tr id="news-row-${row.id}">
                        <td>
                            ${row.image ? 
                                `<img src="uploads/images/${row.image}" class="news-image" alt="News Image">` :
                                `<div class="news-image bg-light d-flex align-items-center justify-content-center">
                                    <small>ไม่มีรูป</small>
                                </div>`
                            }
                        </td>
                        <td>
                           ${row.title}
                            <br>
                            <small class="text-muted">
                                ${row.content.substring(0, 100)}...
                            </small>
                        </td>
                        <td>${row.category_name}</td>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       ${row.status === 'active' ? 'checked' : ''}
                                       onchange="toggleStatus(${row.id}, this.checked)">
                                <span class="toggle-slider"></span>
                            </label>

                        </td>
                        <td>${new Date(row.created_at).toLocaleString('th-TH', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}</td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit_news.php?id=${row.id}" class="btn btn-primary btn-sm">แก้ไข</a>
                                <button class="btn btn-danger btn-sm" onclick="deleteNews(${row.id})">ลบ</button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        function showNotification(message, type = 'success') {
            // สร้าง notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1050;
                min-width: 300px;
            `;
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(notification);

            // ลบ notification หลังจาก 3 วินาที
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }

        // Event listeners for real-time filtering
        document.getElementById('category-filter').addEventListener('change', applyFilters);
        document.getElementById('status-filter').addEventListener('change', applyFilters);
    </script>
</body>

</html>