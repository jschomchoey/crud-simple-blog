<?php
include 'includes/db.php';

// get news from database with filtering
function getNews($category_filter = '', $search_filter = '')
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

    $query .= " ORDER BY news.created_at DESC";

    // echo $query;

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

// get all news for dashboard (including inactive)
function getAllNewsForDashboard()
{
    global $mysqli;

    $query = "SELECT news.*, categories.name AS category_name FROM news JOIN categories ON news.category_id = categories.id ORDER BY news.created_at DESC";

    $result = $mysqli->query($query);
    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    return $articles;
}

// get single news by ID
function getNewsById($id)
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT news.*, categories.name AS category_name FROM news JOIN categories ON news.category_id = categories.id WHERE news.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// get all categories
function getAllCategories()
{
    global $mysqli;

    $query = "SELECT * FROM categories ORDER BY name ASC";
    $result = $mysqli->query($query);
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    return $categories;
}

function addNews($title, $content, $category_id, $image, $files, $status)
{
    global $mysqli;

    // create slug
    $slug = strtolower(str_replace(' ', '-', $title));

    // add news to database
    $stmt = $mysqli->prepare("INSERT INTO news (title, content, category_id, image, files, status, slug, views, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
    $stmt->bind_param("ssissss", $title, $content, $category_id, $image, $files, $status, $slug);
    return $stmt->execute();
}

// update news
function updateNews($id, $title, $content, $category_id, $image, $files, $status)
{
    global $mysqli;

    // create slug
    $slug = strtolower(str_replace(' ', '-', $title));

    $stmt = $mysqli->prepare("UPDATE news SET title = ?, content = ?, category_id = ?, image = ?, files = ?, status = ?, slug = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssissssi", $title, $content, $category_id, $image, $files, $status, $slug, $id);
    return $stmt->execute();
}

// delete news
function deleteNews($id)
{
    global $mysqli;

    $stmt = $mysqli->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// เพิ่มฟังก์ชันการนับยอดวิว
function incrementNewsViews($id)
{
    global $mysqli;

    $stmt = $mysqli->prepare("UPDATE news SET views = views + 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// get single news by slug and increment views
function getNewsBySlugAndIncrementViews($slug)
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT news.*, categories.name AS category_name FROM news JOIN categories ON news.category_id = categories.id WHERE news.slug = ? AND news.status = 'active'");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $article = $result->fetch_assoc();

    // เพิ่มยอดวิวถ้าพบข่าว
    if ($article) {
        incrementNewsViews($article['id']);
        $article['views'] = $article['views'] + 1; // อัปเดตยอดวิวในข้อมูลที่ส่งกลับ
    }

    return $article;
}
