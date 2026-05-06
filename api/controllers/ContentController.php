<?php
    require_once 'core/Helpers.php';

    class ContentController {
        private $db;
        private $auth;

        private $uploadDir = 'uploads';  // Корневая папка для файлов
        private $maxSize = 10 * 1024 * 1024;
        private $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }
        

        
        /**
         * GET /content/article/get/{article_id} - получить статью
         */
        public function articleGet($articleId) {
            // Получаем статью
            $article = $this->db->fetchOne("
                    SELECT
                        a.id,
                        a.title,
                        a.content_html AS contentHtml,
                        f.file_path AS coverMediaUrl,
                        f.file_title AS coverMediaTitle,
                        a.read_time AS readTime,
                        a.created_at AS createdAt,
                        a.updated_at AS updatedAt
                    FROM
                        articles a
                        LEFT JOIN files f ON a.cover_media_id = f.id
                    WHERE
                        a.id = ?
                ",
                [$articleId]
            );
            
            $article['coverMediaUrl'] = Helpers::fileUrl($article['coverMediaUrl'] ?? null);
            
            Helpers::jsonResponse(['success' => true, 'article' => $article]);
        }
        
        /**
         * POST /content/article/create - создать статью
         */
        public function articleCreate() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);
            
            $dataTitle = $data['title'] ?? '';
            $coverMediaId = $data['coverMediaId'] ?? null;
            $dataContent = $data['content'] ?? '';
            
            $title = trim($dataTitle) ?: null;
            $contentRaw = trim($dataContent) ?: null;
            $contentHtml = Helpers::markdownToHtml($contentRaw);

            
            // Проверка на содержимое контента
            if (empty($title)) {
                Helpers::errorResponse('Заголовок обязателен', 400);
            }
            if (mb_strlen($title) > 255) {
                Helpers::errorResponse('Заголовок не должен превышать 255 символов', 400);
            }
            if (empty($contentRaw)) {
                Helpers::errorResponse('Контент обязателен', 400);
            }


            // Проверяем, что coverMediaId действительно существует и доступен текущему пользователю
            if ($coverMediaId !== null) {
                $media = $this->db->fetchOne("
                        SELECT
                            id
                        FROM
                            files
                        WHERE
                            id = ?
                            AND
                            (uploaded_by = ? OR is_public = 1)
                    ",
                    [$coverMediaId, $currentUserId]
                );
                if (!$media) {
                    Helpers::errorResponse('Указанный медиафайл не найден или недоступен', 400);
                }
            }
            

            // Вычисляем время чтения (примерно 200 слов/мин, минимум 1 мин)
            $wordCount = count(preg_split('/\s+/u', $contentRaw));
            $readTime = max(1, ceil($wordCount / 200));


            // Создание статьи
            try {
                $this->db->query("
                        INSERT INTO articles (author_id, title, cover_media_id, content_raw, content_html, read_time, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ",
                    [$currentUserId, $title, $coverMediaId, $contentRaw, $contentHtml, $readTime]
                );
                $newArticleId = $this->db->lastInsertId();
                
                Helpers::jsonResponse(['success' => true, 'newContentId' => $newArticleId]);

            } catch (Exception $e) {
                Helpers::errorResponse('Ошибка создания статьи: ' . $e->getMessage(), 500);
            }
        }

        /**
         * POST /file/upload - загрузить файл
         * Ожидает multipart/form-data с полем "file"
         */
        public function fileUpload() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];
            
            
            // Проверяем наличие файла
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                Helpers::errorResponse('Файл не получен или ошибка загрузки', 400);
                return;
            }

            $file = $_FILES['file'];
            $tmpPath = $file['tmp_name'];
            $originalName = basename($file['name']);
            $mimeType = $file['type'];
            $size = $file['size'];


            // Проверка размера
            if ($size > $this->maxSize) {
                Helpers::errorResponse('Файл слишком большой. Максимум 10 МБ', 413);
            }
            
            // Проверка mime-типа и расширения
            if (!array_key_exists($mimeType, $this->allowedTypes)) {
                Helpers::errorResponse('Недопустимый тип файла. Разрешены: JPEG, PNG, GIF, WebP', 415);
            }
            
            // Дополнительная проверка реального MIME-типа через finfo (безопасность)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            if (!array_key_exists($realMime, $this->allowedTypes)) {
                Helpers::errorResponse('Подозрительный файл – реальное содержимое не соответствует заявленному', 400);
            }


            // Генерируем уникальное имя файла
            $ext = $this->allowedTypes[$realMime];
            $newFileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $yearMonth = date('Y/m');
            $relativeDir = $this->uploadDir . '/' . $yearMonth;
            $absoluteDir = __DIR__ . '/../' . $relativeDir;  // Путь от корня проекта

            if (!is_dir($absoluteDir)) {
                if (!mkdir($absoluteDir, 0755, true)) {
                    Helpers::errorResponse('Не удалось создать папку для загрузки', 500);
                }
            }

            $destination = $absoluteDir . '/' . $newFileName;
            if (!move_uploaded_file($tmpPath, $destination)) {
                Helpers::errorResponse('Ошибка при сохранении файла', 500);
            }
            chmod($destination, 0644);  // Гарантирует, что файл будет доступен для чтения веб-серверу, но не для выполнения
            



            // Сохраняем в БД
            $filePath = $relativeDir . '/' . $newFileName;
            $fileUrl = Helpers::apiBaseUrl() . '/' . $filePath;
            try {
                $this->db->query("
                        INSERT INTO files (uploaded_by, file_path, file_title, mime_type, size, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ",
                    [$currentUserId, $filePath, $originalName, $realMime, $size]
                );
                $fileId = $this->db->lastInsertId();

                Helpers::jsonResponse([
                    'success' => true,
                    'file' => [
                        'id'        => $fileId,
                        'url'       => $fileUrl,
                        'title'     => $originalName,
                        'extension' => $ext,
                        'mimeType'  => $realMime,
                        'size'      => $size
                    ]
                ]);

            } catch (Exception $e) {
                // Если не удалось записать в БД, удаляем файл
                unlink($destination);
                Helpers::errorResponse('Ошибка сохранения информации о файле: ' . $e->getMessage(), 500);
            }
        }
    }
?>
