<?php
include_once(__DIR__ . '/_config.php');
include_once(_DB_ . '/db.php');
include_once(_QUERY_ . '/q_news.php');


$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$limit = 5;
$offset = ($page - 1) * $limit;

// แก้ไขฟังก์ชัน getNews เพื่อรองรับ pagination และการเรียงตามการปักหมุดและ sorting
function getNewsWithPagination($category_filter = '', $search_filter = '', $limit = 5, $offset = 0, $sort = 'date_desc')
{
    global $mysqli;
    // print_r($mysqli);

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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/scss/main.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* เพิ่ม Style สำหรับข่าวปักหมุด */
        .pinned-article {
            position: relative;
            border: 2px solid #ffc107;
            border-radius: 25px;
            background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
            box-shadow: 0px 0px 20px rgba(255, 193, 7, 0.3);
        }

        .pinned-badge {
            position: absolute;
            top: -10px;
            right: 15px;
            background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
            color: #212529;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0px 2px 8px rgba(255, 193, 7, 0.4);
            z-index: 10;
        }

        .pinned-badge i {
            margin-right: 5px;
        }

        .pinned-article .article-heading h2 {
            color: #ff8c00;
        }

        .pinned-article:hover {
            transform: translateY(-3px);
            box-shadow: 0px 8px 25px rgba(255, 193, 7, 0.4);
        }

        /* สำหรับ Grid Layout */
        .grid-layout .pinned-article {
            border: 2px solid #ffc107;
            background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
        }

        .grid-layout .pinned-badge {
            top: -5px;
            right: 10px;
        }

        /* Style สำหรับแสดงยอดวิว */
        .views-count {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .views-count i {
            margin-right: 3px;
        }

        /* Style สำหรับ sort dropdown */
        .sort-dropdown {
            position: relative;
            display: inline-block;
        }

        .sort-dropdown .dropdown-toggle {
            background-color: #ffffff;
            border: 2px solid #04a7e3;
            color: #04a7e3;
            border-radius: 15px;
            padding: 10px 15px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sort-dropdown .dropdown-toggle:hover {
            background-color: #f8f9fa;
        }

        .sort-dropdown .dropdown-toggle::after {
            border: none;
            content: '\f107';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-left: 5px;
        }

        .sort-dropdown .dropdown-menu {
            border: 1px solid #04a7e3;
            border-radius: 15px;
            box-shadow: 0px 5px 15px rgba(4, 167, 227, 0.2);
            padding: 5px 0;
        }

        .sort-dropdown .dropdown-item {
            padding: 10px 15px;
            color: #333;
            transition: all 0.3s ease;
        }

        .sort-dropdown .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #04a7e3;
        }

        .sort-dropdown .dropdown-item.active {
            background-color: #04a7e3;
            color: white;
        }

        .sort-dropdown .dropdown-item i {
            margin-right: 8px;
            width: 16px;
        }
    </style>
</head>

<body>
    <nav class="nav-bar d-flex justify-content-between align-items-center">
        <a class="heading-text" href="index.php">บอ ลอ อ็อก บล็อก</a>
        <div>
            <a href="dashboard.php" class="theme-button">แดชบอร์ด</a>
        </div>
    </nav>
    <div class="d-flex">
        <aside class="side-bar">
            <p>หมวดหมู่</p>
            <select class="form-select" id="form-select" onchange="filterByCategory(this.value)">
                <option value="">ทั้งหมด</option>
                <?php
                $category_query = "SELECT * FROM categories";
                $category_result = $mysqli->query($category_query);
                while ($category = $category_result->fetch_assoc()) {
                    $selected = ($category_filter == $category['id']) ? 'selected' : '';
                    echo '<option value="' . $category['id'] . '" ' . $selected . '>' . $category['name'] . '</option>';
                }
                ?>
            </select>
            <p>ค้นหา</p>
            <form id="search-form" onsubmit="searchNews(event)">
                <input type="text" name="search" id="search-input" class="form-control" placeholder="ค้นหาข่าว" value="<?php echo htmlspecialchars($search_filter); ?>">
                <button type="submit" class="theme-button">ค้นหา</button>
                <button type="button" class="clear-filter" id="clear-filter" onclick="clearFilters()" style="display: none;">ล้างตัวกรอง</button>
            </form>
        </aside>
        <div class="article-section">
            <!-- Layout Toggle Buttons and Pagination -->
            <div class="pagination-section">
                <div class="layout-toggle">
                    <button class="layout-btn active" id="row-layout" onclick="switchLayout('row')" title="แสดงแบบแถว">
                        <i class="fas fa-bars"></i>
                    </button>
                    <button class="layout-btn" id="grid-layout" onclick="switchLayout('grid')" title="แสดงแบบกริด">
                        <i class="fas fa-th"></i>
                    </button>
                </div>

                <div class="pagination-buttons" id="pagination-buttons">
                    <button class="page-btn" onclick="goToPage(<?php echo $page - 1; ?>)" <?php echo ($page <= 1) ? 'disabled' : ''; ?>>
                        <i class="fas fa-angle-left"></i>
                    </button>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);

                    for ($i = $start; $i <= $end; $i++) {
                        $activeClass = ($i == $page) ? 'active' : '';
                        echo "<button class='page-btn $activeClass' onclick='goToPage($i)'>$i</button>";
                    }
                    ?>

                    <button class="page-btn" onclick="goToPage(<?php echo $page + 1; ?>)" <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>>
                        <i class="fas fa-angle-right"></i>
                    </button>
                </div>
                <div class="pagination-info" id="pagination-info">
                    หน้า <?php echo $page; ?> จาก <?php echo max(1, $totalPages); ?> (<?php echo $totalNews; ?> รายการ)
                </div>

                <div class="sort-dropdown dropdown">
                    <button class="dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-sort" id="sort-icon"></i>
                        <span id="sort-text">
                            <?php
                            switch ($sort) {
                                case 'date_desc':
                                    echo 'วันที่ใหม่ไปเก่า';
                                    break;
                                case 'date_asc':
                                    echo 'วันที่เก่าไปใหม่';
                                    break;
                                case 'views_desc':
                                    echo 'ยอดวิวเยอะไปน้อย';
                                    break;
                                case 'views_asc':
                                    echo 'ยอดวิวน้อยไปเยอะ';
                                    break;
                                default:
                                    echo 'วันที่ใหม่ไปเก่า';
                            }
                            ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                        <li><a class="dropdown-item <?php echo ($sort == 'date_desc') ? 'active' : ''; ?>" href="#" onclick="event.preventDefault(); sortNews('date_desc');">
                                <i class="fas fa-calendar-alt"></i>วันที่ใหม่ไปเก่า
                            </a></li>
                        <li><a class="dropdown-item <?php echo ($sort == 'date_asc') ? 'active' : ''; ?>" href="#" onclick="event.preventDefault(); sortNews('date_asc');">
                                <i class="fas fa-calendar-alt"></i>วันที่เก่าไปใหม่
                            </a></li>
                        <li><a class="dropdown-item <?php echo ($sort == 'views_desc') ? 'active' : ''; ?>" href="#" onclick="event.preventDefault(); sortNews('views_desc');">
                                <i class="fas fa-eye"></i>ยอดวิวเยอะไปน้อย
                            </a></li>
                        <li><a class="dropdown-item <?php echo ($sort == 'views_asc') ? 'active' : ''; ?>" href="#" onclick="event.preventDefault(); sortNews('views_asc');">
                                <i class="fas fa-eye"></i>ยอดวิวน้อยไปเยอะ
                            </a></li>
                    </ul>
                </div>
            </div>

            <article class="article row-layout" id="article-container">
                <?php
                if (empty($news)) {
                    echo '<p>ไม่พบข่าวที่ตรงกับเงื่อนไขการค้นหา</p>';
                } else {
                    foreach ($news as $row) {
                        $slug = $row['slug']; // ใช้ slug จากฐานข้อมูล
                        $article_url = "article.php?slug=" . urlencode($slug);
                        $isPinned = $row['pin'] == 1; // ตรวจสอบว่าเป็นข่าวปักหมุดหรือไม่
                        $pinnedClass = $isPinned ? 'pinned-article' : '';
                ?>

                        <div class="article-wrapper d-flex justify-content-start <?php echo $pinnedClass; ?>">
                            <?php if ($isPinned): ?>
                                <div class="pinned-badge">
                                    <i class="fas fa-thumbtack"></i>ปักหมุด
                                </div>
                            <?php endif; ?>

                            <div class="article-image-container">
                                <img class="article-image" src="uploads/images/<?php echo htmlspecialchars($row['image']); ?>" alt="">
                            </div>

                            <div class="article-content d-flex flex-column justify-content-between">
                                <div>
                                    <a href="<?php echo $article_url; ?>" class="article-heading" style="text-decoration: none;">
                                        <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                                    </a>
                                    <p><?php echo htmlspecialchars(substr($row['content'], 0, 255)) . '...'; ?></p>
                                </div>
                                <div class="d-flex">
                                    <p><?php echo date("d M Y", strtotime($row['created_at'])); ?></p>
                                    <p class="ms-2"> - </p>
                                    <p class="ms-2"><?php echo htmlspecialchars($row['category_name']); ?></p>
                                    <p class="ms-2"> - </p>
                                    <p class="ms-2 views-count"><i class="fas fa-eye"></i> <?php echo number_format($row['views']); ?></p>
                                </div>
                            </div>
                        </div>
                <?php
                    }
                }
                ?>
            </article>
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="loading" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">กำลังโหลด...</span>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
    <script src="js/script.js"></script>

    <script>
        let currentCategory = '<?php echo $category_filter; ?>';
        let currentSearch = '<?php echo $search_filter; ?>';
        let currentPage = <?php echo $page; ?>;
        let currentLayout = 'row'; // Default layout
        let currentSort = '<?php echo $sort; ?>'; // Current sort

        // แสดงปุ่มล้างตัวกรองหากมีการกรอง
        function updateClearButton() {
            const clearButton = document.getElementById('clear-filter');
            if (currentCategory || currentSearch) {
                clearButton.style.display = 'flex';
            } else {
                clearButton.style.display = 'none';
            }
        }

        // ฟังก์ชันสลับ layout
        function switchLayout(layout) {
            currentLayout = layout;
            const articleContainer = document.getElementById('article-container');
            const rowBtn = document.getElementById('row-layout');
            const gridBtn = document.getElementById('grid-layout');

            // Remove active class from all buttons
            rowBtn.classList.remove('active');
            gridBtn.classList.remove('active');

            if (layout === 'row') {
                articleContainer.classList.remove('grid-layout');
                articleContainer.classList.add('row-layout');
                rowBtn.classList.add('active');
            } else {
                articleContainer.classList.remove('row-layout');
                articleContainer.classList.add('grid-layout');
                gridBtn.classList.add('active');
            }

            // Save layout preference
            localStorage.setItem('preferred_layout', layout);
        }

        // ฟังก์ชันสลับ sort
        function sortNews(sortType) {
            // ปิด dropdown
            const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('sortDropdown'));
            if (dropdown) {
                dropdown.hide();
            }

            currentSort = sortType;

            // อัปเดต UI ของ dropdown
            const sortText = document.getElementById('sort-text');
            const dropdownItems = document.querySelectorAll('.dropdown-item');

            // ลบ active class จากทุกรายการ
            dropdownItems.forEach(item => item.classList.remove('active'));

            // อัปเดตข้อความและเพิ่ม active class
            switch (sortType) {
                case 'date_desc':
                    sortText.textContent = 'วันที่ใหม่ไปเก่า';
                    dropdownItems[0].classList.add('active');
                    break;
                case 'date_asc':
                    sortText.textContent = 'วันที่เก่าไปใหม่';
                    dropdownItems[1].classList.add('active');
                    break;
                case 'views_desc':
                    sortText.textContent = 'ยอดวิวเยอะไปน้อย';
                    dropdownItems[2].classList.add('active');
                    break;
                case 'views_asc':
                    sortText.textContent = 'ยอดวิวน้อยไปเยอะ';
                    dropdownItems[3].classList.add('active');
                    break;
            }

            // Reset to page 1 when sorting
            currentPage = 1;
            loadNews(currentCategory, currentSearch, 1, currentSort);
        }

        // ฟังก์ชันไปยังหน้าที่ระบุ
        function goToPage(page) {
            if (page < 1) return;
            currentPage = page;
            loadNews(currentCategory, currentSearch, page, currentSort);
        }

        // โหลดข่าวด้วย Ajax แบบ FormData
        function loadNews(category = '', search = '', page = 1, sort = 'date_desc') {
            const loading = document.getElementById('loading');
            const articleContainer = document.getElementById('article-container');

            loading.style.display = 'block';

            let formData = new FormData();
            formData.append('category', category);
            formData.append('search', search);
            formData.append('page', page);
            formData.append('sort', sort);

            $.ajax({
                type: 'POST',
                url: './api/ajax_get_news.php',
                cache: false,
                contentType: false,
                processData: false,
                data: formData
            }).done(function(result) {
                if (result.success) {
                    articleContainer.innerHTML = result.html;

                    // Apply current layout after loading new content
                    switchLayout(currentLayout);

                    // อัปเดต pagination info และ buttons
                    updatePaginationInfo(result.currentPage, result.totalPages, result.totalNews);
                    updatePaginationButtons(result.currentPage, result.totalPages);

                    // อัปเดต URL โดยไม่รีเฟรช
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.delete('category');
                    newUrl.searchParams.delete('search');
                    newUrl.searchParams.delete('page');
                    newUrl.searchParams.delete('sort');

                    if (category) newUrl.searchParams.set('category', category);
                    if (search) newUrl.searchParams.set('search', search);
                    if (page > 1) newUrl.searchParams.set('page', page);
                    if (sort !== 'date_desc') newUrl.searchParams.set('sort', sort);

                    window.history.pushState({}, '', newUrl);

                    // อัปเดตตัวแปรสถานะ
                    currentCategory = category;
                    currentSearch = search;
                    currentPage = page;
                    currentSort = sort;

                    updateClearButton();
                } else {
                    articleContainer.innerHTML = '<p>เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
                }
            }).fail(function(result) {
                console.error('Error:', result);
                articleContainer.innerHTML = '<p>เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
            }).always(function() {
                loading.style.display = 'none';
            });
        }

        // อัปเดตข้อมูล pagination
        function updatePaginationInfo(currentPage, totalPages, totalNews) {
            const paginationInfo = document.getElementById('pagination-info');
            paginationInfo.textContent = `หน้า ${currentPage} จาก ${Math.max(1, totalPages)} (${totalNews} รายการ)`;
        }

        // อัปเดตปุ่ม pagination
        function updatePaginationButtons(currentPage, totalPages) {
            const paginationButtons = document.getElementById('pagination-buttons');

            let buttonsHtml = '';

            // ปุ่มหน้าก่อนหน้า
            buttonsHtml += `<button class="page-btn" onclick="goToPage(${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''}>
                <i class="fas fa-angle-left"></i>
            </button>`;

            // ปุ่มหมายเลขหน้า
            const start = Math.max(1, currentPage - 2);
            const end = Math.min(totalPages, currentPage + 2);

            for (let i = start; i <= end; i++) {
                const activeClass = (i == currentPage) ? 'active' : '';
                buttonsHtml += `<button class="page-btn ${activeClass}" onclick="goToPage(${i})">${i}</button>`;
            }

            // ปุ่มหน้าถัดไป
            buttonsHtml += `<button class="page-btn" onclick="goToPage(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''}>
                <i class="fas fa-angle-right"></i>
            </button>`;

            paginationButtons.innerHTML = buttonsHtml;
        }

        function filterByCategory(categoryId) {
            currentPage = 1; // รีเซ็ตไปหน้า 1 เมื่อกรอง
            loadNews(categoryId, currentSearch, 1, currentSort);
        }

        function searchNews(event) {
            event.preventDefault();
            const searchValue = document.getElementById('search-input').value;
            currentPage = 1; // รีเซ็ตไปหน้า 1 เมื่อค้นหา
            loadNews(currentCategory, searchValue, 1, currentSort);
        }

        function clearFilters() {
            document.getElementById('form-select').value = '';
            document.getElementById('search-input').value = '';
            currentPage = 1;
            loadNews('', '', 1, currentSort);
        }

        // เรียกใช้ฟังก์ชันเมื่อโหลดหน้าเว็บ
        document.addEventListener('DOMContentLoaded', function() {
            updateClearButton();

            // Load saved layout preference
            const savedLayout = localStorage.getItem('preferred_layout');
            if (savedLayout) {
                switchLayout(savedLayout);
            }
        });

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            const urlParams = new URLSearchParams(window.location.search);
            const category = urlParams.get('category') || '';
            const search = urlParams.get('search') || '';
            const page = parseInt(urlParams.get('page')) || 1;
            const sort = urlParams.get('sort') || 'date_desc';

            document.getElementById('form-select').value = category;
            document.getElementById('search-input').value = search;

            // Update sort dropdown text and active state
            const sortText = document.getElementById('sort-text');
            const dropdownItems = document.querySelectorAll('.dropdown-item');

            dropdownItems.forEach(item => item.classList.remove('active'));

            switch (sort) {
                case 'date_desc':
                    sortText.textContent = 'วันที่ใหม่ไปเก่า';
                    dropdownItems[0].classList.add('active');
                    break;
                case 'date_asc':
                    sortText.textContent = 'วันที่เก่าไปใหม่';
                    dropdownItems[1].classList.add('active');
                    break;
                case 'views_desc':
                    sortText.textContent = 'ยอดวิวเยอะไปน้อย';
                    dropdownItems[2].classList.add('active');
                    break;
                case 'views_asc':
                    sortText.textContent = 'ยอดวิวน้อยไปเยอะ';
                    dropdownItems[3].classList.add('active');
                    break;
            }

            loadNews(category, search, page, sort);
        });
    </script>
</body>

</html>