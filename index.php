<?php
/**
 * AriaStore CMS (اریا استور) - Version 3 Beta 1
 * Author: Sadra Olfati / صدرا الفتی
 * License: MIT License
 * Website: https://xeondev.ir/ariastore
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
session_start();

// -------------------------------------------------------------------------
// 1. اتصال به پایگاه داده و توابع نصب و راه‌اندازی (مشترک با admin.php)
// -------------------------------------------------------------------------
function get_db() {
    $db_file = 'database.sqlite';
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $db;
}

function init_database_schema($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT)");
    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, email TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT UNIQUE, name TEXT, parent_id INTEGER DEFAULT 0, image TEXT DEFAULT '', sort_order INTEGER DEFAULT 0)");
    $db->exec("CREATE TABLE IF NOT EXISTS products (id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT UNIQUE, title TEXT, category_slug TEXT, price INTEGER DEFAULT 0, old_price INTEGER DEFAULT 0, image TEXT, gallery TEXT, description TEXT, specs TEXT, variables TEXT DEFAULT '', external_link TEXT DEFAULT '', is_featured INTEGER DEFAULT 0, is_special INTEGER DEFAULT 0, views INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS posts (id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT UNIQUE, title TEXT, author TEXT DEFAULT 'مدیر', image TEXT, excerpt TEXT, content TEXT, views INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS pages (id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT UNIQUE, title TEXT, content TEXT, is_published INTEGER DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS sliders (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, image TEXT, link TEXT, sort_order INTEGER DEFAULT 0)");
    $db->exec("CREATE TABLE IF NOT EXISTS banners (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, image TEXT, link TEXT, position TEXT, sort_order INTEGER DEFAULT 0)");
    $db->exec("CREATE TABLE IF NOT EXISTS menus (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, link TEXT, parent_id INTEGER DEFAULT 0, badge_text TEXT DEFAULT '', badge_color TEXT DEFAULT '#ef4444', is_mega INTEGER DEFAULT 0, mega_content TEXT DEFAULT '', sort_order INTEGER DEFAULT 0)");
    $db->exec("CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY AUTOINCREMENT, item_slug TEXT, item_type TEXT, name TEXT, comment TEXT, rating INTEGER DEFAULT 5, parent_id INTEGER DEFAULT NULL, reply_text TEXT DEFAULT '', status TEXT DEFAULT 'approved', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS likes (id INTEGER PRIMARY KEY AUTOINCREMENT, item_slug TEXT, item_type TEXT, user_hash TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE(item_slug, item_type, user_hash))");
}

function is_site_installed($db) {
    try {
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'installed'");
        $stmt->execute();
        $row = $stmt->fetch();
        return ($row && $row['value'] === '1');
    } catch (Exception $e) {
        return false;
    }
}

function seed_placeholder_data($db) {
    $default_socials = json_encode([
        [
            'name' => 'اینستاگرام',
            'url' => 'https://instagram.com',
            'icon_png' => 'https://cdn-icons-png.flaticon.com/512/174/174855.png'
        ],
        [
            'name' => 'تلگرام',
            'url' => 'https://t.me',
            'icon_png' => 'https://cdn-icons-png.flaticon.com/512/2111/2111646.png'
        ],
        [
            'name' => 'واتساپ',
            'url' => 'https://whatsapp.com',
            'icon_png' => 'https://cdn-icons-png.flaticon.com/512/733/733585.png'
        ]
    ], JSON_UNESCAPED_UNICODE);

    $default_settings = [
        'site_title' => 'اریا استور',
        'site_subtitle' => 'معرفی، بررسی و راهنمای خرید بهترین محصولات',
        'logo_url' => '',
        'primary_color' => '#4f46e5',
        'secondary_color' => '#ec4899',
        'top_bar_text' => 'جشنواره معرفی محصولات اریا استور با اتصال به بهترین فروشندگان',
        'top_bar_phone' => '۰۲۱-۱۲۳۴۵۶۷۸',
        'show_hero_slider' => '1',
        'show_categories' => '1',
        'categories_title' => 'دسته‌بندی‌های محبوب محصولات',
        'show_middle_banners' => '1',
        'show_special_offers' => '1',
        'special_offers_title' => 'پیشنهاد شگفت‌انگیز',
        'show_latest_products' => '1',
        'latest_products_title' => 'جدیدترین محصولات',
        'show_blog_slider' => '1',
        'blog_slider_title' => 'آخرین مقالات و راهنمای خرید',
        'footer_col1_title' => 'معرفی اریا استور',
        'footer_about' => 'اریا استور مرجعی تخصصی برای بررسی، مقایسه و معرفی محصولات است. ما شما را مستقیماً به معتبرترین صفحات خرید کالا هدایت می‌کنیم.',
        'footer_col2_title' => 'دسترسی سریع',
        'footer_col2_links' => "صفحه اصلی|?\nهمه محصولات|?route=shop\nلیست علاقه‌مندی‌ها|?route=wishlist\nمقالات و آموزش‌ها|?route=blog",
        'footer_col3_title' => 'راهنمای کاربران',
        'footer_col3_links' => "درباره ما|?page=about\nتماس با ما|?page=contact\nرویه بررسی کالا|#\nحریم خصوصی کاربران|#",
        'footer_col4_title' => 'نمادهای رسمی و اعتماد',
        'footer_copyright' => 'تمامی حقوق محفوظ است.',
        'custom_social_networks' => $default_socials,
        'head_scripts' => '',
        'body_scripts' => '',
        'footer_scripts' => '',
        'trust_badges' => '<div style="display:flex;gap:10px;align-items:center;"><span class="material-symbols-outlined" style="font-size:32px;color:#22c55e;">verified</span><span>نماد اعتماد الکترونیکی</span></div>',
        'installed' => '1'
    ];
    foreach ($default_settings as $k => $v) {
        $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
        $stmt->execute([$k, $v]);
    }

    $stmt = $db->prepare("INSERT OR REPLACE INTO admin_users (id, username, password, email) VALUES (1, ?, ?, ?)");
    $stmt->execute(['admin', '12345', 'admin@ariastore.ir']);

    $categories = [
        ['digital', 'کالای دیجیتال', 0, 'https://images.unsplash.com/photo-1550009158-9ebf69173e03?w=300', 1],
        ['fashion', 'پوشاک و مد', 0, 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=300', 2],
        ['home', 'خانه و آشپزخانه', 0, 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=300', 3]
    ];
    $stmt = $db->prepare("INSERT OR REPLACE INTO categories (id, slug, name, parent_id, image, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($categories as $idx => $cat) {
        $stmt->execute([$idx + 1, $cat[0], $cat[1], $cat[2], $cat[3], $cat[4]]);
    }

    $mega_content = json_encode([
        'columns' => [
            [
                'title' => 'کالای دیجیتال',
                'links' => [
                    ['title' => 'گوشی‌های هوشمند', 'url' => '?route=shop&cat=digital'],
                    ['title' => 'لپ‌تاپ و اولترابوک', 'url' => '?route=shop&cat=digital']
                ]
            ],
            [
                'title' => 'پوشاک و مد',
                'links' => [
                    ['title' => 'کفش ورزشی برند', 'url' => '?route=shop&cat=fashion']
                ]
            ]
        ],
        'promo' => [
            'title' => 'جشنواره معرفی اپل',
            'image' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=400',
            'url' => '?route=shop&cat=digital'
        ]
    ], JSON_UNESCAPED_UNICODE);

    $menus = [
        [1, 'صفحه اصلی', '?', 0, '', '', 0, '', 1],
        [2, 'فروشگاه محصولات', '?route=shop', 0, 'ویژه', '#ef4444', 1, $mega_content, 2],
        [3, 'پیشنهاد شگفت‌انگیز', '?route=shop&special=1', 0, 'تخفیف', '#ec4899', 0, '', 3],
        [4, 'مقالات و راهنما', '?route=blog', 0, '', '', 0, '', 4]
    ];
    $stmt = $db->prepare("INSERT OR REPLACE INTO menus (id, title, link, parent_id, badge_text, badge_color, is_mega, mega_content, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($menus as $m) {
        $stmt->execute($m);
    }

    $vars_iphone = json_encode([
        [
            'group_name' => 'رنگ',
            'items' => [
                ['name' => 'تیتانیوم طبیعی', 'hex' => '#a19e99', 'image' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=800'],
                ['name' => 'تیتانیوم مشکی', 'hex' => '#38373b', 'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800']
            ]
        ],
        [
            'group_name' => 'حافظه داخلی',
            'items' => [
                ['name' => '256 گیگابایت', 'hex' => '', 'image' => ''],
                ['name' => '512 گیگابایت', 'hex' => '', 'image' => '']
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

    $products = [
        [
            1, 'iphone-15-pro-max', 'گوشی موبایل اپل iPhone 15 Pro Max ظرفیت 256 گیگابایت', 'digital',
            685000000, 720000000, 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=600',
            json_encode(['https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=800', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800'], JSON_UNESCAPED_UNICODE),
            '<h3>معرفی پرچمدار اپل</h3><p>گوشی موبایل iPhone 15 Pro Max با تراشه قدرتمند A17 Pro و بدنه تیتانیومی. انتخابی بی‌نظیر برای عکاسی حرفه‌ای و پردازش‌های سنگین.</p>',
            json_encode(['برند' => 'اپل', 'حافظه داخلی' => '256 گیگابایت', 'رم' => '8 گیگابایت'], JSON_UNESCAPED_UNICODE),
            $vars_iphone, 'https://example.com/buy/iphone-15-pro-max', 1, 1, 140
        ],
        [
            2, 'sony-wh-1000xm5', 'هدفون بی‌سیم سونی مدل WH-1000XM5', 'digital',
            185000000, 210000000, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600',
            json_encode(['https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800'], JSON_UNESCAPED_UNICODE),
            '<h3>سکوت مطلق و کیفیت صدای استودیویی</h3><p>هدفون پرچمدار سونی با پیشرفته‌ترین سیستم حذف نویز فعال (ANC) و کیفیت ساخت ارگونومیک برای استفاده طولانی مدت.</p>',
            json_encode(['برند' => 'سونی', 'عمر باتری' => '30 ساعت'], JSON_UNESCAPED_UNICODE),
            '', 'https://example.com/buy/sony-wh-1000xm5', 1, 0, 95
        ],
        [
            3, 'nike-air-max-2026', 'کفش ورزشی نایک مدل Air Max 2026', 'fashion',
            65000000, 78000000, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600',
            json_encode(['https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800'], JSON_UNESCAPED_UNICODE),
            '<h3>طراحی ارگونومیک با حداکثر تنفس‌پذیری</h3><p>کفش ورزشی نایک سری Air Max با کفی طبی پیشرفته، ضربه‌گیری عالی و وزن بسیار سبک برای پیاده‌روی‌های طولانی.</p>',
            json_encode(['برند' => 'نایک', 'کاربرد' => 'ورزشی و روزمره'], JSON_UNESCAPED_UNICODE),
            '', 'https://example.com/buy/nike-air-max', 1, 1, 78
        ],
        [
            4, 'espresso-coffee-maker', 'دستگاه اسپرسوساز اتوماتیک خانگی', 'home',
            45000000, 52000000, 'https://images.unsplash.com/photo-1517668808822-9a429a83a83f?w=600',
            json_encode(['https://images.unsplash.com/photo-1517668808822-9a429a83a83f?w=800'], JSON_UNESCAPED_UNICODE),
            '<h3>قهوه تازه‌دم در منزل</h3><p>اسپرسوساز تمام اتوماتیک با قابلیت تنظیم فشار و فوم‌ساز حرفه‌ای شیر، مناسب برای تهیه انواع قهوه و کاپوچینو.</p>',
            json_encode(['توان' => '1450 وات', 'فشار بخار' => '15 بار'], JSON_UNESCAPED_UNICODE),
            '', 'https://example.com/buy/coffee-maker', 1, 0, 60
        ]
    ];
    $stmt = $db->prepare("INSERT OR REPLACE INTO products (id, slug, title, category_slug, price, old_price, image, gallery, description, specs, variables, external_link, is_featured, is_special, views) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($products as $p) {
        $stmt->execute($p);
    }

    $sliders = [
        [1, 'جشنواره معرفی محصولات', 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200', '?route=shop', 1],
        [2, 'خرید مطمئن کالا', 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200', '?route=shop', 2]
    ];
    $stmt = $db->prepare("INSERT OR REPLACE INTO sliders (id, title, image, link, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($sliders as $s) {
        $stmt->execute($s);
    }

    $banners = [
        [1, 'تخفیف لوازم جانبی موبایل', 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=500', '?route=shop&cat=digital', 'hero_side', 1],
        [2, 'گجت‌های هوشمند خانگی', 'https://images.unsplash.com/photo-1558002038-1055907df827?w=500', '?route=shop&cat=home', 'hero_side', 2],
        [3, 'جشنواره پوشاک و مد فصل', 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=800', '?route=shop&cat=fashion', 'home_middle', 1],
        [4, 'تجهیزات و لوازم خانگی مدرن', 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=800', '?route=shop&cat=home', 'home_middle', 2]
    ];
    $stmt = $db->prepare("INSERT OR REPLACE INTO banners (id, title, image, link, position, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($banners as $b) {
        $stmt->execute($b);
    }

    $posts = [
        [1, 'best-smartphones-guide', 'راهنمای انتخاب بهترین گوشی‌های سال', 'مدیر', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800', 'در این مقاله به بررسی و مقایسه پرچمداران بازار می‌پردازیم...', '<p>انتخاب یک گوشی مناسب به نیاز شما به دوربین، باتری و پردازنده بستگی دارد. در این راهنما بهترین گزینه‌های موجود را معرفی کرده‌ایم.</p>', 45],
        [2, 'extend-laptop-battery-life', 'چگونه عمر باتری لپ‌تاپ خود را ۲ برابر کنیم؟', 'تیم فنی', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800', 'نکات کلیدی و کاربردی برای افزایش دوام و عمر مفید باتری در کاربری روزمره...', '<p>یکی از مهم‌ترین عوامل در افزایش طول عمر لپ‌تاپ‌ها، نگهداری صحیح از باتری است.</p>', 62]
    ];
    $stmt = $db->prepare("INSERT OR REPLACE INTO posts (id, slug, title, author, image, excerpt, content, views) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($posts as $po) {
        $stmt->execute($po);
    }

    $stmt = $db->prepare("INSERT OR REPLACE INTO pages (id, slug, title, content, is_published) VALUES (1, 'about', 'درباره اریا استور', '<p>اریا استور یک مرجع تخصصی برای بررسی، مقایسه و معرفی محصولات است. ما شما را مستقیماً به معتبرترین صفحات خرید کالا هدایت می‌کنیم.</p>', 1)");
    $stmt->execute();
}

function check_and_handle_setup($db) {
    init_database_schema($db);
    if (!is_site_installed($db)) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_action'])) {
            $site_title = trim($_POST['site_title'] ?? 'اریا استور');
            $site_subtitle = trim($_POST['site_subtitle'] ?? '');
            $admin_user = trim($_POST['admin_user'] ?? 'admin');
            $admin_pass = trim($_POST['admin_pass'] ?? '12345');
            $admin_email = trim($_POST['admin_email'] ?? 'admin@ariastore.ir');

            seed_placeholder_data($db);

            $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('site_title', ?), ('site_subtitle', ?)");
            $stmt->execute([$site_title, $site_subtitle]);

            if ($admin_user && $admin_pass) {
                $db->prepare("INSERT OR REPLACE INTO admin_users (id, username, password, email) VALUES (1, ?, ?, ?)")->execute([$admin_user, $admin_pass, $admin_email]);
            }

            header("Location: admin.php?msg=" . urlencode("راه‌اندازی اریا استور با موفقیت انجام شد."));
            exit;
        }
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>راه‌اندازی اریا استور</title>
            <style>
                @font-face { font-family: 'Peyda'; src: url('peydaregular.woff2'); font-weight: 400; }
                @font-face { font-family: 'Peyda'; src: url('peydaextrabold.woff2'); font-weight: 800; }
                * { box-sizing: border-box; font-family: 'Peyda', Tahoma, sans-serif; }
                body { background: #0f172a; color: #f8fafc; margin: 0; padding: 40px 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
                .setup-card { background: #1e293b; border: 1px solid #334155; border-radius: 20px; max-width: 480px; width: 100%; padding: 36px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
                .setup-card h1 { font-size: 24px; margin: 0 0 8px 0; color: #818cf8; font-weight: 800; text-align: center; }
                .setup-card p { color: #94a3b8; font-size: 14px; text-align: center; margin: 0 0 28px 0; }
                .form-group { margin-bottom: 20px; }
                .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #e2e8f0; }
                .form-control { width: 100%; padding: 12px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #fff; font-size: 14px; outline: none; transition: 0.3s; }
                .form-control:focus { border-color: #6366f1; }
                .btn-submit { width: 100%; padding: 14px; background: #4f46e5; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.2s; margin-top: 10px; }
                .btn-submit:hover { background: #4338ca; }
            </style>
        </head>
        <body>
            <div class="setup-card">
                <h1>راه‌اندازی اریا استور</h1>
                <p>تنظیمات اولیه وب‌سایت و حساب کاربری مدیر را وارد کنید.</p>
                <form method="POST">
                    <input type="hidden" name="setup_action" value="1">
                    <div class="form-group">
                        <label>نام وب‌سایت</label>
                        <input type="text" name="site_title" class="form-control" value="اریا استور" required>
                    </div>
                    <div class="form-group">
                        <label>شعار یا توضیح کوتاه</label>
                        <input type="text" name="site_subtitle" class="form-control" value="معرفی و راهنمای خرید بهترین محصولات">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                        <div class="form-group">
                            <label>نام کاربری مدیر</label>
                            <input type="text" name="admin_user" class="form-control" value="admin" required>
                        </div>
                        <div class="form-group">
                            <label>رمز عبور مدیر</label>
                            <input type="text" name="admin_pass" class="form-control" value="12345" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>ایمیل مدیر</label>
                        <input type="email" name="admin_email" class="form-control" value="admin@ariastore.ir" required>
                    </div>
                    <button type="submit" class="btn-submit">ذخیره و ورود به مدیریت</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

function get_all_settings($db) {
    $settings = [];
    try {
        $stmt = $db->query("SELECT key, value FROM settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['key']] = $row['value'];
        }
    } catch (Exception $e) {}

    $default_socials = json_encode([
        [
            'name' => 'اینستاگرام',
            'url' => 'https://instagram.com',
            'icon_png' => 'https://cdn-icons-png.flaticon.com/512/174/174855.png'
        ],
        [
            'name' => 'تلگرام',
            'url' => 'https://t.me',
            'icon_png' => 'https://cdn-icons-png.flaticon.com/512/2111/2111646.png'
        ],
        [
            'name' => 'واتساپ',
            'url' => 'https://whatsapp.com',
            'icon_png' => 'https://cdn-icons-png.flaticon.com/512/733/733585.png'
        ]
    ], JSON_UNESCAPED_UNICODE);

    return array_merge([
        'site_title' => 'اریا استور',
        'site_subtitle' => 'معرفی، بررسی و راهنمای خرید بهترین محصولات',
        'logo_url' => '',
        'primary_color' => '#4f46e5',
        'secondary_color' => '#ec4899',
        'top_bar_text' => 'جشنواره معرفی محصولات اریا استور با اتصال به بهترین فروشندگان',
        'top_bar_phone' => '۰۲۱-۱۲۳۴۵۶۷۸',
        'show_hero_slider' => '1',
        'show_categories' => '1',
        'categories_title' => 'دسته‌بندی‌های محبوب محصولات',
        'show_middle_banners' => '1',
        'show_special_offers' => '1',
        'special_offers_title' => 'پیشنهاد شگفت‌انگیز',
        'show_latest_products' => '1',
        'latest_products_title' => 'جدیدترین محصولات',
        'show_blog_slider' => '1',
        'blog_slider_title' => 'آخرین مقالات و راهنمای خرید',
        'footer_col1_title' => 'معرفی اریا استور',
        'footer_about' => 'اریا استور مرجعی تخصصی برای بررسی، مقایسه و معرفی محصولات است. ما شما را مستقیماً به معتبرترین صفحات خرید کالا هدایت می‌کنیم.',
        'footer_col2_title' => 'دسترسی سریع',
        'footer_col2_links' => "صفحه اصلی|?\nهمه محصولات|?route=shop\nلیست علاقه‌مندی‌ها|?route=wishlist\nمقالات و آموزش‌ها|?route=blog",
        'footer_col3_title' => 'راهنمای کاربران',
        'footer_col3_links' => "درباره ما|?page=about\nتماس با ما|?page=contact\nرویه بررسی کالا|#\nحریم خصوصی کاربران|#",
        'footer_col4_title' => 'نمادهای رسمی و اعتماد',
        'footer_copyright' => 'تمامی حقوق محفوظ است.',
        'custom_social_networks' => $default_socials,
        'head_scripts' => '',
        'body_scripts' => '',
        'footer_scripts' => '',
        'trust_badges' => '<div style="display:flex;gap:10px;align-items:center;"><span class="material-symbols-outlined" style="font-size:32px;color:#22c55e;">verified</span><span>نماد اعتماد الکترونیکی</span></div>',
        'installed' => '1'
    ], $settings);
}

function normalize_str($str) {
    return strtolower(str_replace([' ', '‌', 'ي'], ['', '', 'ی'], $str));
}
function fa_price($num) {
    return number_format($num) . ' ریال';
}
function readingTime($text) {
    return ceil(count(preg_split('/\s+/', trim(strip_tags($text)))) / 200) . ' دقیقه';
}
function get_like_count($db, $slug, $type) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM likes WHERE item_slug=? AND item_type=?");
    $stmt->execute([$slug, $type]);
    return $stmt->fetchColumn();
}
function has_user_liked($db, $slug, $type, $hash) {
    $stmt = $db->prepare("SELECT id FROM likes WHERE item_slug=? AND item_type=? AND user_hash=?");
    $stmt->execute([$slug, $type, $hash]);
    return $stmt->fetch() ? true : false;
}

// -------------------------------------------------------------------------
// 2. اتصال و بارگذاری تنظیمات
// -------------------------------------------------------------------------
$db = get_db();
check_and_handle_setup($db);
$settings = get_all_settings($db);

$primary_color = $settings['primary_color'] ?? '#4f46e5';
$primary_light_color = $primary_color . '1A';
$secondary_color = $settings['secondary_color'] ?? '#ec4899';

if (!isset($_SESSION['user_hash'])) {
    $_SESSION['user_hash'] = bin2hex(random_bytes(16));
}
$user_hash = $_SESSION['user_hash'];

// -------------------------------------------------------------------------
// 3. API و درخواست‌های AJAX (جستجو، لایک، علاقه‌مندی‌ها و نظرات)
// -------------------------------------------------------------------------
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_GET['api'] == 'search') {
        $q = normalize_str($_GET['q'] ?? '');
        $results = [];
        if ($q && strlen($q) >= 2) {
            $stmt = $db->query("SELECT * FROM products");
            while ($p = $stmt->fetch()) {
                if (strpos(normalize_str($p['title']), $q) !== false) {
                    $results[] = [
                        'title' => $p['title'],
                        'url' => '?product=' . $p['slug'],
                        'img' => $p['image'],
                        'price' => fa_price($p['price']),
                        'type' => 'محصول'
                    ];
                }
            }
            $stmt = $db->query("SELECT * FROM categories");
            while ($c = $stmt->fetch()) {
                if (strpos(normalize_str($c['name']), $q) !== false) {
                    $results[] = [
                        'title' => 'دسته: ' . $c['name'],
                        'url' => '?route=shop&cat=' . $c['slug'],
                        'img' => $c['image'],
                        'price' => '',
                        'type' => 'دسته‌بندی'
                    ];
                }
            }
        }
        echo json_encode($results, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_GET['api'] == 'like' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $slug = $_POST['slug'] ?? '';
        $type = $_POST['type'] ?? 'product';
        if ($slug) {
            if (has_user_liked($db, $slug, $type, $user_hash)) {
                $db->prepare("DELETE FROM likes WHERE item_slug=? AND item_type=? AND user_hash=?")->execute([$slug, $type, $user_hash]);
                $liked = false;
            } else {
                $db->prepare("INSERT INTO likes (item_slug, item_type, user_hash) VALUES (?, ?, ?)")->execute([$slug, $type, $user_hash]);
                $liked = true;
            }
            echo json_encode(['success' => true, 'liked' => $liked, 'count' => get_like_count($db, $slug, $type)]);
            exit;
        }
    }

    if ($_GET['api'] == 'comment' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $slug = $_POST['slug'] ?? '';
        $type = $_POST['type'] ?? 'product';
        $name = trim($_POST['name'] ?? 'کاربر مهمان');
        $comment = trim($_POST['comment'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);
        if ($slug && $comment) {
            $stmt = $db->prepare("INSERT INTO comments (item_slug, item_type, name, comment, rating, status) VALUES (?, ?, ?, ?, ?, 'approved')");
            $stmt->execute([$slug, $type, $name, $comment, $rating]);
            echo json_encode(['success' => true, 'msg' => 'دیدگاه شما با موفقیت ثبت شد.']);
            exit;
        }
    }

    if ($_GET['api'] == 'wishlist_products' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $slugs = $data['slugs'] ?? [];
        $res = [];
        if (!empty($slugs) && is_array($slugs)) {
            $in = str_repeat('?,', count($slugs) - 1) . '?';
            $stmt = $db->prepare("SELECT * FROM products WHERE slug IN ($in)");
            $stmt->execute($slugs);
            $rows = $stmt->fetchAll();
            foreach ($rows as $r) {
                $r['formatted_price'] = fa_price($r['price']);
                $r['formatted_old_price'] = $r['old_price'] > $r['price'] ? fa_price($r['old_price']) : '';
                $res[] = $r;
            }
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }

    exit;
}

// -------------------------------------------------------------------------
// 4. بارگذاری اطلاعات صفحه و نقشه نام دسته‌بندی‌ها
// -------------------------------------------------------------------------
$categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll();
$cat_map = [];
foreach ($categories as $c) {
    $cat_map[$c['slug']] = $c['name'];
}

$menus = $db->query("SELECT * FROM menus ORDER BY sort_order ASC, id ASC")->fetchAll();
$sliders = $db->query("SELECT * FROM sliders ORDER BY sort_order ASC, id ASC")->fetchAll();
$banners = $db->query("SELECT * FROM banners ORDER BY sort_order ASC, id ASC")->fetchAll();

$route = $_GET['route'] ?? 'home';
$page_title = $settings['site_title'];
$current_item = null;

if (isset($_GET['product'])) {
    $route = 'single_product';
    $stmt = $db->prepare("SELECT * FROM products WHERE slug = ?");
    $stmt->execute([$_GET['product']]);
    $current_item = $stmt->fetch();
    if ($current_item) {
        $page_title = $current_item['title'] . ' | ' . $settings['site_title'];
        $db->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$current_item['id']]);
    } else {
        $route = '404';
    }
} elseif (isset($_GET['post'])) {
    $route = 'single_post';
    $stmt = $db->prepare("SELECT * FROM posts WHERE slug = ?");
    $stmt->execute([$_GET['post']]);
    $current_item = $stmt->fetch();
    if ($current_item) {
        $page_title = $current_item['title'] . ' | ' . $settings['site_title'];
        $db->prepare("UPDATE posts SET views = views + 1 WHERE id = ?")->execute([$current_item['id']]);
    } else {
        $route = '404';
    }
} elseif (isset($_GET['cat'])) {
    $route = 'shop';
    foreach ($categories as $c) {
        if ($c['slug'] == $_GET['cat']) {
            $page_title = 'دسته ' . $c['name'] . ' | ' . $settings['site_title'];
        }
    }
} elseif ($route == 'shop') {
    $page_title = 'فروشگاه محصولات | ' . $settings['site_title'];
} elseif ($route == 'blog') {
    $page_title = 'مقالات و آموزش‌ها | ' . $settings['site_title'];
} elseif ($route == 'wishlist') {
    $page_title = 'لیست علاقه‌مندی‌ها | ' . $settings['site_title'];
} elseif (isset($_GET['page'])) {
    $route = 'page';
    $stmt = $db->prepare("SELECT * FROM pages WHERE slug = ? AND is_published = 1");
    $stmt->execute([$_GET['page']]);
    $current_item = $stmt->fetch();
    if ($current_item) {
        $page_title = $current_item['title'] . ' | ' . $settings['site_title'];
    } else {
        $route = '404';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <!-- لود اولویت‌دار آیکون‌های گوگل فونت (Anti-FOUC) -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"></noscript>
    <style>
        @font-face { font-family: 'Peyda'; src: url('peydaregular.woff2'); font-weight: 400; }
        @font-face { font-family: 'Peyda'; src: url('peydaextrabold.woff2'); font-weight: 800; }

        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined' !important;
            font-weight: normal !important;
            font-style: normal !important;
            font-size: 22px;
            line-height: 1;
            display: inline-block;
            vertical-align: middle;
            user-select: none;
            direction: ltr;
            font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
            color: transparent;
            transition: color 0.15s ease;
        }
        html.icons-loaded .material-symbols-outlined {
            color: inherit;
        }

        :root {
            --primary: <?= $primary_color ?>;
            --primary-light: <?= $primary_light_color ?>;
            --secondary: <?= $secondary_color ?>;
            --bg: #ffffff;
            --bg-secondary: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --card-bg: #ffffff;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.03);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.06);
            --radius: 16px;
        }

        [data-theme="dark"] {
            --bg: #0f172a;
            --bg-secondary: #1e293b;
            --text: #f8fafc;
            --muted: #94a3b8;
            --border: #334155;
            --card-bg: #1e293b;
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.4);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Peyda', Tahoma, sans-serif; }
        body { background: var(--bg-secondary); color: var(--text); line-height: 1.7; transition: 0.3s; }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; height: auto; display: block; }
        .container { max-width: 1240px; margin: 0 auto; padding: 0 20px; }

        .top-bar {
            background: #0f172a;
            color: #e2e8f0;
            font-size: 13px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .top-bar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .main-header {
            background: var(--bg);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 76px;
            gap: 24px;
        }
        .logo-box { display: flex; align-items: center; gap: 12px; }
        .logo-box h1 { font-size: 24px; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
        .logo-box span { font-size: 12px; color: var(--muted); display: block; }
        .logo-img { max-height: 48px; width: auto; object-fit: contain; }

        .search-container { flex: 1; max-width: 480px; position: relative; }
        .search-box {
            display: flex;
            align-items: center;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 99px;
            overflow: hidden;
            height: 44px;
            transition: 0.3s;
        }
        .search-box:focus-within { border-color: var(--primary); }
        .search-cat-select {
            background: transparent;
            border: none;
            border-left: 1px solid var(--border);
            padding: 0 14px;
            height: 100%;
            color: var(--text);
            font-size: 13px;
            outline: none;
            cursor: pointer;
        }
        .search-box input {
            flex: 1;
            height: 100%;
            border: none;
            background: transparent;
            padding: 0 18px;
            color: var(--text);
            font-size: 14px;
            outline: none;
        }
        .search-btn {
            background: transparent;
            color: var(--muted);
            border: none;
            width: 44px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .search-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow-md);
            max-height: 380px;
            overflow-y: auto;
            z-index: 1001;
            display: none;
        }
        .search-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            transition: 0.2s;
        }
        .search-item:hover { background: var(--primary-light); }
        .search-item img { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; }

        .header-actions { display: flex; align-items: center; gap: 10px; }
        .action-btn {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: 0.2s;
        }
        .action-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
        .badge-count {
            position: absolute;
            top: -5px;
            left: -5px;
            background: var(--secondary);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* نوار منو به همراه مگامنو بدون باگ و پرش */
        .nav-bar {
            background: var(--bg);
            border-bottom: 1px solid var(--border);
            position: relative;
        }
        .nav-inner {
            display: flex;
            align-items: center;
            gap: 12px;
            height: 54px;
            flex-wrap: wrap;
        }
        .menu-item { position: relative; display: inline-flex; height: 100%; align-items: center; }
        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            border-radius: 10px;
            white-space: nowrap;
            transition: 0.2s;
        }
        .nav-link:hover { color: var(--primary); background: var(--primary-light); }
        .menu-badge {
            font-size: 11px;
            font-weight: 800;
            color: #fff;
            padding: 2px 8px;
            border-radius: 99px;
            margin-right: 4px;
        }

        .mega-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 660px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 0 0 16px 16px;
            box-shadow: var(--shadow-md);
            padding: 24px;
            display: none;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            z-index: 1005;
        }
        .mega-dropdown::before {
            content: '';
            position: absolute;
            top: -14px;
            left: 0;
            right: 0;
            height: 14px;
        }
        .menu-item:hover .mega-dropdown,
        .mega-dropdown.active {
            display: grid !important;
        }
        .mega-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .mega-col h4 { font-size: 14px; font-weight: 800; color: var(--primary); margin-bottom: 12px; border-bottom: 2px solid var(--primary-light); padding-bottom: 6px; }
        .mega-col ul { list-style: none; }
        .mega-col ul li a { display: block; padding: 6px 0; color: var(--text); font-size: 13.5px; transition: 0.2s; }
        .mega-col ul li a:hover { color: var(--primary); transform: translateX(-4px); }
        .mega-promo { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; padding: 14px; text-align: center; }
        .mega-promo img { border-radius: 8px; margin-bottom: 10px; max-height: 120px; width: 100%; object-fit: cover; }
        .mega-promo h5 { font-size: 14px; font-weight: 800; margin-bottom: 6px; }

        /* منوی کشویی موبایل و تبلت با پشتیبانی آکاردئونی برای مگامنو */
        .mobile-nav-btn { display: none; }
        .mobile-drawer {
            position: fixed;
            top: 0;
            right: -320px;
            width: 300px;
            height: 100vh;
            background: var(--bg);
            z-index: 2000;
            transition: 0.3s ease;
            box-shadow: -10px 0 30px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }
        .mobile-drawer.active { right: 0; }
        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1999;
            display: none;
        }
        .mobile-overlay.active { display: block; }
        .mobile-acc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            font-weight: 700;
            cursor: pointer;
            color: var(--text);
        }
        .mobile-acc-body {
            display: none;
            padding: 8px 14px;
            background: var(--bg-secondary);
            border-radius: 10px;
            margin: 6px 0;
        }
        .mobile-acc-body.active { display: block; }

        .hero-slider-wrap { margin: 28px 0; }
        .hero-slider {
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            background: var(--card-bg);
            border: 1px solid var(--border);
            height: 380px;
        }
        .slides-track { display: flex; height: 100%; transition: transform 0.6s ease; }
        .slide-item { min-width: 100%; height: 100%; position: relative; }
        .slide-item img { width: 100%; height: 100%; object-fit: cover; }

        .slider-bottom-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            padding: 0 10px;
        }
        .slider-dots { display: flex; gap: 8px; }
        .slider-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--border);
            cursor: pointer;
            transition: 0.3s;
        }
        .slider-dot.active { background: var(--primary); width: 28px; border-radius: 6px; }
        .slider-arrows { display: flex; gap: 8px; }
        .slider-arrow-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
        }
        .slider-arrow-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 18px;
            margin-bottom: 36px;
        }
        .category-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: 0.3s;
        }
        .category-card:hover { transform: translateY(-4px); border-color: var(--primary); box-shadow: var(--shadow-sm); }
        .cat-img-box {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--bg-secondary);
            border: 2px solid var(--border);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cat-img-box img { width: 100%; height: 100%; object-fit: cover; }
        .category-card h3 { font-size: 15px; font-weight: 800; color: var(--text); margin-bottom: 4px; }

        .middle-banners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin: 36px 0;
        }
        .promo-banner-card {
            border-radius: 18px;
            overflow: hidden;
            position: relative;
            height: 200px;
            display: block;
            border: 1px solid var(--border);
        }
        .promo-banner-card img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .promo-banner-card:hover img { transform: scale(1.06); }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 36px 0 20px 0;
        }
        .section-header h2 { font-size: 22px; font-weight: 800; }
        .section-header a { font-size: 14px; font-weight: 700; color: var(--primary); }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .product-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: 0.3s;
        }
        .product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary); }
        .card-img-wrap {
            position: relative;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            background: var(--bg-secondary);
        }
        .card-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .product-card:hover .card-img-wrap img { transform: scale(1.06); }

        .discount-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #ef4444;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            padding: 2px 10px;
            border-radius: 8px;
            z-index: 2;
        }
        .wishlist-btn-card {
            position: absolute;
            top: 12px;
            left: 12px;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            transition: 0.2s;
        }
        .wishlist-btn-card:hover, .wishlist-btn-card.active { background: var(--primary); color: #fff; border-color: var(--primary); }

        .card-body { padding: 16px; display: flex; flex-direction: column; flex: 1; justify-content: space-between; }
        .card-cat { font-size: 12px; color: var(--muted); margin-bottom: 6px; font-weight: 600; }
        .card-title { font-size: 14.5px; font-weight: 700; color: var(--text); line-height: 1.5; margin-bottom: 14px; height: 44px; overflow: hidden; }
        .card-footer { display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border); padding-top: 14px; margin-top: auto; }
        .old-price { font-size: 12.5px; color: var(--muted); text-decoration: line-through; }
        .new-price { font-size: 16px; font-weight: 800; color: var(--primary); }

        .btn-buy {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-buy:hover { background: #4338ca; }

        .blog-carousel-wrap {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 16px;
            scroll-snap-type: x mandatory;
        }
        .blog-slide-card {
            min-width: 320px;
            flex: 0 0 320px;
            scroll-snap-align: start;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: 0.3s;
        }
        .blog-slide-card:hover { transform: translateY(-4px); border-color: var(--primary); }
        .blog-slide-img { aspect-ratio: 16 / 10; overflow: hidden; }
        .blog-slide-img img { width: 100%; height: 100%; object-fit: cover; }
        .blog-slide-body { padding: 18px; display: flex; flex-direction: column; flex: 1; }

        .single-layout {
            display: grid;
            grid-template-columns: 1fr 1.1fr;
            gap: 36px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            margin: 32px 0;
        }
        .gallery-main {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border);
            aspect-ratio: 1 / 1;
            background: var(--bg-secondary);
        }
        .gallery-main img { width: 100%; height: 100%; object-fit: cover; transition: 0.3s; }
        .gallery-thumbs { display: flex; gap: 10px; margin-top: 12px; }
        .thumb-item {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            border: 1px solid var(--border);
            overflow: hidden;
            cursor: pointer;
        }
        .thumb-item.active { border-color: var(--primary); }
        .thumb-item img { width: 100%; height: 100%; object-fit: cover; }

        .variant-group { margin: 18px 0; }
        .variant-group-title { font-size: 14px; font-weight: 700; margin-bottom: 8px; color: var(--text); }
        .variant-swatches { display: flex; flex-wrap: wrap; gap: 10px; }
        .variant-swatch {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg);
            color: var(--text);
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }
        .variant-swatch.active { border-color: var(--primary); background: var(--primary-light); color: var(--primary); }
        .swatch-color-dot { width: 16px; height: 16px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.2); }

        .prod-info h1 { font-size: 24px; font-weight: 800; margin-bottom: 12px; }
        .price-box {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }
        .btn-buy-lg {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: #fff;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 800;
            transition: 0.2s;
            margin-bottom: 14px;
        }
        .btn-buy-lg:hover { background: #4338ca; }

        .specs-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .specs-table th, .specs-table td { padding: 12px 16px; border: 1px solid var(--border); font-size: 14px; }
        .specs-table th { background: var(--bg-secondary); width: 35%; text-align: right; font-weight: 700; }

        .comments-section {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            margin: 24px 0;
        }
        .comment-box {
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }
        .comment-header { display: flex; align-items: center; justify-content: space-between; font-weight: 700; margin-bottom: 6px; }
        .reply-box {
            background: var(--bg-secondary);
            padding: 12px 16px;
            border-radius: 10px;
            border-right: 3px solid var(--primary);
            margin-top: 10px;
            font-size: 14px;
        }

        /* فوتر ۴ ستونه با لینک‌ها و شبکه‌های اجتماعی دلخواه (همراه با آیکون PNG) */
        .site-footer {
            background: var(--bg);
            border-top: 1px solid var(--border);
            margin-top: 60px;
            padding: 46px 0 24px 0;
            font-size: 14px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.8fr 1fr 1fr 1.2fr;
            gap: 32px;
            margin-bottom: 30px;
        }
        .footer-col h3 { font-size: 16px; font-weight: 800; margin-bottom: 16px; color: var(--text); }
        .footer-col p { color: var(--muted); line-height: 1.8; }
        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a:hover { color: var(--primary); }

        .social-links-wrap { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
        .social-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            transition: 0.2s;
            overflow: hidden;
        }
        .social-icon-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
        .social-png-img { width: 22px; height: 22px; object-fit: contain; }

        .footer-bottom {
            border-top: 1px solid var(--border);
            padding-top: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--muted);
            font-size: 13px;
            flex-wrap: wrap;
            gap: 12px;
        }

        #toast {
            position: fixed;
            bottom: 24px;
            left: 24px;
            background: #0f172a;
            color: #fff;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            z-index: 10000;
            display: none;
            box-shadow: var(--shadow-md);
        }

        @media (max-width: 992px) {
            .single-layout { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .middle-banners-grid { grid-template-columns: 1fr; }
            .nav-inner { display: none !important; }
            .mobile-nav-btn { display: inline-flex !important; }
            .products-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .footer-grid { grid-template-columns: 1fr; }
            .header-inner { flex-wrap: wrap; height: auto; padding: 14px 0; }
            .search-container { order: 3; max-width: 100%; width: 100%; margin-top: 10px; }
            .products-grid { grid-template-columns: 1fr; }
        }
    </style>
    <!-- اسکریپت ضد پرش آیکون (Anti-FOUC) -->
    <script>
        if ('fonts' in document) {
            document.fonts.load('24px "Material Symbols Outlined"').then(function() {
                document.documentElement.classList.add('icons-loaded');
            });
        } else {
            document.documentElement.classList.add('icons-loaded');
        }
        setTimeout(function() { document.documentElement.classList.add('icons-loaded'); }, 900);
    </script>
    <?= $settings['head_scripts'] ?>
</head>
<body>
    <?= $settings['body_scripts'] ?>

    <!-- نوار بالایی هدر -->
    <?php if (!empty($settings['top_bar_text'])): ?>
    <div class="top-bar">
        <div class="container top-bar-inner">
            <div><?= htmlspecialchars($settings['top_bar_text']) ?></div>
            <?php if (!empty($settings['top_bar_phone'])): ?>
                <div><span class="material-symbols-outlined" style="font-size:16px;">call</span> <?= htmlspecialchars($settings['top_bar_phone']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- هدر سایت -->
    <header class="main-header">
        <div class="container header-inner">
            <a href="?" class="logo-box">
                <?php if (!empty($settings['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="<?= htmlspecialchars($settings['site_title']) ?>" class="logo-img">
                <?php else: ?>
                    <span class="material-symbols-outlined" style="font-size: 32px; color: var(--primary);">shopping_bag</span>
                <?php endif; ?>
                <div>
                    <h1><?= htmlspecialchars($settings['site_title']) ?></h1>
                    <?php if ($settings['site_subtitle']): ?><span><?= htmlspecialchars($settings['site_subtitle']) ?></span><?php endif; ?>
                </div>
            </a>

            <!-- جستجوی زنده -->
            <div class="search-container">
                <div class="search-box">
                    <select class="search-cat-select" onchange="if(this.value) window.location.href='?route=shop&cat='+encodeURIComponent(this.value); else window.location.href='?route=shop';">
                        <option value="">همه دسته‌ها</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['slug'] ?>" <?= (isset($_GET['cat']) && $_GET['cat'] == $c['slug']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" placeholder="جستجوی محصول، برند یا دسته‌بندی..." onkeyup="liveSearch(this.value)" autocomplete="off">
                    <button class="search-btn"><span class="material-symbols-outlined">search</span></button>
                </div>
                <div id="search-dropdown" class="search-dropdown"></div>
            </div>

            <!-- دکمه‌های کنشی و منوی موبایل -->
            <div class="header-actions">
                <button class="action-btn" onclick="toggleTheme()" title="تغییر حالت شب و روز">
                    <span class="material-symbols-outlined" id="theme-icon">light_mode</span>
                </button>
                <a href="?route=wishlist" class="action-btn" title="علاقه‌مندی‌ها">
                    <span class="material-symbols-outlined">bookmark</span>
                    <span class="badge-count" id="wishlist-badge">0</span>
                </a>
                <button class="action-btn mobile-nav-btn" onclick="toggleMobileDrawer()" title="منوی سایت">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>
        </div>
    </header>

    <!-- نوار ناوبری به همراه مگامنو بدون هیچ باگ و پرش -->
    <nav class="nav-bar">
        <div class="container nav-inner">
            <?php foreach ($menus as $m): ?>
                <div class="menu-item">
                    <a href="<?= htmlspecialchars($m['link']) ?>" class="nav-link" <?= $m['is_mega'] == 1 ? 'onclick="toggleMegaDropdown(event, this)"' : '' ?>>
                        <span><?= htmlspecialchars($m['title']) ?></span>
                        <?php if ($m['badge_text']): ?>
                            <span class="menu-badge" style="background:<?= htmlspecialchars($m['badge_color'] ?: '#ef4444') ?>;"><?= htmlspecialchars($m['badge_text']) ?></span>
                        <?php endif; ?>
                        <?php if ($m['is_mega'] == 1): ?><span class="material-symbols-outlined" style="font-size:16px;">expand_more</span><?php endif; ?>
                    </a>

                    <?php if ($m['is_mega'] == 1 && !empty($m['mega_content'])): 
                        $mega = json_decode($m['mega_content'], true);
                        if ($mega && isset($mega['columns'])):
                    ?>
                        <div class="mega-dropdown">
                            <div class="mega-cols">
                                <?php foreach ($mega['columns'] as $col): ?>
                                    <div class="mega-col">
                                        <h4><?= htmlspecialchars($col['title']) ?></h4>
                                        <ul>
                                            <?php foreach ($col['links'] as $ln): ?>
                                                <li><a href="<?= htmlspecialchars($ln['url']) ?>"><?= htmlspecialchars($ln['title']) ?> ←</a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!empty($mega['promo'])): ?>
                                <div class="mega-promo">
                                    <?php if (!empty($mega['promo']['image'])): ?>
                                        <img src="<?= htmlspecialchars($mega['promo']['image']) ?>" alt="">
                                    <?php endif; ?>
                                    <h5><?= htmlspecialchars($mega['promo']['title']) ?></h5>
                                    <a href="<?= htmlspecialchars($mega['promo']['url'] ?? '?route=shop') ?>" class="btn-buy" style="margin-top:8px; display:inline-flex;">مشاهده پیشنهاد</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </nav>

    <!-- منوی کشویی موبایل و تبلت با پشتیبانی آکاردئونی برای مگامنو -->
    <div class="mobile-overlay" id="mobile-overlay" onclick="toggleMobileDrawer()"></div>
    <div class="mobile-drawer" id="mobile-drawer">
        <div style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-size: 16px; font-weight: 800;">منوی اریا استور</h3>
            <button onclick="toggleMobileDrawer()" style="background:none; border:none; cursor:pointer; color:var(--text);"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div style="padding: 16px; overflow-y: auto;">
            <?php foreach ($menus as $m): 
                $mega_data = json_decode($m['mega_content'] ?? '', true);
                $has_mega = ($m['is_mega'] == 1 && !empty($mega_data['columns']));
            ?>
                <?php if ($has_mega): ?>
                    <div class="mobile-acc-header" onclick="this.nextElementSibling.classList.toggle('active')">
                        <span><?= htmlspecialchars($m['title']) ?></span>
                        <span class="material-symbols-outlined" style="font-size:18px;">expand_more</span>
                    </div>
                    <div class="mobile-acc-body">
                        <?php foreach ($mega_data['columns'] as $col): ?>
                            <div style="font-size:13px; font-weight:800; color:var(--primary); margin:8px 0 4px 0;"><?= htmlspecialchars($col['title']) ?></div>
                            <?php foreach ($col['links'] as $ln): ?>
                                <a href="<?= htmlspecialchars($ln['url']) ?>" style="display:block; padding:6px 0; font-size:13px; color:var(--text);"><?= htmlspecialchars($ln['title']) ?> ←</a>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($m['link']) ?>" style="display:block; padding: 12px 0; border-bottom: 1px solid var(--border); font-weight: 700;"><?= htmlspecialchars($m['title']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- محتوای اصلی بر اساس مسیر -->
    <main class="container">
        <?php if ($route == 'home'): ?>
            <!-- 1. اسلایدر (بدون هیچ‌گونه متن یا اورلی روی تصویر) -->
            <?php if (($settings['show_hero_slider'] ?? '1') === '1' && !empty($sliders)): ?>
                <div class="hero-slider-wrap">
                    <section class="hero-slider">
                        <div class="slides-track" id="slides-track">
                            <?php foreach ($sliders as $slide): ?>
                                <div class="slide-item">
                                    <a href="<?= htmlspecialchars($slide['link']) ?>" style="display:block; width:100%; height:100%;">
                                        <img src="<?= htmlspecialchars($slide['image']) ?>" alt="<?= htmlspecialchars($slide['title']) ?>">
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <!-- نویگیشن زیر اسلایدر -->
                    <?php if (count($sliders) > 1): ?>
                    <div class="slider-bottom-nav">
                        <div class="slider-dots">
                            <?php foreach ($sliders as $idx => $s): ?>
                                <div class="slider-dot <?= $idx === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $idx ?>)"></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="slider-arrows">
                            <button class="slider-arrow-btn" onclick="prevSlide()" title="اسلاید قبلی"><span class="material-symbols-outlined">chevron_right</span></button>
                            <button class="slider-arrow-btn" onclick="nextSlide()" title="اسلاید بعدی"><span class="material-symbols-outlined">chevron_left</span></button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- 2. دسته‌بندی‌های محبوب همراه با تصویر -->
            <?php if (($settings['show_categories'] ?? '1') === '1' && !empty($categories)): ?>
                <div class="section-header">
                    <h2><?= htmlspecialchars($settings['categories_title'] ?? 'دسته‌بندی‌های محبوب') ?></h2>
                    <a href="?route=shop">همه دسته‌ها ←</a>
                </div>
                <div class="categories-grid">
                    <?php foreach ($categories as $c): ?>
                        <a href="?route=shop&cat=<?= $c['slug'] ?>" class="category-card">
                            <div class="cat-img-box">
                                <?php if (!empty($c['image'])): ?>
                                    <img src="<?= htmlspecialchars($c['image']) ?>" alt="">
                                <?php endif; ?>
                            </div>
                            <h3><?= htmlspecialchars($c['name']) ?></h3>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- 3. بنرهای تبلیغاتی وسط صفحه اصلی -->
            <?php
            $middle_banners = $db->query("SELECT * FROM banners WHERE position = 'home_middle' ORDER BY sort_order ASC, id ASC")->fetchAll();
            if (($settings['show_middle_banners'] ?? '1') === '1' && !empty($middle_banners)):
            ?>
                <div class="middle-banners-grid">
                    <?php foreach ($middle_banners as $mb): ?>
                        <a href="<?= htmlspecialchars($mb['link']) ?>" class="promo-banner-card">
                            <img src="<?= htmlspecialchars($mb['image']) ?>" alt="<?= htmlspecialchars($mb['title']) ?>">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- 4. پیشنهاد شگفت‌انگیز -->
            <?php
            $special = $db->query("SELECT * FROM products WHERE is_special = 1 ORDER BY id DESC")->fetchAll();
            if (($settings['show_special_offers'] ?? '1') === '1' && !empty($special)):
            ?>
                <div class="section-header">
                    <h2><?= htmlspecialchars($settings['special_offers_title'] ?? 'پیشنهاد شگفت‌انگیز') ?></h2>
                    <a href="?route=shop&special=1">مشاهده همه ←</a>
                </div>
                <div class="products-grid">
                    <?php foreach ($special as $p):
                        $disc = ($p['old_price'] > $p['price'] && $p['old_price'] > 0) ? round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) : 0;
                    ?>
                        <div class="product-card">
                            <div class="card-img-wrap">
                                <?php if ($disc > 0): ?><span class="discount-badge"><?= $disc ?>% تخفیف</span><?php endif; ?>
                                <button class="wishlist-btn-card" onclick="toggleWishlist('<?= $p['slug'] ?>', this); return false;" title="افزودن به علاقه‌مندی">
                                    <span class="material-symbols-outlined">bookmark</span>
                                </button>
                                <a href="?product=<?= $p['slug'] ?>"><img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>"></a>
                            </div>
                            <div class="card-body">
                                <div class="card-cat"><?= htmlspecialchars($cat_map[$p['category_slug']] ?? $p['category_slug']) ?></div>
                                <a href="?product=<?= $p['slug'] ?>" class="card-title"><?= htmlspecialchars($p['title']) ?></a>
                                <div class="card-footer">
                                    <div>
                                        <?php if ($p['old_price'] > $p['price']): ?><span class="old-price"><?= fa_price($p['old_price']) ?></span><?php endif; ?>
                                        <div class="new-price"><?= fa_price($p['price']) ?></div>
                                    </div>
                                    <a href="<?= !empty($p['external_link']) ? htmlspecialchars($p['external_link']) : '?product=' . $p['slug'] ?>" <?= !empty($p['external_link']) ? 'target="_blank" rel="nofollow"' : '' ?> class="btn-buy">
                                        <span class="material-symbols-outlined" style="font-size:16px;">open_in_new</span>
                                        <span>خرید محصول</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- 5. جدیدترین محصولات -->
            <?php
            $latest = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 8")->fetchAll();
            if (($settings['show_latest_products'] ?? '1') === '1'):
            ?>
                <div class="section-header">
                    <h2><?= htmlspecialchars($settings['latest_products_title'] ?? 'جدیدترین محصولات') ?></h2>
                    <a href="?route=shop">مشاهده همه ←</a>
                </div>
                <?php if (empty($latest)): ?>
                    <div style="text-align:center; padding:60px; background:var(--card-bg); border:1px solid var(--border); border-radius:16px;">
                        <span class="material-symbols-outlined" style="font-size:50px; color:var(--muted); margin-bottom:12px;">inventory_2</span>
                        <p style="color:var(--muted);">هنوز محصولی توسط مدیریت ثبت نشده است.</p>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($latest as $p):
                            $disc = ($p['old_price'] > $p['price'] && $p['old_price'] > 0) ? round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) : 0;
                        ?>
                            <div class="product-card">
                                <div class="card-img-wrap">
                                    <?php if ($disc > 0): ?><span class="discount-badge"><?= $disc ?>% تخفیف</span><?php endif; ?>
                                    <button class="wishlist-btn-card" onclick="toggleWishlist('<?= $p['slug'] ?>', this); return false;"><span class="material-symbols-outlined">bookmark</span></button>
                                    <a href="?product=<?= $p['slug'] ?>"><img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>"></a>
                                </div>
                                <div class="card-body">
                                    <div class="card-cat"><?= htmlspecialchars($cat_map[$p['category_slug']] ?? $p['category_slug']) ?></div>
                                    <a href="?product=<?= $p['slug'] ?>" class="card-title"><?= htmlspecialchars($p['title']) ?></a>
                                    <div class="card-footer">
                                        <div>
                                            <?php if ($p['old_price'] > $p['price']): ?><span class="old-price"><?= fa_price($p['old_price']) ?></span><?php endif; ?>
                                            <div class="new-price"><?= fa_price($p['price']) ?></div>
                                        </div>
                                        <a href="<?= !empty($p['external_link']) ? htmlspecialchars($p['external_link']) : '?product=' . $p['slug'] ?>" <?= !empty($p['external_link']) ? 'target="_blank" rel="nofollow"' : '' ?> class="btn-buy">
                                            <span class="material-symbols-outlined" style="font-size:16px;">open_in_new</span>
                                            <span>خرید محصول</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- 6. اسلایدر مقالات در صفحه اصلی (بدون نمایش بازدید اشتباه) -->
            <?php
            $home_posts = $db->query("SELECT * FROM posts ORDER BY id DESC LIMIT 6")->fetchAll();
            if (($settings['show_blog_slider'] ?? '1') === '1' && !empty($home_posts)):
            ?>
                <div class="section-header">
                    <h2><?= htmlspecialchars($settings['blog_slider_title'] ?? 'آخرین مقالات و راهنمای خرید') ?></h2>
                    <a href="?route=blog">مجله خبری ←</a>
                </div>
                <div class="blog-carousel-wrap">
                    <?php foreach ($home_posts as $po): ?>
                        <div class="blog-slide-card">
                            <a href="?post=<?= $po['slug'] ?>" class="blog-slide-img">
                                <img src="<?= htmlspecialchars($po['image']) ?>" alt="<?= htmlspecialchars($po['title']) ?>">
                            </a>
                            <div class="blog-slide-body">
                                <div style="font-size:12px;color:var(--muted);margin-bottom:6px;">
                                    <span>نویسنده: <?= htmlspecialchars($po['author']) ?></span> | <span><?= readingTime($po['content']) ?> مطالعه</span>
                                </div>
                                <a href="?post=<?= $po['slug'] ?>" style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:10px;display:block;"><?= htmlspecialchars($po['title']) ?></a>
                                <p style="font-size:13.5px;color:var(--muted);line-height:1.7;margin-bottom:14px;flex:1;"><?= htmlspecialchars($po['excerpt']) ?></p>
                                <a href="?post=<?= $po['slug'] ?>" style="font-size:13.5px;font-weight:700;color:var(--primary);">ادامه مطلب ←</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($route == 'shop'): ?>
            <!-- صفحه محصولات -->
            <div style="margin: 24px 0;">
                <h1 style="font-size:24px; font-weight:800; margin-bottom:20px;"><?= htmlspecialchars($page_title) ?></h1>
                <?php
                $sql = "SELECT * FROM products WHERE 1=1";
                $params = [];
                if (isset($_GET['cat']) && !empty($_GET['cat'])) {
                    $sql .= " AND category_slug = ?";
                    $params[] = $_GET['cat'];
                }
                $sql .= " ORDER BY id DESC";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $shop_products = $stmt->fetchAll();
                if (empty($shop_products)):
                ?>
                    <div style="text-align:center; padding: 60px; background:var(--card-bg); border:1px solid var(--border); border-radius:16px;">
                        <p style="color:var(--muted);">محصولی در این بخش وجود ندارد.</p>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($shop_products as $p):
                            $disc = ($p['old_price'] > $p['price'] && $p['old_price'] > 0) ? round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) : 0;
                        ?>
                            <div class="product-card">
                                <div class="card-img-wrap">
                                    <?php if ($disc > 0): ?><span class="discount-badge"><?= $disc ?>% تخفیف</span><?php endif; ?>
                                    <button class="wishlist-btn-card" onclick="toggleWishlist('<?= $p['slug'] ?>', this); return false;"><span class="material-symbols-outlined">bookmark</span></button>
                                    <a href="?product=<?= $p['slug'] ?>"><img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>"></a>
                                </div>
                                <div class="card-body">
                                    <div class="card-cat"><?= htmlspecialchars($cat_map[$p['category_slug']] ?? $p['category_slug']) ?></div>
                                    <a href="?product=<?= $p['slug'] ?>" class="card-title"><?= htmlspecialchars($p['title']) ?></a>
                                    <div class="card-footer">
                                        <div>
                                            <?php if ($p['old_price'] > $p['price']): ?><span class="old-price"><?= fa_price($p['old_price']) ?></span><?php endif; ?>
                                            <div class="new-price"><?= fa_price($p['price']) ?></div>
                                        </div>
                                        <a href="<?= !empty($p['external_link']) ? htmlspecialchars($p['external_link']) : '?product=' . $p['slug'] ?>" <?= !empty($p['external_link']) ? 'target="_blank" rel="nofollow"' : '' ?> class="btn-buy">
                                            <span class="material-symbols-outlined" style="font-size:16px;">open_in_new</span>
                                            <span>خرید محصول</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($route == 'single_product' && $current_item): 
            $p = $current_item;
            $gallery = json_decode($p['gallery'], true) ?: [];
            array_unshift($gallery, $p['image']);
            $specs = json_decode($p['specs'], true) ?: [];
            $disc = ($p['old_price'] > $p['price'] && $p['old_price'] > 0) ? round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) : 0;
            $variables = json_decode($p['variables'] ?? '', true) ?: [];
            $like_count = get_like_count($db, $p['slug'], 'product');
            $is_liked = has_user_liked($db, $p['slug'], 'product', $user_hash);
        ?>
            <!-- صفحه جزئیات محصول (بدون نمایش بازدید اشتباه) -->
            <div class="single-layout">
                <div>
                    <div class="gallery-main" id="gallery-main">
                        <img src="<?= htmlspecialchars($p['image']) ?>" id="main-img" alt="<?= htmlspecialchars($p['title']) ?>">
                    </div>
                    <?php if (count($gallery) > 1): ?>
                        <div class="gallery-thumbs">
                            <?php foreach ($gallery as $idx => $img_url): ?>
                                <div class="thumb-item <?= $idx === 0 ? 'active' : '' ?>" onclick="switchThumb(this, '<?= htmlspecialchars($img_url) ?>')">
                                    <img src="<?= htmlspecialchars($img_url) ?>" alt="">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="prod-info">
                    <h1><?= htmlspecialchars($p['title']) ?></h1>
                    <div style="color:var(--muted); font-size:14px; margin-bottom:16px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                        <span>دسته: <?= htmlspecialchars($cat_map[$p['category_slug']] ?? $p['category_slug']) ?></span>
                        <span>
                            <button onclick="toggleLike('<?= $p['slug'] ?>', 'product')" style="background:none;border:none;cursor:pointer;color:<?= $is_liked ? '#ef4444' : 'var(--muted)' ?>;font-size:14px;display:inline-flex;align-items:center;gap:4px;">
                                <span class="material-symbols-outlined" style="font-size:18px;">favorite</span>
                                <span id="like-count-num"><?= $like_count ?></span>
                            </button>
                        </span>
                    </div>

                    <div class="price-box">
                        <div>
                            <?php if ($p['old_price'] > $p['price']): ?>
                                <span class="old-price" style="display:block;"><?= fa_price($p['old_price']) ?></span>
                            <?php endif; ?>
                            <span class="new-price" style="font-size:24px;"><?= fa_price($p['price']) ?></span>
                        </div>
                        <?php if ($disc > 0): ?><span class="discount-badge" style="position:static;"><?= $disc ?>% تخفیف</span><?php endif; ?>
                    </div>

                    <!-- نمایش متغیرها -->
                    <?php if (!empty($variables)): ?>
                        <?php foreach ($variables as $g_idx => $group): ?>
                            <div class="variant-group">
                                <div class="variant-group-title"><?= htmlspecialchars($group['group_name']) ?>:</div>
                                <div class="variant-swatches">
                                    <?php foreach ($group['items'] as $item_idx => $item): ?>
                                        <div class="variant-swatch <?= ($g_idx === 0 && $item_idx === 0) ? 'active' : '' ?>" onclick="selectVariant(this, '<?= htmlspecialchars($item['image'] ?? '') ?>')">
                                            <?php if (!empty($item['hex'])): ?>
                                                <span class="swatch-color-dot" style="background:<?= htmlspecialchars($item['hex']) ?>;"></span>
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($item['name']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- دکمه خرید اصلی -->
                    <?php if (!empty($p['external_link'])): ?>
                        <a href="<?= htmlspecialchars($p['external_link']) ?>" target="_blank" rel="nofollow" class="btn-buy-lg">
                            <span class="material-symbols-outlined">open_in_new</span>
                            <span>مشاهده و خرید محصول در سایت فروشنده</span>
                        </a>
                    <?php else: ?>
                        <div style="padding:14px; background:var(--bg-secondary); border:1px solid var(--border); border-radius:10px; text-align:center; color:var(--muted); margin-bottom:14px;">
                            لینک خرید این محصول هنوز توسط مدیریت اضافه نشده است.
                        </div>
                    <?php endif; ?>

                    <button class="btn-buy" style="width:100%; justify-content:center; background:var(--bg-secondary); color:var(--text); border:1px solid var(--border); padding:12px;" onclick="toggleWishlist('<?= $p['slug'] ?>')">
                        <span class="material-symbols-outlined">bookmark</span>
                        <span>افزودن به لیست علاقه‌مندی‌ها</span>
                    </button>
                </div>
            </div>

            <!-- توضیحات و مشخصات فنی (پشتیبانی از HTML و IFrame) -->
            <div style="background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:28px; margin:24px 0;">
                <h3 style="font-size:18px; font-weight:800; margin-bottom:16px; color:var(--primary);">معرفی و بررسی محصول</h3>
                <div style="line-height:2; color:var(--text); font-size:15px; margin-bottom:28px;">
                    <?= $p['description'] ?>
                </div>

                <?php if (!empty($specs)): ?>
                    <h3 style="font-size:18px; font-weight:800; margin-bottom:14px; color:var(--primary);">مشخصات فنی</h3>
                    <table class="specs-table">
                        <tbody>
                            <?php foreach ($specs as $k => $v): ?>
                                <tr>
                                    <th><?= htmlspecialchars($k) ?></th>
                                    <td><?= htmlspecialchars($v) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- دیدگاه‌های کاربران -->
            <div class="comments-section">
                <h3 style="font-size:18px; font-weight:800; margin-bottom:20px;">دیدگاه کاربران درباره <?= htmlspecialchars($p['title']) ?></h3>

                <form onsubmit="submitCommentForm(event, '<?= $p['slug'] ?>', 'product')" style="margin-bottom:28px; background:var(--bg-secondary); padding:20px; border-radius:14px; border:1px solid var(--border);">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
                        <input type="text" id="comm-name" placeholder="نام شما" required style="padding:11px; border-radius:10px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
                        <select id="comm-rating" style="padding:11px; border-radius:10px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
                            <option value="5">⭐⭐⭐⭐⭐ (۵ از ۵ - عالی)</option>
                            <option value="4">⭐⭐⭐⭐ (۴ از ۵ - خوب)</option>
                            <option value="3">⭐⭐⭐ (۳ از ۵ - متوسط)</option>
                        </select>
                    </div>
                    <textarea id="comm-text" rows="3" placeholder="دیدگاه یا پرسش خود را درباره این محصول بنویسید..." required style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--border); background:var(--bg); color:var(--text); margin-bottom:16px;"></textarea>
                    <button type="submit" class="btn-buy" style="padding:10px 24px;">ثبت دیدگاه</button>
                </form>

                <?php
                $stmt = $db->prepare("SELECT * FROM comments WHERE item_slug=? AND item_type=? AND status='approved' ORDER BY id DESC");
                $stmt->execute([$p['slug'], 'product']);
                $comments = $stmt->fetchAll();
                if (empty($comments)):
                ?>
                    <p style="color:var(--muted); text-align:center; padding:16px;">هنوز دیدگاهی برای این محصول ثبت نشده است. اولین نفری باشید که نظر می‌دهد!</p>
                <?php else:
                    foreach ($comments as $cm):
                ?>
                    <div class="comment-box">
                        <div class="comment-header">
                            <span><?= htmlspecialchars($cm['name']) ?></span>
                            <span style="color:#f59e0b;"><?= str_repeat('★', $cm['rating']) ?></span>
                        </div>
                        <p style="line-height:1.8; color:var(--text);"><?= htmlspecialchars($cm['comment']) ?></p>
                        <?php if (!empty($cm['reply_text'])): ?>
                            <div class="reply-box">
                                <strong style="color:var(--primary); display:block; margin-bottom:4px;">پاسخ مدیریت اریا استور:</strong>
                                <?= htmlspecialchars($cm['reply_text']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            </div>

        <?php elseif ($route == 'wishlist'): ?>
            <!-- صفحه علاقه‌مندی‌ها -->
            <div style="margin: 24px 0;">
                <h1 style="font-size:24px; font-weight:800; margin-bottom:20px;">لیست علاقه‌مندی‌های شما</h1>
                <div id="wishlist-container">
                    <div style="text-align:center; padding:60px; color:var(--muted);">در حال بارگذاری لیست علاقه‌مندی‌ها...</div>
                </div>
            </div>

        <?php elseif ($route == 'blog'): ?>
            <!-- مقالات (بدون نمایش بازدید اشتباه) -->
            <div style="margin:24px 0;">
                <h1 style="font-size:24px; font-weight:800; margin-bottom:20px;"><?= htmlspecialchars($page_title) ?></h1>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:24px;">
                    <?php
                    $posts = $db->query("SELECT * FROM posts ORDER BY id DESC")->fetchAll();
                    if (empty($posts)):
                    ?>
                        <div style="grid-column:1/-1; text-align:center; padding:50px; background:var(--card-bg); border:1px solid var(--border); border-radius:16px;">
                            <p style="color:var(--muted);">مقاله‌ای یافت نشد.</p>
                        </div>
                    <?php else:
                        foreach ($posts as $po):
                    ?>
                        <div class="product-card">
                            <div class="card-img-wrap" style="aspect-ratio:16/10;">
                                <img src="<?= htmlspecialchars($po['image']) ?>" alt="<?= htmlspecialchars($po['title']) ?>">
                            </div>
                            <div class="card-body">
                                <a href="?post=<?= $po['slug'] ?>" class="card-title" style="height:auto; font-size:16px;"><?= htmlspecialchars($po['title']) ?></a>
                                <p style="font-size:14px; color:var(--muted); margin-bottom:16px;"><?= htmlspecialchars($po['excerpt']) ?></p>
                                <a href="?post=<?= $po['slug'] ?>" style="color:var(--primary); font-weight:700; font-size:14px;">ادامه مطلب ←</a>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        <?php elseif ($route == 'single_post' && $current_item): 
            $po = $current_item;
            $like_count = get_like_count($db, $po['slug'], 'post');
            $is_liked = has_user_liked($db, $po['slug'], 'post', $user_hash);
        ?>
            <!-- صفحه مقاله (بدون نمایش بازدید اشتباه) -->
            <article style="background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:32px; margin:24px 0;">
                <h1 style="font-size:26px; font-weight:800; margin-bottom:12px;"><?= htmlspecialchars($po['title']) ?></h1>
                <div style="color:var(--muted); font-size:14px; margin-bottom:20px; display:flex; align-items:center; gap:16px;">
                    <span>نویسنده: <?= htmlspecialchars($po['author']) ?></span> | <span><?= readingTime($po['content']) ?> مطالعه</span>
                    <span>
                        <button onclick="toggleLike('<?= $po['slug'] ?>', 'post')" style="background:none;border:none;cursor:pointer;color:<?= $is_liked ? '#ef4444' : 'var(--muted)' ?>;font-size:14px;display:inline-flex;align-items:center;gap:4px;">
                            <span class="material-symbols-outlined" style="font-size:18px;">favorite</span>
                            <span><?= $like_count ?></span>
                        </button>
                    </span>
                </div>
                <?php if ($po['image']): ?>
                    <img src="<?= htmlspecialchars($po['image']) ?>" alt="" style="width:100%; border-radius:14px; margin-bottom:24px; max-height:420px; object-fit:cover;">
                <?php endif; ?>
                <div style="line-height:2.2; font-size:15.5px;">
                    <?= $po['content'] ?>
                </div>
            </article>

            <!-- نظرات مقاله -->
            <div class="comments-section">
                <h3 style="font-size:18px; font-weight:800; margin-bottom:20px;">دیدگاه کاربران درباره این مقاله</h3>
                <form onsubmit="submitCommentForm(event, '<?= $po['slug'] ?>', 'post')" style="margin-bottom:28px; background:var(--bg-secondary); padding:20px; border-radius:14px; border:1px solid var(--border);">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
                        <input type="text" id="comm-name" placeholder="نام شما" required style="padding:11px; border-radius:10px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
                        <select id="comm-rating" style="padding:11px; border-radius:10px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
                            <option value="5">⭐⭐⭐⭐⭐ (۵ از ۵)</option>
                            <option value="4">⭐⭐⭐⭐ (۴ از ۵)</option>
                        </select>
                    </div>
                    <textarea id="comm-text" rows="3" placeholder="دیدگاه خود را بنویسید..." required style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--border); background:var(--bg); color:var(--text); margin-bottom:16px;"></textarea>
                    <button type="submit" class="btn-buy" style="padding:10px 24px;">ثبت دیدگاه</button>
                </form>
                <?php
                $stmt = $db->prepare("SELECT * FROM comments WHERE item_slug=? AND item_type=? AND status='approved' ORDER BY id DESC");
                $stmt->execute([$po['slug'], 'post']);
                $comments = $stmt->fetchAll();
                foreach ($comments as $cm):
                ?>
                    <div class="comment-box">
                        <div class="comment-header">
                            <span><?= htmlspecialchars($cm['name']) ?></span>
                            <span style="color:#f59e0b;"><?= str_repeat('★', $cm['rating']) ?></span>
                        </div>
                        <p style="line-height:1.8; color:var(--text);"><?= htmlspecialchars($cm['comment']) ?></p>
                        <?php if (!empty($cm['reply_text'])): ?>
                            <div class="reply-box">
                                <strong style="color:var(--primary); display:block; margin-bottom:4px;">پاسخ مدیریت اریا استور:</strong>
                                <?= htmlspecialchars($cm['reply_text']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($route == 'page' && $current_item): ?>
            <div style="background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:32px; margin:24px 0;">
                <h1 style="font-size:24px; font-weight:800; margin-bottom:20px;"><?= htmlspecialchars($current_item['title']) ?></h1>
                <div><?= $current_item['content'] ?></div>
            </div>

        <?php else: ?>
            <div style="text-align:center; padding:100px 20px;">
                <span class="material-symbols-outlined" style="font-size:60px; color:var(--primary); margin-bottom:16px;">error</span>
                <h1 style="font-size:36px; font-weight:800;">404</h1>
                <p style="color:var(--muted); margin-bottom:20px;">صفحه مورد نظر یافت نشد.</p>
                <a href="?" class="btn-buy" style="padding:10px 24px;">بازگشت به خانه</a>
            </div>
        <?php endif; ?>
    </main>

    <!-- فوتر ۴ ستونه با قابلیت اضافه کردن لینک‌های دلخواه و شبکه‌های اجتماعی دلخواه (همراه با آیکون PNG) -->
    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-col">
                <h3><?= htmlspecialchars($settings['footer_col1_title'] ?? 'معرفی اریا استور') ?></h3>
                <p><?= htmlspecialchars($settings['footer_about']) ?></p>
                <div class="social-links-wrap">
                    <?php
                    // نمایش شبکه‌های اجتماعی دلخواه ایجاد شده توسط مدیر (همراه با آیکون PNG)
                    $custom_socials = json_decode($settings['custom_social_networks'] ?? '[]', true);
                    if (!empty($custom_socials) && is_array($custom_socials)) {
                        foreach ($custom_socials as $soc):
                            if (!empty($soc['url'])):
                    ?>
                                <a href="<?= htmlspecialchars($soc['url']) ?>" target="_blank" rel="nofollow" class="social-icon-btn" title="<?= htmlspecialchars($soc['name'] ?? 'شبکه اجتماعی') ?>">
                                    <?php if (!empty($soc['icon_png'])): ?>
                                        <img src="<?= htmlspecialchars($soc['icon_png']) ?>" alt="" class="social-png-img">
                                    <?php else: ?>
                                        <span><?= htmlspecialchars(mb_substr($soc['name'] ?? 'S', 0, 3)) ?></span>
                                    <?php endif; ?>
                                </a>
                    <?php
                            endif;
                        endforeach;
                    }
                    ?>
                </div>
            </div>
            <div class="footer-col">
                <h3><?= htmlspecialchars($settings['footer_col2_title'] ?? 'دسترسی سریع') ?></h3>
                <ul class="footer-links">
                    <?php
                    $col2_lines = array_filter(array_map('trim', explode("\n", $settings['footer_col2_links'] ?? '')));
                    foreach ($col2_lines as $line):
                        $parts = explode('|', $line, 2);
                        if (count($parts) === 2):
                    ?>
                        <li><a href="<?= htmlspecialchars(trim($parts[1])) ?>"><?= htmlspecialchars(trim($parts[0])) ?></a></li>
                    <?php endif; endforeach; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h3><?= htmlspecialchars($settings['footer_col3_title'] ?? 'راهنمای کاربران') ?></h3>
                <ul class="footer-links">
                    <?php
                    $col3_lines = array_filter(array_map('trim', explode("\n", $settings['footer_col3_links'] ?? '')));
                    foreach ($col3_lines as $line):
                        $parts = explode('|', $line, 2);
                        if (count($parts) === 2):
                    ?>
                        <li><a href="<?= htmlspecialchars(trim($parts[1])) ?>"><?= htmlspecialchars(trim($parts[0])) ?></a></li>
                    <?php endif; endforeach; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h3><?= htmlspecialchars($settings['footer_col4_title'] ?? 'نمادهای رسمی و اعتماد') ?></h3>
                <div><?= $settings['trust_badges'] ?></div>
            </div>
        </div>

        <div class="container footer-bottom">
            <div>ساخته شده با اریا استور | Aria Store - توسعه‌دهنده: صدرا الفتی (Sadra Olfati)</div>
            <div><?= htmlspecialchars($settings['footer_copyright']) ?></div>
        </div>
    </footer>

    <div id="toast"></div>

    <script>
        // اسلایدر
        let slideIndex = 0;
        function goToSlide(idx) {
            const track = document.getElementById('slides-track');
            const dots = document.querySelectorAll('.slider-dot');
            if (!track) return;
            const count = track.children.length || 1;
            slideIndex = (idx + count) % count;
            track.style.transform = `translateX(${slideIndex * 100}%)`;
            dots.forEach((d, i) => d.classList.toggle('active', i === slideIndex));
        }
        function nextSlide() { goToSlide(slideIndex + 1); }
        function prevSlide() { goToSlide(slideIndex - 1); }
        setInterval(() => {
            const track = document.getElementById('slides-track');
            if (track && track.children.length > 1) nextSlide();
        }, 6000);

        // گالری و متغیرهای محصول
        function switchThumb(el, src) {
            document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
            if (el) el.classList.add('active');
            const main = document.getElementById('main-img');
            if (main && src) main.src = src;
        }
        function selectVariant(el, imgSrc) {
            const parent = el.parentElement;
            parent.querySelectorAll('.variant-swatch').forEach(s => s.classList.remove('active'));
            el.classList.add('active');
            if (imgSrc) {
                const main = document.getElementById('main-img');
                if (main) main.src = imgSrc;
            }
        }

        // تم شب و روز
        function toggleTheme() {
            const html = document.documentElement;
            const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('aria_theme', next);
            const icon = document.getElementById('theme-icon');
            if (icon) icon.textContent = next === 'dark' ? 'light_mode' : 'dark_mode';
        }
        const savedTheme = localStorage.getItem('aria_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);

        // جستجوی زنده
        let searchTimer = null;
        function liveSearch(q) {
            clearTimeout(searchTimer);
            const drop = document.getElementById('search-dropdown');
            if (!q || q.trim().length < 2) {
                drop.style.display = 'none';
                return;
            }
            searchTimer = setTimeout(() => {
                fetch('?api=search&q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(res => {
                        if (!res || res.length === 0) {
                            drop.innerHTML = '<div style="padding:14px;text-align:center;color:var(--muted);">نتیجه‌ای یافت نشد.</div>';
                        } else {
                            drop.innerHTML = res.map(i => `
                                <a href="${i.url}" class="search-item">
                                    ${i.img ? `<img src="${i.img}" alt="">` : '<span class="material-symbols-outlined" style="font-size:24px;margin-left:8px;">shopping_bag</span>'}
                                    <div style="flex:1;">
                                        <div style="font-weight:700;font-size:13.5px;">${i.title}</div>
                                        ${i.price ? `<div style="font-size:12px;color:var(--primary);font-weight:700;">${i.price}</div>` : ''}
                                    </div>
                                    <span style="font-size:11px;background:var(--primary-light);color:var(--primary);padding:2px 8px;border-radius:6px;">${i.type}</span>
                                </a>
                            `).join('');
                        }
                        drop.style.display = 'block';
                    });
            }, 250);
        }

        function toggleMegaDropdown(e, el) {
            e.preventDefault();
            e.stopPropagation();
            const drop = el.parentElement.querySelector('.mega-dropdown');
            if (drop) {
                document.querySelectorAll('.mega-dropdown.active').forEach(d => {
                    if (d !== drop) d.classList.remove('active');
                });
                drop.classList.toggle('active');
            }
        }
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.menu-item')) {
                document.querySelectorAll('.mega-dropdown.active').forEach(d => d.classList.remove('active'));
            }
        });

        function toggleMobileDrawer() {
            document.getElementById('mobile-overlay').classList.toggle('active');
            document.getElementById('mobile-drawer').classList.toggle('active');
        }

        function getWishlist() {
            return JSON.parse(localStorage.getItem('aria_wishlist') || '[]');
        }
        function toggleWishlist(slug, el) {
            let list = getWishlist();
            if (list.includes(slug)) {
                list = list.filter(s => s !== slug);
                showToast('از علاقه‌مندی‌ها حذف شد');
                if (el) el.classList.remove('active');
            } else {
                list.push(slug);
                showToast('به علاقه‌مندی‌ها اضافه شد');
                if (el) el.classList.add('active');
            }
            localStorage.setItem('aria_wishlist', JSON.stringify(list));
            updateWishlistBadge();
            if (window.location.search.includes('route=wishlist')) renderWishlistPage();
        }
        function updateWishlistBadge() {
            const badge = document.getElementById('wishlist-badge');
            if (badge) badge.textContent = getWishlist().length;
        }

        function renderWishlistPage() {
            const container = document.getElementById('wishlist-container');
            if (!container) return;
            const list = getWishlist();
            if (list.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding:60px; background:var(--card-bg); border:1px solid var(--border); border-radius:16px;">
                        <span class="material-symbols-outlined" style="font-size:50px; color:var(--muted); margin-bottom:12px;">bookmark</span>
                        <h3 style="margin-bottom:8px;">لیست علاقه‌مندی‌های شما خالی است</h3>
                        <p style="color:var(--muted); margin-bottom:20px;">شما هنوز محصولی را به علاقه‌مندی‌ها اضافه نکرده‌اید.</p>
                        <a href="?route=shop" class="btn-buy">مشاهده محصولات</a>
                    </div>
                `;
                return;
            }
            fetch('?api=wishlist_products', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ slugs: list })
            })
            .then(r => r.json())
            .then(products => {
                if (!products || products.length === 0) {
                    container.innerHTML = '<div style="text-align:center; padding:40px; color:var(--muted);">محصولات ذخیره‌شده یافت نشدند.</div>';
                    return;
                }
                container.innerHTML = '<div class="products-grid">' + products.map(p => `
                    <div class="product-card">
                        <div class="card-img-wrap">
                            <button class="wishlist-btn-card active" onclick="toggleWishlist('${p.slug}', this)"><span class="material-symbols-outlined">delete</span></button>
                            <a href="?product=${p.slug}"><img src="${p.image}" alt=""></a>
                        </div>
                        <div class="card-body">
                            <div class="card-cat">${p.category_slug}</div>
                            <a href="?product=${p.slug}" class="card-title">${p.title}</a>
                            <div class="card-footer">
                                <div>
                                    ${p.formatted_old_price ? `<span class="old-price">${p.formatted_old_price}</span>` : ''}
                                    <div class="new-price">${p.formatted_price}</div>
                                </div>
                                <a href="${p.external_link || '?product=' + p.slug}" ${p.external_link ? 'target="_blank" rel="nofollow"' : ''} class="btn-buy">
                                    <span class="material-symbols-outlined" style="font-size:16px;">open_in_new</span>
                                    <span>خرید محصول</span>
                                </a>
                            </div>
                        </div>
                    </div>
                `).join('') + '</div>';
            });
        }

        function toggleLike(slug, type) {
            const fd = new FormData();
            fd.append('slug', slug);
            fd.append('type', type);
            fetch('?api=like', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.liked ? 'لایک شد ♥' : 'لایک برداشته شد');
                        const el = document.getElementById('like-count-num');
                        if (el) el.textContent = res.count;
                    }
                });
        }

        function submitCommentForm(e, slug, type) {
            e.preventDefault();
            const name = document.getElementById('comm-name').value;
            const rating = document.getElementById('comm-rating').value;
            const comment = document.getElementById('comm-text').value;
            const fd = new FormData();
            fd.append('slug', slug);
            fd.append('type', type);
            fd.append('name', name);
            fd.append('rating', rating);
            fd.append('comment', comment);

            fetch('?api=comment', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast('دیدگاه شما ثبت شد ♥');
                        document.getElementById('comm-text').value = '';
                        setTimeout(() => window.location.reload(), 1200);
                    }
                });
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            if (!toast) return;
            toast.textContent = msg;
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 2800);
        }

        window.addEventListener('DOMContentLoaded', () => {
            const icon = document.getElementById('theme-icon');
            if (icon) icon.textContent = savedTheme === 'dark' ? 'light_mode' : 'dark_mode';
            updateWishlistBadge();
            if (window.location.search.includes('route=wishlist')) renderWishlistPage();
        });
    </script>
    <?= $settings['footer_scripts'] ?>
</body>
</html>
