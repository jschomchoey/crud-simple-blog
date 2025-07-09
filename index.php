<?php
include './query/q_news.php';

$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';
$news = getNews($category_filter, $search_filter);

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
            <!-- Layout Toggle Buttons -->
            <div class="layout-toggle">
                <button class="layout-btn active" id="row-layout" onclick="switchLayout('row')" title="แสดงแบบแถว">
                    <i class="fas fa-bars"></i>
                </button>
                <button class="layout-btn" id="grid-layout" onclick="switchLayout('grid')" title="แสดงแบบกริด">
                    <i class="fas fa-th"></i>
                </button>
            </div>

            <article class="article" id="article-container">
                <?php
                if (empty($news)) {
                    echo '<p>ไม่พบข่าวที่ตรงกับเงื่อนไขการค้นหา</p>';
                } else {
                    foreach ($news as $row) {
                        $slug = $row['slug']; // ใช้ slug จากฐานข้อมูล
                        $article_url = "article.php?slug=" . urlencode($slug);
                ?>

                        <div class="article-wrapper d-flex justify-content-start">
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
                                    <p><strong><?php echo date("d M Y", strtotime($row['created_at'])); ?></strong></p>
                                    <p class="ms-2"> - </p>
                                    <p class="ms-2"><strong><?php echo htmlspecialchars($row['category_name']); ?></strong></p>
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
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
    <script src="js/script.js"></script>

    <script>
        let currentCategory = '<?php echo $category_filter; ?>';
        let currentSearch = '<?php echo $search_filter; ?>';
        let currentLayout = 'row'; // Default layout

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

        // โหลดข่าวด้วย Ajax แบบ FormData
        function loadNews(category = '', search = '') {
            const loading = document.getElementById('loading');
            const articleContainer = document.getElementById('article-container');

            loading.style.display = 'block';

            let formData = new FormData();
            formData.append('category', category);
            formData.append('search', search);

            $.ajax({
                type: 'POST',
                url: 'ajax_get_news.php',
                cache: false,
                contentType: false,
                processData: false,
                data: formData
            }).done(function(result) {
                if (result.success) {
                    articleContainer.innerHTML = result.html;

                    // Apply current layout after loading new content
                    switchLayout(currentLayout);

                    // อัปเดต URL โดยไม่รีเฟรช
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.delete('category');
                    newUrl.searchParams.delete('search');

                    if (category) newUrl.searchParams.set('category', category);
                    if (search) newUrl.searchParams.set('search', search);

                    window.history.pushState({}, '', newUrl);

                    // อัปเดตตัวแปรสถานะ
                    currentCategory = category;
                    currentSearch = search;

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

        function filterByCategory(categoryId) {
            loadNews(categoryId, currentSearch);
        }


        function searchNews(event) {
            event.preventDefault();
            const searchValue = document.getElementById('search-input').value;
            loadNews(currentCategory, searchValue);
        }

        function clearFilters() {
            document.getElementById('form-select').value = '';
            document.getElementById('search-input').value = '';
            loadNews('', '');
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

            document.getElementById('form-select').value = category;
            document.getElementById('search-input').value = search;

            loadNews(category, search);
        });
    </script>
</body>

</html>