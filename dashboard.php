<?php
include './query/q_news.php';

// ดึงข่าวทั้งหมดสำหรับแดชบอร์ด
$news = getAllNewsForDashboard();

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

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
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
        <!-- <div class="dashboard-header">
            <h1 class="mb-0" style="color: #04a7e3;">Dashboard - จัดการข่าว</h1>
        </div> -->

        <div class="news-table">
            <h3 style="color: #04a7e3; margin-bottom: 20px;">รายการข่าว</h3>

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
                                <td colspan="6" class="text-center">ไม่มีข่าวในระบบ</td>
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
                                        <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(substr($row['content'], 0, 100)); ?>...
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $row['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $row['status'] == 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                        </span>
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

                    // ตรวจสอบว่าไม่มีข่าวเหลือ
                    const tbody = document.getElementById('news-table-body');
                    if (tbody.children.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center">ไม่มีข่าวในระบบ</td></tr>';
                    }

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
    </script>
</body>

</html>