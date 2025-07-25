<?php
header('Content-Type: application/json');
include_once(__DIR__ . '/../_config.php');
include_once(_DB_ . '/db.php');
include _QUERY_ . '/q_news.php';

try {
    // รับข้อมูลจาก POST
    $category_filter = isset($_POST['category']) ? $_POST['category'] : '';
    $search_filter = isset($_POST['search']) ? $_POST['search'] : '';
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $sort = isset($_POST['sort']) ? $_POST['sort'] : 'date_desc';
    $limit = 5;
    $offset = ($page - 1) * $limit;

    // ฟังก์ชันดึงข่าวแบบ pagination พร้อมการเรียงตามการปักหมุดและ sorting
    function getNewsWithPagination($category_filter = '', $search_filter = '', $limit = 5, $offset = 0, $sort = 'date_desc')
    {
        global $mysqli;

        $query = "SELECT news.*, categories.name AS category_name FROM news JOIN categories ON news.category_id = categories.id WHERE news.status = 'active'";

        $conditions = [];
        $params = [];
        $types = '';

        // Add category filter
        if (!empty($category_filter)) {
            $conditions[] = "news.category_id = ?";
            $params[] = $category_filter;
            $types .= 'i';
        }

        // Add search filter
        if (!empty($search_filter)) {
            $conditions[] = "(news.title LIKE ? OR news.content LIKE ?)";
            $searchTerm = '%' . $search_filter . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }

        // Add WHERE clause if there are conditions
        if (!empty($conditions)) {
            $query .= " AND " . implode(' AND ', $conditions);
        }

        // แก้ไขการเรียงลำดับ: ปักหมุดขึ้นก่อน จากนั้นเรียงตาม sort parameter
        $orderBy = "news.pin DESC";

        switch ($sort) {
            case 'date_desc':
                $orderBy .= ", news.created_at DESC";
                break;
            case 'date_asc':
                $orderBy .= ", news.created_at ASC";
                break;
            case 'views_desc':
                $orderBy .= ", news.views DESC";
                break;
            case 'views_asc':
                $orderBy .= ", news.views ASC";
                break;
            default:
                $orderBy .= ", news.created_at DESC";
        }

        $query .= " ORDER BY " . $orderBy . " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $mysqli->prepare($query);

        // Bind parameters if any
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        $result = $stmt->get_result();
        $articles = [];
        while ($row = $result->fetch_assoc()) {
            $articles[] = $row;
        }
        return $articles;
    }

    // นับจำนวนข่าวทั้งหมดตามเงื่อนไข
    function countNews($category_filter = '', $search_filter = '')
    {
        global $mysqli;

        $query = "SELECT COUNT(*) as total FROM news JOIN categories ON news.category_id = categories.id WHERE news.status = 'active'";

        $conditions = [];
        $params = [];
        $types = '';

        // Add category filter
        if (!empty($category_filter)) {
            $conditions[] = "news.category_id = ?";
            $params[] = $category_filter;
            $types .= 'i';
        }

        // Add search filter
        if (!empty($search_filter)) {
            $conditions[] = "(news.title LIKE ? OR news.content LIKE ?)";
            $searchTerm = '%' . $search_filter . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }

        // Add WHERE clause if there are conditions
        if (!empty($conditions)) {
            $query .= " AND " . implode(' AND ', $conditions);
        }

        $stmt = $mysqli->prepare($query);

        // Bind parameters if any
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    $news = getNewsWithPagination($category_filter, $search_filter, $limit, $offset, $sort);
    $totalNews = countNews($category_filter, $search_filter);
    $totalPages = ceil($totalNews / $limit);

    // Create HTML output
    $html = '';
    if (empty($news)) {
        $html = '<p>ไม่พบข่าวที่ตรงกับเงื่อนไขการค้นหา</p>';
    } else {
        foreach ($news as $row) {
            $slug = $row['slug']; // ใช้ slug จากฐานข้อมูล
            $article_url = "article.php?slug=" . urlencode($slug);
            $isPinned = $row['pin'] == 1; // ตรวจสอบว่าเป็นข่าวปักหมุดหรือไม่
            $pinnedClass = $isPinned ? 'pinned-article' : '';

            $html .= '<div class="article-wrapper d-flex justify-content-start ' . $pinnedClass . '">';

            // แสดง badge ปักหมุดถ้าเป็นข่าวปักหมุด
            if ($isPinned) {
                $html .= '<div class="pinned-badge">';
                $html .= '<i class="fas fa-thumbtack"></i>ปักหมุด';
                $html .= '</div>';
            }

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
            $html .= '<p>' . date("d M Y", strtotime($row['created_at'])) . '</p>';
            $html .= '<p class="ms-2"> - </p>';
            $html .= '<p class="ms-2">' . htmlspecialchars($row['category_name']) . '</p>';
            $html .= '<p class="ms-2"> - </p>';
            $html .= '<p class="ms-2 views-count"><i class="fas fa-eye"></i> ' . number_format($row['views']) . '</p>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
    }

    // Return JSON response
    $response = [
        'success' => true,
        'html' => $html,
        'count' => count($news),
        'totalNews' => $totalNews,
        'totalPages' => max(1, $totalPages),
        'currentPage' => $page,
        'sort' => $sort
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
