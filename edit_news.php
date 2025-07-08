<?php
include './query/q_news.php';

// ตรวจสอบ ID ที่ส่งมา
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$news_id = $_GET['id'];

// ดึงข้อมูลข่าวที่จะแก้ไข
$news = getNewsById($news_id);
if (!$news) {
    header('Location: dashboard.php');
    exit();
}

// ดึงหมวดหมู่ทั้งหมด
$categories = getAllCategories();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข่าว - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/scss/main.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 25px;
            box-shadow: 0px 0px 15px #04a8e328;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            color: #04a7e3;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border: 1px solid #04a7e3;
            border-radius: 15px;
            padding: 10px 15px;
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(4, 167, 227, 0.25);
            border-color: #04a7e3;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
        }

        .success-message {
            color: #28a745;
            font-size: 0.875rem;
            margin-top: 5px;
        }

        .file-info {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .btn-container {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            border-radius: 50px;
            padding: 0.5rem 2rem;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }

        .current-image {
            max-width: 200px;
            height: auto;
            border-radius: 10px;
            margin-top: 10px;
        }

        .image-preview {
            margin-top: 10px;
        }

        .remove-image-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .remove-image-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>

<body>
    <nav class="nav-bar d-flex justify-content-between align-items-center">
        <a class="heading-text" href="index.php">บอ ลอ อ็อก บล็อก</a>
        <div>
            <a href="dashboard.php" class="theme-button me-2">Dashboard</a>
            <a href="index.php" class="theme-button">กลับหน้าหลัก</a>
        </div>
    </nav>

    <div class="form-container">
        <h2 style="color: #04a7e3; margin-bottom: 30px;">แก้ไขข่าว</h2>

        <form id="editNewsForm" enctype="multipart/form-data">
            <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">

            <div class="form-group">
                <label for="title" class="form-label">หัวข้อข่าว *</label>
                <input type="text" class="form-control" id="title" name="title"
                    value="<?php echo htmlspecialchars($news['title']); ?>" required>
                <div class="error-message" id="title-error"></div>
            </div>

            <div class="form-group">
                <label for="content" class="form-label">เนื้อหาข่าว *</label>
                <textarea class="form-control" id="content" name="content" rows="6" required><?php echo htmlspecialchars($news['content']); ?></textarea>
                <div class="error-message" id="content-error"></div>
            </div>

            <div class="form-group">
                <label for="category" class="form-label">หมวดหมู่ *</label>
                <select class="form-select" id="category" name="category" required>
                    <option value="">เลือกหมวดหมู่</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                            <?php echo ($news['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="error-message" id="category-error"></div>
            </div>

            <div class="form-group">
                <label for="image" class="form-label">รูปภาพประกอบ</label>
                <?php if ($news['image']): ?>
                    <div class="image-preview" id="current-image-container">
                        <div>รูปภาพปัจจุบัน:</div>
                        <img src="uploads/images/<?php echo htmlspecialchars($news['image']); ?>"
                            class="current-image" alt="Current Image" id="current-image">
                        <br>
                        <button type="button" class="remove-image-btn" onclick="removeCurrentImage()">
                            ลบรูปภาพปัจจุบัน
                        </button>
                        <input type="hidden" name="remove_image" id="remove_image" value="0">
                    </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/jpg" style="margin-top: 10px;">
                <div class="file-info">รองรับไฟล์ JPG, PNG ขนาดไม่เกิน 2MB</div>
                <div class="error-message" id="image-error"></div>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">สถานะ *</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="">เลือกสถานะ</option>
                    <option value="active" <?php echo ($news['status'] == 'active') ? 'selected' : ''; ?>>เปิดใช้งาน</option>
                    <option value="inactive" <?php echo ($news['status'] == 'inactive') ? 'selected' : ''; ?>>ปิดใช้งาน</option>
                </select>
                <div class="error-message" id="status-error"></div>
            </div>

            <div class="btn-container">
                <a href="dashboard.php" class="btn btn-secondary">ยกเลิก</a>
                <button type="submit" class="theme-button">บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>

    <!-- Loading indicator -->
    <div id="loading" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">บันทึกสำเร็จ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    แก้ไขข่าวเรียบร้อยแล้ว
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="window.location.href='dashboard.php'">ไปยัง Dashboard</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(function(element) {
                element.textContent = '';
            });
        }

        function displayErrors(errors) {
            clearErrors();
            for (let field in errors) {
                const errorElement = document.getElementById(field + '-error');
                if (errorElement) {
                    errorElement.textContent = errors[field];
                }
            }
        }

        function removeCurrentImage() {
            const currentImageContainer = document.getElementById('current-image-container');
            const removeImageInput = document.getElementById('remove_image');

            currentImageContainer.style.display = 'none';
            removeImageInput.value = '1';
        }

        function validateForm() {
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();
            const category = document.getElementById('category').value;
            const status = document.getElementById('status').value;
            const imageFile = document.getElementById('image').files[0];

            let errors = {};

            // Validate title
            if (!title) {
                errors.title = 'กรุณากรอกหัวข้อข่าว';
            } else if (title.length < 3) {
                errors.title = 'หัวข้อข่าวต้องมีอย่างน้อย 3 ตัวอักษร';
            } else if (title.length > 255) {
                errors.title = 'หัวข้อข่าวต้องไม่เกิน 255 ตัวอักษร';
            }

            // Validate content
            if (!content) {
                errors.content = 'กรุณากรอกเนื้อหาข่าว';
            } else if (content.length < 10) {
                errors.content = 'เนื้อหาข่าวต้องมีอย่างน้อย 10 ตัวอักษร';
            }

            // Validate category
            if (!category) {
                errors.category = 'กรุณาเลือกหมวดหมู่';
            }

            // Validate status
            if (!status) {
                errors.status = 'กรุณาเลือกสถานะ';
            }

            // Validate image
            if (imageFile) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (!allowedTypes.includes(imageFile.type)) {
                    errors.image = 'รองรับเฉพาะไฟล์ JPG, PNG เท่านั้น';
                } else if (imageFile.size > maxSize) {
                    errors.image = 'ขนาดไฟล์ต้องไม่เกิน 2MB';
                }
            }

            return errors;
        }

        document.getElementById('editNewsForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const errors = validateForm();

            if (Object.keys(errors).length > 0) {
                displayErrors(errors);
                return;
            }

            const loading = document.getElementById('loading');
            loading.style.display = 'block';

            const formData = new FormData(this);
            formData.append('action', 'edit');

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
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                } else {
                    if (result.errors) {
                        displayErrors(result.errors);
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + result.message);
                    }
                }
            }).fail(function() {
                alert('เกิดข้อผิดพลาดในการส่งข้อมูล');
            }).always(function() {
                loading.style.display = 'none';
            });
        });

        // Real-time validation
        document.getElementById('title').addEventListener('input', function() {
            const titleError = document.getElementById('title-error');
            const value = this.value.trim();

            if (value && value.length < 3) {
                titleError.textContent = 'หัวข้อข่าวต้องมีอย่างน้อย 3 ตัวอักษร';
            } else if (value && value.length > 255) {
                titleError.textContent = 'หัวข้อข่าวต้องไม่เกิน 255 ตัวอักษร';
            } else {
                titleError.textContent = '';
            }
        });

        document.getElementById('content').addEventListener('input', function() {
            const contentError = document.getElementById('content-error');
            const value = this.value.trim();

            if (value && value.length < 10) {
                contentError.textContent = 'เนื้อหาข่าวต้องมีอย่างน้อย 10 ตัวอักษร';
            } else {
                contentError.textContent = '';
            }
        });

        document.getElementById('image').addEventListener('change', function() {
            const imageError = document.getElementById('image-error');
            const file = this.files[0];

            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (!allowedTypes.includes(file.type)) {
                    imageError.textContent = 'รองรับเฉพาะไฟล์ JPG, PNG เท่านั้น';
                } else if (file.size > maxSize) {
                    imageError.textContent = 'ขนาดไฟล์ต้องไม่เกิน 2MB';
                } else {
                    imageError.textContent = '';
                }
            }
        });
    </script>
</body>

</html>