<?php
header('Content-Type: application/json');
include './query/q_news.php';

try {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    switch ($action) {
        case 'add':
            handleAddNews();
            break;
        case 'edit':
            handleEditNews();
            break;
        case 'delete':
            handleDeleteNews();
            break;
        case 'toggle_status':
            handleToggleStatus();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการประมวลผล',
        'error' => $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    http_response_code(500);
}

function handleAddNews()
{
    // Validate input
    $errors = validateNewsInput();
    if (!empty($errors)) {
        $response = [
            'success' => false,
            'message' => 'ข้อมูลไม่ถูกต้อง',
            'errors' => $errors
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = $_POST['category'];
    $status = $_POST['status'];
    $imageName = '';
    $pdfName = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['image']);
        if ($uploadResult['success']) {
            $imageName = $uploadResult['filename'];
        } else {
            $response = [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ',
                'errors' => ['image' => $uploadResult['message']]
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }
    }

    // Handle PDF upload
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadPdf($_FILES['pdf']);
        if ($uploadResult['success']) {
            $pdfName = $uploadResult['filename'];
        } else {
            $response = [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปโหลด PDF',
                'errors' => ['pdf' => $uploadResult['message']]
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }
    }

    // Add news to database
    if (addNews($title, $content, $category_id, $imageName, $pdfName, $status)) {
        $response = [
            'success' => true,
            'message' => 'เพิ่มข่าวเรียบร้อยแล้ว'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function handleEditNews()
{
    $news_id = isset($_POST['news_id']) ? (int)$_POST['news_id'] : 0;

    if (!$news_id) {
        $response = [
            'success' => false,
            'message' => 'ไม่พบรหัสข่าวที่ต้องการแก้ไข'
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    // Get current news data
    $currentNews = getNewsById($news_id);
    if (!$currentNews) {
        $response = [
            'success' => false,
            'message' => 'ไม่พบข่าวที่ต้องการแก้ไข'
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    // Validate input
    $errors = validateNewsInput();
    if (!empty($errors)) {
        $response = [
            'success' => false,
            'message' => 'ข้อมูลไม่ถูกต้อง',
            'errors' => $errors
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = $_POST['category'];
    $status = $_POST['status'];
    $imageName = $currentNews['image']; // Keep current image by default
    $pdfName = $currentNews['files']; // Keep current PDF by default
    $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] == '1';
    $removePdf = isset($_POST['remove_pdf']) && $_POST['remove_pdf'] == '1';

    // Handle image removal
    if ($removeImage && $currentNews['image']) {
        deleteImageFile($currentNews['image']);
        $imageName = '';
    }

    // Handle PDF removal
    if ($removePdf && $currentNews['files']) {
        deletePdfFile($currentNews['files']);
        $pdfName = '';
    }

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Delete old image if exists and not already removed
        if (!$removeImage && $currentNews['image']) {
            deleteImageFile($currentNews['image']);
        }

        $uploadResult = uploadImage($_FILES['image']);
        if ($uploadResult['success']) {
            $imageName = $uploadResult['filename'];
        } else {
            $response = [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ',
                'errors' => ['image' => $uploadResult['message']]
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }
    }

    // Handle new PDF upload
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        // Delete old PDF if exists and not already removed
        if (!$removePdf && $currentNews['files']) {
            deletePdfFile($currentNews['files']);
        }

        $uploadResult = uploadPdf($_FILES['pdf']);
        if ($uploadResult['success']) {
            $pdfName = $uploadResult['filename'];
        } else {
            $response = [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปโหลด PDF',
                'errors' => ['pdf' => $uploadResult['message']]
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }
    }

    // Update news in database
    if (updateNews($news_id, $title, $content, $category_id, $imageName, $pdfName, $status)) {
        $response = [
            'success' => true,
            'message' => 'แก้ไขข่าวเรียบร้อยแล้ว'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function handleDeleteNews()
{
    $news_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if (!$news_id) {
        $response = [
            'success' => false,
            'message' => 'ไม่พบรหัสข่าวที่ต้องการลบ'
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    // Get news data before deletion
    $news = getNewsById($news_id);
    if (!$news) {
        $response = [
            'success' => false,
            'message' => 'ไม่พบข่าวที่ต้องการลบ'
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    // Delete image file if exists
    if ($news['image']) {
        deleteImageFile($news['image']);
    }

    // Delete PDF file if exists
    if ($news['files']) {
        deletePdfFile($news['files']);
    }

    // Delete news from database
    if (deleteNews($news_id)) {
        $response = [
            'success' => true,
            'message' => 'ลบข่าวเรียบร้อยแล้ว'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล'
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function handleToggleStatus()
{
    $news_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if (!$news_id) {
        $response = [
            'success' => false,
            'message' => 'ไม่พบรหัสข่าวที่ต้องการเปลี่ยนสถานะ'
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    // ตรวจสอบว่าข่าวนี้มีอยู่จริง
    $news = getNewsById($news_id);
    if (!$news) {
        $response = [
            'success' => false,
            'message' => 'ไม่พบข่าวที่ต้องการเปลี่ยนสถานะ'
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    // ตรวจสอบว่าสถานะที่ส่งมาถูกต้อง
    if (!in_array($status, ['active', 'inactive'])) {
        $response = [
            'success' => false,
            'message' => 'สถานะไม่ถูกต้อง'
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    // อัปเดตสถานะในฐานข้อมูล
    if (updateNewsStatus($news_id, $status)) {
        $response = [
            'success' => true,
            'message' => 'เปลี่ยนสถานะเรียบร้อยแล้ว',
            'new_status' => $status
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะ'
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function validateNewsInput()
{
    $errors = [];

    // Validate title
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    if (empty($title)) {
        $errors['title'] = 'กรุณากรอกหัวข้อข่าว';
    } elseif (strlen($title) < 3) {
        $errors['title'] = 'หัวข้อข่าวต้องมีอย่างน้อย 3 ตัวอักษร';
    } elseif (strlen($title) > 255) {
        $errors['title'] = 'หัวข้อข่าวต้องไม่เกิน 255 ตัวอักษร';
    }

    // Validate content
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    if (empty($content)) {
        $errors['content'] = 'กรุณากรอกเนื้อหาข่าว';
    } elseif (strlen($content) < 10) {
        $errors['content'] = 'เนื้อหาข่าวต้องมีอย่างน้อย 10 ตัวอักษร';
    }

    // Validate category
    $category_id = isset($_POST['category']) ? $_POST['category'] : '';
    if (empty($category_id)) {
        $errors['category'] = 'กรุณาเลือกหมวดหมู่';
    }

    // Validate status
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    if (empty($status) || !in_array($status, ['active', 'inactive'])) {
        $errors['status'] = 'กรุณาเลือกสถานะ';
    }

    // Validate image file if uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageValidation = validateImageFile($_FILES['image']);
        if (!$imageValidation['valid']) {
            $errors['image'] = $imageValidation['message'];
        }
    }

    // Validate PDF file if uploaded
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $pdfValidation = validatePdfFile($_FILES['pdf']);
        if (!$pdfValidation['valid']) {
            $errors['pdf'] = $pdfValidation['message'];
        }
    }

    return $errors;
}

function validateImageFile($file)
{
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowedTypes)) {
        return ['valid' => false, 'message' => 'รองรับเฉพาะไฟล์ JPG, PNG เท่านั้น'];
    }

    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'message' => 'ขนาดไฟล์ต้องไม่เกิน 2MB'];
    }

    return ['valid' => true, 'message' => ''];
}

function validatePdfFile($file)
{
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['type'] !== 'application/pdf') {
        return ['valid' => false, 'message' => 'รองรับเฉพาะไฟล์ PDF เท่านั้น'];
    }

    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'message' => 'ขนาดไฟล์ต้องไม่เกิน 5MB'];
    }

    return ['valid' => true, 'message' => ''];
}

function uploadImage($file)
{
    $uploadDir = 'uploads/images/';

    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Validate file
    $validation = validateImageFile($file);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }

    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $targetPath = $uploadDir . $fileName;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $fileName];
    } else {
        return ['success' => false, 'message' => 'ไม่สามารถอัปโหลดไฟล์ได้'];
    }
}

function uploadPdf($file)
{
    $uploadDir = 'uploads/files/';

    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Validate file
    $validation = validatePdfFile($file);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }

    // Generate unique filename
    $fileName = uniqid() . '_' . time() . '.pdf';
    $targetPath = $uploadDir . $fileName;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $fileName];
    } else {
        return ['success' => false, 'message' => 'ไม่สามารถอัปโหลดไฟล์ได้'];
    }
}

function deleteImageFile($filename)
{
    $filePath = 'uploads/images/' . $filename;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

function deletePdfFile($filename)
{
    $filePath = 'uploads/files/' . $filename;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

function updateNewsStatus($id, $status)
{
    global $mysqli;

    $stmt = $mysqli->prepare("UPDATE news SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    return $stmt->execute();
}
