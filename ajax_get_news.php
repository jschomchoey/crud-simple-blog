<?php
header('Content-Type: application/json');
include './query/q_news.php';

try {
    // รับข้อมูลจาก POST
    $category_filter = isset($_POST['category']) ? $_POST['category'] : '';
    $search_filter = isset($_POST['search']) ? $_POST['search'] : '';
    $news = getNews($category_filter, $search_filter);

    // Create HTML output
    $html = '';
    if (empty($news)) {
        $html = '<p>ไม่พบข่าวที่ตรงกับเงื่อนไขการค้นหา</p>';
    } else {
        foreach ($news as $row) {
            $slug = $row['slug']; // ใช้ slug จากฐานข้อมูล
            $article_url = "article.php?slug=" . urlencode($slug);

            $html .= '<div class="article-wrapper d-flex justify-content-start">';
            $html .= '<div class="article-image-container">';
            $html .= '<img class="article-image" src="uploads/images/' . htmlspecialchars($row['image']) . '" alt="">';
            $html .= '</div>';
            $html .= '<div class="article-content d-flex flex-column justify-content-between">';
            $html .= '<div>';
            $html .= '<a href="' . $article_url . '" class="article-heading" style="text-decoration: none;">';
            $html .= '<h2>' . htmlspecialchars($row['title']) . '</h2>';
            $html .= '</a>';
            $html .= '<p>' . htmlspecialchars(substr($row['content'], 0, 255)) . '...</p>';
            $html .= '</div>';
            $html .= '<div class="d-flex">';
            $html .= '<p><strong>' . date("d M Y", strtotime($row['created_at'])) . '</strong></p>';
            $html .= '<p class="ms-2"> - </p>';
            $html .= '<p class="ms-2"><strong>' . htmlspecialchars($row['category_name']) . '</strong></p>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
    }

    // Return JSON response
    $response = [
        'success' => true,
        'html' => $html,
        'count' => count($news)
    ];
    $responseCode = 200;
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการโหลดข้อมูล',
        'error' => $e->getMessage()
    ];
    $responseCode = 500;
}

http_response_code($responseCode);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
