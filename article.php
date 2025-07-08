<?php
include './query/q_news.php';

// ตรวจสอบ slug ที่ส่งมา
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: index.php');
    exit();
}

$slug = $_GET['slug'];

// ดึงข่าวจาก slug
function getNewsBySlug($slug)
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT news.*, categories.name AS category_name FROM news JOIN categories ON news.category_id = categories.id WHERE news.slug = ? AND news.status = 'active'");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

$article = getNewsBySlug($slug);

// ถ้าไม่พบข่าวหรือข่าวไม่ active ให้กลับไปหน้าหลัก
if (!$article) {
    header('Location: index.php');
    exit();
}

// ดึงข่าวที่เกี่ยวข้อง (หมวดหมู่เดียวกัน แต่ไม่ใช่ข่าวปัจจุบัน)
function getRelatedNews($category_id, $current_id, $limit = 3)
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT news.*, categories.name AS category_name FROM news JOIN categories ON news.category_id = categories.id WHERE news.category_id = ? AND news.id != ? AND news.status = 'active' ORDER BY news.created_at DESC LIMIT ?");
    $stmt->bind_param("iii", $category_id, $current_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $related = [];
    while ($row = $result->fetch_assoc()) {
        $related[] = $row;
    }
    return $related;
}

$relatedNews = getRelatedNews($article['category_id'], $article['id']);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - บอ ลอ อ็อก บล็อก</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($article['content'], 0, 160)); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/scss/main.css">
    <style>
        .article-container {
            max-width: 900px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0px 0px 15px #04a8e328;
        }

        .article-header {
            margin-bottom: 30px;
            border-bottom: 2px solid #f1f4f5;
            padding-bottom: 20px;
        }

        .article-title {
            color: #04a7e3;
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 15px;
        }

        .article-meta {
            color: #6c757d;
            font-size: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .article-meta .badge {
            background-color: #04a7e3;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
        }

        .article-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 15px;
            margin: 30px 0;
            box-shadow: 0px 5px 20px rgba(0, 0, 0, 0.1);
        }

        .article-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #333;
            margin-bottom: 40px;
        }

        .article-content p {
            margin-bottom: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: #5a6268;
            color: white;
            transform: translateY(-2px);
        }

        .related-news {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #f1f4f5;
        }

        .related-news h3 {
            color: #04a7e3;
            margin-bottom: 25px;
            font-size: 1.8rem;
        }

        .related-item {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .related-item:hover {
            transform: translateY(-3px);
            box-shadow: 0px 5px 15px rgba(4, 167, 227, 0.2);
            border-color: #04a7e3;
        }

        .related-item h5 {
            color: #04a7e3;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .related-item h5 a {
            color: inherit;
            text-decoration: none;
        }

        .related-item h5 a:hover {
            color: #0396d1;
        }

        .related-item p {
            color: #6c757d;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .related-item .meta {
            font-size: 0.85rem;
            color: #868e96;
        }

        @media (max-width: 768px) {
            .article-container {
                margin: 10px;
                padding: 20px;
            }

            .article-title {
                font-size: 2rem;
            }

            .article-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <nav class="nav-bar d-flex justify-content-between align-items-center">
        <a class="heading-text" href="index.php">บอ ลอ อ็อก บล็อก</a>
        <div>
            <a href="dashboard.php" class="theme-button me-2">แดชบอร์ด</a>
            <a href="index.php" class="theme-button">หน้าหลัก</a>
        </div>
    </nav>

    <div class="article-container">
        <a href="index.php" class="back-button">
            ← กลับไปหน้าหลัก
        </a>

        <article>
            <header class="article-header">
                <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                <div class="article-meta">
                    <span><strong>วันที่:</strong> <?php echo date("d F Y", strtotime($article['created_at'])); ?></span>
                    <span class="badge"><?php echo htmlspecialchars($article['category_name']); ?></span>
                    <?php if ($article['updated_at']): ?>
                        <span><strong>แก้ไขล่าสุด:</strong> <?php echo date("d F Y", strtotime($article['updated_at'])); ?></span>
                    <?php endif; ?>
                </div>
            </header>

            <?php if ($article['image']): ?>
                <img src="uploads/images/<?php echo htmlspecialchars($article['image']); ?>"
                    alt="<?php echo htmlspecialchars($article['title']); ?>"
                    class="article-image">
            <?php endif; ?>

            <div class="article-content">
                <?php echo nl2br(htmlspecialchars($article['content'])); ?>
            </div>
        </article>

        <?php if (!empty($relatedNews)): ?>
            <section class="related-news">
                <h3>ข่าวที่เกี่ยวข้อง</h3>
                <div class="row">
                    <?php foreach ($relatedNews as $related): ?>
                        <div class="col-md-12">
                            <div class="related-item">
                                <h5>
                                    <a href="article.php?slug=<?php echo urlencode($related['slug']); ?>">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </h5>
                                <p><?php echo htmlspecialchars(substr($related['content'], 0, 150)) . '...'; ?></p>
                                <div class="meta">
                                    <?php echo date("d M Y", strtotime($related['created_at'])); ?> |
                                    <?php echo htmlspecialchars($related['category_name']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // เพิ่ม smooth scroll สำหรับลิงค์ภายใน
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // เพิ่ม reading progress indicator
        window.addEventListener('scroll', function() {
            const article = document.querySelector('article');
            const articleTop = article.offsetTop;
            const articleHeight = article.offsetHeight;
            const windowHeight = window.innerHeight;
            const scrollTop = window.pageYOffset;

            const progress = Math.min(
                Math.max((scrollTop - articleTop + windowHeight) / articleHeight, 0),
                1
            );

            // สามารถเพิ่ม progress bar ได้ที่นี่
        });
    </script>
</body>

</html>